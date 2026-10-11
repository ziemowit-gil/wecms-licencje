<?php

namespace App\Console\Commands;

use App\Licensing\LicenseSigner;
use Illuminate\Console\Command;

class GenerateKeypair extends Command
{
    protected $signature = 'licencje:keypair {--force : Nadpisz istniejący klucz (unieważnia WSZYSTKIE wydane licencje)}';

    protected $description = 'Wygeneruj parę kluczy RSA do podpisywania licencji weCMS';

    public function handle(LicenseSigner $signer): int
    {
        $path = $signer->path();

        if (file_exists($path) && ! $this->option('force')) {
            $this->error("Klucz już istnieje: {$path}. Nowy klucz unieważni wszystkie wydane licencje — użyj --force, jeśli naprawdę tego chcesz.");

            return self::FAILURE;
        }

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, $pem);
        chmod($path, 0600);

        $this->info("Zapisano klucz prywatny: {$path}");
        $this->line('Zrób jego kopię zapasową w bezpiecznym miejscu. Do .env instalacji weCMS:');
        $this->line($signer->publicKeyEnvLine());

        return self::SUCCESS;
    }
}
