<?php

use App\Http\Controllers\Api\LicenseCheckController;
use Illuminate\Support\Facades\Route;

Route::post('v1/licenses/check', LicenseCheckController::class)
    ->middleware('throttle:30,1')
    ->name('api.licenses.check');
