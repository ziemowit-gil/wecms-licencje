<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('logowanie', [AuthController::class, 'show'])->name('login');
    Route::post('logowanie', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('wyloguj', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('klienci', CustomerController::class)
        ->parameters(['klienci' => 'customer'])
        ->names('customers');

    Route::resource('licencje', LicenseController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['licencje' => 'license'])
        ->names('licenses');
    Route::get('licencje/{license}/nowy-klucz', [LicenseController::class, 'renewForm'])->name('licenses.renew-form');
    Route::post('licencje/{license}/nowy-klucz', [LicenseController::class, 'renew'])->name('licenses.renew');
    Route::post('licencje/{license}/uniewaznij', [LicenseController::class, 'revoke'])->name('licenses.revoke');
    Route::get('licencje/{license}/pobierz', [LicenseController::class, 'download'])->name('licenses.download');

    Route::get('ustawienia', SettingsController::class)->name('settings');
});
