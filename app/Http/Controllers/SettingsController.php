<?php

namespace App\Http\Controllers;

use App\Licensing\LicenseSigner;

class SettingsController extends Controller
{
    public function __invoke(LicenseSigner $signer)
    {
        return view('settings', [
            'signer' => $signer,
            'envLine' => $signer->configured() ? $signer->publicKeyEnvLine() : null,
        ]);
    }
}
