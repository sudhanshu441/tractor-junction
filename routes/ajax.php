<?php

use App\Http\Controllers\Ajax\GeoController;
use App\Http\Controllers\Auth\OtpLoginController;
use Illuminate\Support\Facades\Route;

/*
| AJAX endpoints. All CSRF-protected by the web group, all returning the same
| envelope: {status, message?, data?, errors?}.
*/

Route::prefix('ajax')->name('ajax.')->group(function () {
    Route::post('/auth/otp/send', [OtpLoginController::class, 'send'])
        ->middleware('throttle:12,1')->name('otp.send');
    Route::post('/auth/otp/verify', [OtpLoginController::class, 'verify'])
        ->middleware('throttle:12,1')->name('otp.verify');

    Route::get('/geo/states', [GeoController::class, 'states'])->name('geo.states');
    Route::get('/geo/states/{state}/districts', [GeoController::class, 'districts'])->name('geo.districts');
    Route::get('/geo/districts/{district}/cities', [GeoController::class, 'cities'])->name('geo.cities');
});
