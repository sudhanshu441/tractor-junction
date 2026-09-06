<?php

use App\Http\Controllers\Dealer\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('dealer')->name('dealer.')
    ->middleware(['auth', 'user.type:dealer'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('dealer.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
