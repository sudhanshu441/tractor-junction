<?php

use App\Http\Controllers\Account\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')
    ->middleware(['auth', 'user.type:customer,dealer,staff'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('account.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
