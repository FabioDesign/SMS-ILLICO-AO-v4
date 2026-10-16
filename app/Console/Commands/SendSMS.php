<?php

namespace App\Console\Commands;

use Throwable;
use App\Models\NumberSms;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSMS extends Command
{
    protected $signature = 'sms:send';

    protected $description = 'Envoi de SMS Standard';

    public function handle(SmsService $smsService): int
    {
        $this->info('Début du traitement des SMS en attente...');

        $query = NumberSms::select(
            'numbersms.id as numbersms_id',
            'bodysms.id as bodysms_id',
            'bodysms.sender',
            'bodysms.message',
            'numbersms.number'
        )
        ->join(
            'bodysms',
            'bodysms.id',
            '=',
            'numbersms.bodysms_id'
        )
        ->where('bodysms.status', 1)
        ->where('numbersms.status', 0)
        ->orderBy('numbersms.id')
        ->limit(50)
        ->get();

        if ($query->isEmpty()) {
            $this->info('Aucun SMS en attente.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($query as $data) {
            try {
                $result = $smsService->sendSms(
                    expediteur: $data->sender,
                    numeros: $data->number,
                    message: $data->message,
                    idsms: (string) $data->numbersms_id
                );

                if ($result['success']) {
                    // À adapter selon les colonnes de ta table.
                    NumberSms::where('id', $data->numbersms_id)
                        ->update([
                            'status' => 1,
                            'sending_at' => now(),
                        ]);

                    $sent++;

                    $this->info(
                        "SMS accepté : {$data->number}"
                    );
                } else {
                    $failed++;

                    Log::warning('Échec de soumission SMS', [
                        'numbersms_id' => $data->numbersms_id,
                        'number' => $data->number,
                        'result' => $result,
                    ]);

                    $this->warn(
                        "Échec de l'envoi vers {$data->number}"
                    );
                }
            } catch (Throwable $e) {
                $failed++;

                Log::error('Erreur de traitement SMS', [
                    'numbersms_id' => $data->numbersms_id,
                    'error' => $e->getMessage(),
                ]);

                $this->error(
                    "Erreur lors du traitement du SMS ID "
                    . $data->numbersms_id
                );
            }
        }

        $this->info("Traitement terminé. Acceptés : {$sent}.");
        $this->info("Échecs : {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
