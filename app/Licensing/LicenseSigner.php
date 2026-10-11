<?php

namespace App\Licensing;

use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * Podpisywanie i weryfikacja kluczy kluczem prywatnym z config/licensing.php.
 * Klucz publiczny wyliczamy z prywatnego — w panelu „Ustawienia” jest gotowy do
 * wklejenia jako LICENSE_PUBLIC_KEY w instalacjach weCMS.
 */
class LicenseSigner
{
    public function configured(): bool
    {
        return is_readable($this->path());
    }

    public function path(): string
    {
        return (string) config('licensing.private_key_path');
    }

    /** @param array{d: string, e: ?string, x: string, n: string, i: string} $data */
    public function issue(array $data): string
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! openssl_sign($payload, $signature, $this->privateKey(), OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Nie udało się podpisać klucza: '.openssl_error_string());
        }

        return LicenseKey::encode($payload, $signature);
    }

    /** Payload klucza z poprawnym podpisem albo null. */
    public function verify(string $key): ?array
    {
        $decoded = LicenseKey::decode($key);
        if ($decoded === null) {
            return null;
        }

        [$payload, $signature] = $decoded;
        $public = openssl_pkey_get_public($this->publicKeyPem());

        if (! $public || openssl_verify($payload, $signature, $public, OPENSSL_ALGO_SHA256) !== 1) {
            return null;
        }

        $data = json_decode($payload, true);

        return is_array($data) ? $data : null;
    }

    public function publicKeyPem(): string
    {
        return (string) openssl_pkey_get_details($this->privateKey())['key'];
    }

    /** Linia do .env instalacji weCMS. */
    public function publicKeyEnvLine(): string
    {
        return 'LICENSE_PUBLIC_KEY="'.str_replace("\n", '\n', trim($this->publicKeyPem())).'"';
    }

    private function privateKey(): OpenSSLAsymmetricKey
    {
        if (! $this->configured()) {
            throw new RuntimeException('Brak klucza prywatnego: '.$this->path().' (php artisan licencje:keypair).');
        }

        $key = openssl_pkey_get_private((string) file_get_contents($this->path()));
        if (! $key) {
            throw new RuntimeException('Nie da się wczytać klucza prywatnego z '.$this->path().'.');
        }

        return $key;
    }
}
