<?php

namespace App\Console\Commands;

use App\Models\NumberSms;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Log};
use Throwable;

class SendSMS extends Command
{
    private const MAX_SMS_PER_RUN = 100;
    private const MAX_ATTEMPTS = 3;
    private const STATUS_PENDING = 0;
    private const STATUS_SENT = 1;
    private const STATUS_PROCESSING = 2;
    private const STATUS_FAILED = 3;

    /*
     * À ajuster selon la durée maximale d'un traitement.
     * Une réservation est considérée abandonnée après 2 heures.
     */
    private const STALE_MINUTES = 120;

    protected $signature = 'sms:send';

    protected $description = 'Envoi de SMS avec 3 tentatives maximum';

    public function handle(SmsService $smsService): int
    {
        $this->info('Début du traitement des SMS en attente.');
        /*
         * 1. Récupérer les réservations abandonnées.
         * Ne pas remettre en attente les SMS ayant épuisé
         * leurs trois tentatives.
         */
        $this->recoverStaleSms();

        /*
         * 2. Réserver au maximum 50 SMS.
         * L'incrémentation des tentatives se fait au moment
         * de la réservation, avant l'appel réseau.
         */
        try {
            $ids = DB::transaction(function () {
                $ids = NumberSms::query()
                    ->select('numbersms.id')
                    ->join('bodysms', 'bodysms.id', '=', 'numbersms.bodysms_id')
                    ->where('bodysms.status', 1)
                    ->where('numbersms.status', self::STATUS_PENDING)
                    ->where('numbersms.attempts', '<', self::MAX_ATTEMPTS)
                    ->orderBy('numbersms.id')
                    ->limit(self::MAX_SMS_PER_RUN)
                    ->lockForUpdate()
                    ->pluck('numbersms.id');

                if ($ids->isNotEmpty()) {
                    NumberSms::whereIn('id', $ids)
                        ->where('status',self::STATUS_PENDING)
                        ->update([
                            'status' => self::STATUS_PROCESSING,
                            'attempts' => DB::raw('attempts + 1'),
                            'processing_at' => now(),
                        ]);
                }

                return $ids;
            }, 3);
        } catch (Throwable $e) {
            Log::error('Impossible de réserver les SMS', [
                'error' => $e->getMessage(),
            ]);

            $this->error('Erreur lors de la réservation des SMS.');

            return self::FAILURE;
        }

        if ($ids->isEmpty()) {
            $this->info('Aucun SMS en attente.');

            return self::SUCCESS;
        }

        /*
         * 3. Charger les SMS réservés.
         * Les appels réseau sont effectués hors transaction.
         */
        $smsList = NumberSms::query()
            ->select(
                'numbersms.id as numbersms_id',
                'numbersms.number',
                'numbersms.attempts',
                'bodysms.sender',
                'bodysms.message'
            )
            ->join('bodysms', 'bodysms.id', '=', 'numbersms.bodysms_id')
            ->whereIn('numbersms.id', $ids)
            ->where('numbersms.status', self::STATUS_PROCESSING)
            ->where('bodysms.status', 1)
            ->orderBy('numbersms.id')
            ->get();

        $sent = 0;
        $retry = 0;
        $failed = 0;

        foreach ($smsList as $sms) {
            try {
                $result = $smsService->sendSms(
                    expediteur: $sms->sender,
                    numeros: $sms->number,
                    message: $sms->message,
                    idsms: (string) $sms->numbersms_id
                );

                if (!empty($result['success'])) {
                    /*
                     * 4. Succès : le SMS est accepté
                     * par la passerelle.
                     */
                    $updated = NumberSms::where('id', $sms->numbersms_id)
                        ->where('status', self::STATUS_PROCESSING)
                        ->update([
                            'status' => self::STATUS_SENT,
                            'sending_at' => now(),
                            'processing_at' => null,
                        ]);

                    if ($updated) {
                        $sent++;

                        $this->info("SMS accepté : {$sms->number}");
                    }

                    continue;
                }

                /*
                 * 5. Échec : réessayer si le compteur
                 * est inférieur à 3, sinon échec définitif.
                 */
                $isFinalFailure = $this->handleFailure(
                    (int) $sms->numbersms_id,
                    (int) $sms->attempts,
                    $result['message'] ?? 'Échec de soumission'
                );

                if ($isFinalFailure) {
                    $failed++;

                    $this->error("Échec définitif du SMS {$sms->numbersms_id}");
                } else {
                    $retry++;

                    $this->warn("SMS {$sms->numbersms_id} remis en attente.");
                }
            } catch (Throwable $e) {
                $isFinalFailure = $this->handleFailure(
                    (int) $sms->numbersms_id,
                    (int) $sms->attempts,
                    $e->getMessage()
                );

                if ($isFinalFailure) {
                    $failed++;
                } else {
                    $retry++;
                }

                Log::error('Erreur pendant le traitement SMS', [
                    'numbersms_id' => $sms->numbersms_id,
                    'attempts' => $sms->attempts,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("SMS acceptés : {$sent}");
        $this->info("À retenter : {$retry}");
        $this->info("Échecs définitifs : {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Traiter un échec d'envoi.
     */
    private function handleFailure(int $id, int $attempts, string $message): bool
    {
        $finalFailure = $attempts >= self::MAX_ATTEMPTS;

        $updated = NumberSms::where('id', $id)
            ->where('status', self::STATUS_PROCESSING)
            ->update([
                'status' => $finalFailure
                    ? self::STATUS_FAILED
                    : self::STATUS_PENDING,
                'processing_at' => null,
            ]);

        Log::warning('Résultat de la tentative SMS', [
            'numbersms_id' => $id,
            'attempt' => $attempts,
            'max_attempts' => self::MAX_ATTEMPTS,
            'final_failure' => $finalFailure,
            'updated' => $updated,
            'message' => $message,
        ]);

        return $finalFailure;
    }

    /**
     * Récupérer les réservations devenues obsolètes.
     */
    private function recoverStaleSms(): void
    {
        $threshold = now()->subMinutes(self::STALE_MINUTES);

        /*
         * Les SMS dont les tentatives sont épuisées
         * ne doivent pas être remis en attente.
         */
        $failed = NumberSms::where('status', self::STATUS_PROCESSING)
            ->where('processing_at', '<', $threshold)
            ->where('attempts', '>=', self::MAX_ATTEMPTS)
            ->update([
                'status' => self::STATUS_FAILED,
                'processing_at' => null,
            ]);

        /*
         * Les autres SMS peuvent être retentés.
         */
        $released = NumberSms::where('status', self::STATUS_PROCESSING)
            ->where('processing_at', '<', $threshold)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->update([
                'status' => self::STATUS_PENDING,
                'processing_at' => null,
            ]);

        if ($failed > 0 || $released > 0) {
            Log::warning('Récupération des SMS abandonnés', [
                'released_for_retry' => $released,
                'marked_as_failed' => $failed,
            ]);
        }
    }
}
