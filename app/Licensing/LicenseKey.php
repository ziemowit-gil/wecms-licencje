<?php

namespace App\Licensing;

/**
 * Format klucza weCMS — MUSI zgadzać się z App\Support\LicenseValidator w feer-web:
 *
 *   WCMS-<base32(payload JSON) w blokach po 8>.<base32(podpis RSA SHA-256) w blokach po 8>
 *
 * Base32 (RFC 4648, bez dopełnienia) nie rozróżnia wielkości liter, więc klucz
 * wklejony małymi literami czy połamany w wierszach nadal się weryfikuje.
 */
final class LicenseKey
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function encode(string $payload, string $signature): string
    {
        return 'WCMS-'
            .implode('-', str_split(self::base32Encode($payload), 8))
            .'.'
            .implode('-', str_split(self::base32Encode($signature), 8));
    }

    /** @return array{0: string, 1: string}|null [payload JSON, podpis] */
    public static function decode(string $key): ?array
    {
        $parts = explode('.', self::normalize($key));
        if (count($parts) !== 2) {
            return null;
        }

        $payload = self::base32Decode($parts[0]);
        $signature = self::base32Decode($parts[1]);

        return $payload && $signature ? [$payload, $signature] : null;
    }

    /** Postać kanoniczna (bez prefiksu, myślników i białych znaków, wielkie litery). */
    public static function normalize(string $key): string
    {
        $body = strtoupper((string) preg_replace('/\s+/', '', $key));

        return str_replace('-', '', (string) preg_replace('/^WCMS-?/', '', $body));
    }

    /** Odcisk klucza do wyszukiwania w bazie (API sprawdzania licencji). */
    public static function hash(string $key): string
    {
        return hash('sha256', self::normalize($key));
    }

    private static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $out;
    }

    private static function base32Decode(string $input): ?string
    {
        $bits = '';
        foreach (str_split($input) as $char) {
            $pos = strpos(self::ALPHABET, $char);
            if ($pos === false) {
                return null;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
