<?php

namespace App\Http\Controllers;

use App\Licensing\LicenseSigner;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\License;

class DashboardController extends Controller
{
    public function __invoke(LicenseSigner $signer)
    {
        $counts = collect(array_keys(License::STATUSES))
            ->mapWithKeys(fn ($status) => [$status => License::inStatus($status)->count()]);

        return view('dashboard', [
            'counts' => $counts,
            'customers' => Customer::count(),
            'expiring' => License::inStatus('expiring')->with('customer')->orderBy('expires_on')->get(),
            'activities' => Activity::with('user', 'license')->latest('id')->limit(15)->get(),
            'signerReady' => $signer->configured(),
        ]);
    }
}
