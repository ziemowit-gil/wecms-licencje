<?php

namespace App\Licensing;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\License;
use Illuminate\Support\Facades\DB;

/** Wystawianie, przedłużanie i unieważnianie licencji (z wpisem w dzienniku). */
class LicenseIssuer
{
    public function __construct(private readonly LicenseSigner $signer) {}

    /** @param array{domain: string, edition: string, expires_on: ?string, note: ?string} $data */
    public function issue(Customer $customer, array $data): License
    {
        $domain = self::normalizeDomain($data['domain']);
        $issuedOn = today()->toDateString();

        $key = $this->signer->issue([
            'd' => $domain,
            'e' => $data['expires_on'] ?: null,
            'x' => $data['edition'],
            'n' => (string) ($data['note'] ?? ''),
            'i' => $issuedOn,
        ]);

        $license = $customer->licenses()->create([
            'domain' => $domain,
            'edition' => $data['edition'],
            'issued_on' => $issuedOn,
            'expires_on' => $data['expires_on'] ?: null,
            'note' => $data['note'] ?? null,
            'license_key' => $key,
            'key_hash' => LicenseKey::hash($key),
            'issued_by' => auth()->id(),
        ]);

        Activity::log("Wystawiono klucz dla {$domain} ({$license->editionLabel()}, "
            .($license->expires_on?->format('d.m.Y') ?? 'bezterminowo').').', $license);

        return $license;
    }

    /** Nowy klucz w miejsce starego (przedłużenie, zmiana edycji lub domeny). */
    public function renew(License $old, array $data): License
    {
        return DB::transaction(function () use ($old, $data) {
            $new = $this->issue($old->customer, $data);
            $old->forceFill(['replaced_by_id' => $new->id])->save();
            Activity::log("Klucz #{$old->id} zastąpiony kluczem #{$new->id}.", $old);

            return $new;
        });
    }

    public function revoke(License $license, string $reason): void
    {
        $license->forceFill(['revoked_at' => now(), 'revoke_reason' => $reason])->save();
        Activity::log("Unieważniono klucz dla {$license->domain}: {$reason}", $license);
    }

    /** „https://www.Klient.pl/” → „klient.pl”; „*” zostaje. */
    public static function normalizeDomain(string $value): string
    {
        $value = trim(strtolower($value));
        if ($value === '*') {
            return '*';
        }

        $host = parse_url(str_contains($value, '://') ? $value : 'https://'.$value, PHP_URL_HOST) ?: $value;

        return (string) preg_replace('/^www\./', '', $host);
    }
}
