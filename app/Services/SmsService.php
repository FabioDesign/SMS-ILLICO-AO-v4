<?php

namespace App\Services;

use Throwable;
use Illuminate\Support\Facades\{Http, Log};
use Illuminate\Http\Client\ConnectionException;

class SmsService
{
    /**
     * Envoie un SMS via la passerelle SMS.
     */
    public function sendSms(
        string $expediteur,
        string $numeros,
        string $message,
        string $idsms
    ): array {
        $url = rtrim(config('services.sms.url'), '/')
            . '/cgi-bin/sendsms';

        $dlrUrl = config('services.sms.dlr_url')
            . '?type=%d&smsc_id=%F&idsms=' . rawurlencode($idsms);

        try {
            $response = Http::connectTimeout(5)
                ->timeout(60)
                ->get($url, [
                    'username' => config('services.sms.username'),
                    'password' => config('services.sms.password'),
                    'from' => $expediteur,
                    'to' => $numeros,
                    'text' => $message,
                    'coding' => 2,
                    'charset' => 'UTF-8',
                    'dlr-url' => $dlrUrl,
                ]);

            $body = trim($response->body());

            // La passerelle doit confirmer l'acceptation du SMS.
            $accepted = $response->successful()
                && str_contains(
                    strtolower($body),
                    '0: accepted for delivery'
                );

            if (!$accepted) {
                Log::warning('Échec de soumission du SMS', [
                    'http_status' => $response->status(),
                    'response' => $body,
                    'idsms' => $idsms,
                ]);
            }

            return [
                'success' => $accepted,
                'idsms' => $idsms,
                'http_status' => $response->status(),
                'message' => $accepted
                    ? 'SMS accepté par la passerelle.'
                    : 'La passerelle n’a pas confirmé l’acceptation du SMS.',
                'response' => $body,
            ];
        } catch (ConnectionException $e) {
            Log::error('Connexion à la passerelle SMS impossible', [
                'idsms' => $idsms,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'idsms' => $idsms,
                'message' => 'Impossible de joindre la passerelle SMS.',
            ];
        } catch (Throwable $e) {
            Log::error('Erreur lors de l’envoi du SMS', [
                'idsms' => $idsms,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'idsms' => $idsms,
                'message' => 'Une erreur est survenue lors de l’envoi.',
            ];
        }
    }
}
