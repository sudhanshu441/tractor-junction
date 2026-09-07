<?php

use App\Http\Controllers\Ajax\GeoController;
use App\Http\Controllers\Ajax\ProductFilterController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Web\CompareController;
use App\Http\Controllers\Web\SearchController;
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

    // Catalogue
    Route::get('/products/filter', ProductFilterController::class)->name('products.filter');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])
        ->middleware('throttle:60,1')->name('search.suggest');

    // Comparison
    Route::post('/compare/add', [CompareController::class, 'add'])->name('compare.add');
    Route::post('/compare/remove', [CompareController::class, 'remove'])->name('compare.remove');
    Route::post('/compare/clear', [CompareController::class, 'clear'])->name('compare.clear');
});
