<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Calcul du nombre de SMS (segments) d'un message.
 * Port fidèle de sms_counter.js : même jeu de caractères GSM-7, mêmes limites.
 */
final class SmsCounter
{
    public const GSM7    = 'gsm7';
    public const UNICODE = 'unicode';

    private const LIMITS = [
        self::GSM7    => ['single' => 160, 'multi' => 153],
        self::UNICODE => ['single' => 70,  'multi' => 67],
    ];

    // Jeu de base GSM-7 (identique au JS, y compris \n, \r et ESC)
    private const GSM7_BASIC =
        '@£$¥èéùìòÇ' . "\n" . 'Øø' . "\r" . 'ÅåΔ_ΦΓΛΩΠΨΣΘΞ' . "\x1b" . 'ÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?'
        . '¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';

    // Extension GSM-7 : chaque caractère compte pour 2
    private const GSM7_EXT = '^{}\\[~]|€';

    private static ?array $basicSet = null;
    private static ?array $extSet   = null;

    private static function basicSet(): array
    {
        return self::$basicSet ??= array_fill_keys(mb_str_split(self::GSM7_BASIC, 1, 'UTF-8'), true);
    }

    private static function extSet(): array
    {
        return self::$extSet ??= array_fill_keys(mb_str_split(self::GSM7_EXT, 1, 'UTF-8'), true);
    }

    /**
     * Analyse un message : encodage détecté, poids, nombre de SMS, caractères restants.
     */
    public function analyze(string $text): array
    {
        $chars = $text === '' ? [] : mb_str_split($text, 1, 'UTF-8');
        $basic = self::basicSet();
        $ext   = self::extSet();

        $isGsm7      = true;
        $hasExtended = false;
        $gsmLength   = 0;

        foreach ($chars as $ch) {
            if (isset($ext[$ch])) {
                $hasExtended = true;
                $gsmLength  += 2;
            } elseif (isset($basic[$ch])) {
                $gsmLength += 1;
            } else {
                $isGsm7 = false;
            }
        }

        $encoding = $isGsm7 ? self::GSM7 : self::UNICODE;
        $length   = $isGsm7 ? $gsmLength : count($chars);
        ['single' => $single, 'multi' => $multi] = self::LIMITS[$encoding];

        if ($length === 0) {
            $segments  = 0;
            $remaining = $single;
        } elseif ($length <= $single) {
            $segments  = 1;
            $remaining = $single - $length;
        } else {
            $segments  = (int) ceil($length / $multi);
            $remaining = $segments * $multi - $length;
        }

        return [
            'encoding'     => $encoding,
            'characters'   => $length,
            'single_limit' => $single,
            'multi_limit'  => $multi,
            'segments'     => $segments,
            'remaining'    => $remaining,
            'has_extended' => $isGsm7 && $hasExtended,
        ];
    }

    /**
     * Volume total consommé = nombre de SMS du message × nombre de contacts.
     *
     * @param string[] $contacts
     */
    public function volume(string $message, array $contacts): array
    {
        $analysis   = $this->analyze($message);
        $recipients = count($contacts);

        return array_merge($analysis, [
            'contacts'  => $recipients,
            'total_sms' => $analysis['segments'] * $recipients,
        ]);
    }
}
