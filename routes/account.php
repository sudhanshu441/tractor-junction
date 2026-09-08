<?php

use App\Http\Controllers\Account\BoostController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\ListingController;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')
    ->middleware(['auth', 'user.type:customer,dealer,staff'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('account.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
        Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listings.show');
        Route::post('/listings/{listing}/sold', [ListingController::class, 'markSold'])->name('listings.sold');
        Route::post('/listings/{listing}/renew', [ListingController::class, 'renew'])->name('listings.renew');

        Route::get('/listings/{listing}/promote', [BoostController::class, 'show'])->name('boost.show');
        Route::post('/listings/{listing}/promote/{plan}', [BoostController::class, 'checkout'])->name('boost.checkout');
        Route::post('/listings/{listing}/promote/{payment}/confirm', [BoostController::class, 'confirm'])->name('boost.confirm');

        Route::get('/leads', [ListingController::class, 'leads'])->name('leads');
        Route::get('/enquiries', [ListingController::class, 'enquiries'])->name('enquiries');
    });
