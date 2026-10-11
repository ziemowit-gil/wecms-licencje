<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Licensing\LicenseIssuer;
use App\Licensing\LicenseKey;
use App\Licensing\LicenseSigner;
use App\Models\License;
use Illuminate\Http\Request;

/**
 * POST /api/v1/licenses/check  {key, domain}
 *
 * Dla instalacji weCMS, które chcą sprawdzać licencję online — klucz weryfikuje się
 * też offline (podpis), ale tylko tu widać unieważnienie i zastąpienie nowym kluczem.
 * Odpowiedź nie zdradza danych klienta.
 */
class LicenseCheckController extends Controller
{
    public function __invoke(Request $request, LicenseSigner $signer)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:2000'],
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $payload = $signer->verify($data['key']);
        $license = License::where('key_hash', LicenseKey::hash($data['key']))->first();

        if ($payload === null || $license === null) {
            return response()->json(['valid' => false, 'reason' => 'not_found']);
        }

        $domain = LicenseIssuer::normalizeDomain($data['domain']);
        $license->forceFill(['last_check_at' => now(), 'last_check_domain' => $domain])->save();

        $reason = match (true) {
            $license->revoked_at !== null => 'revoked',
            $license->replaced_by_id !== null => 'replaced',
            $license->status() === 'expired' => 'expired',
            $license->domain !== '*' && $license->domain !== $domain => 'domain_mismatch',
            default => null,
        };

        return response()->json([
            'valid' => $reason === null,
            'reason' => $reason,
            'edition' => $license->edition,
            'expires_on' => $license->expires_on?->toDateString(),
        ]);
    }
}
