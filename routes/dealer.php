<?php

use App\Http\Controllers\Dealer\BillingController;
use App\Http\Controllers\Dealer\DashboardController;
use App\Http\Controllers\Dealer\InventoryController;
use App\Http\Controllers\Dealer\LeadInboxController;
use Illuminate\Support\Facades\Route;

Route::prefix('dealer')->name('dealer.')
    ->middleware(['auth', 'user.type:dealer'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('dealer.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/leads', [LeadInboxController::class, 'index'])->name('leads.index');
        Route::get('/leads/{lead}', [LeadInboxController::class, 'show'])->name('leads.show');
        Route::post('/leads/{lead}/status', [LeadInboxController::class, 'updateStatus'])->name('leads.status');
        Route::post('/leads/{lead}/note', [LeadInboxController::class, 'addNote'])->name('leads.note');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::delete('/inventory/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/checkout/{plan}', [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::post('/billing/confirm/{payment}', [BillingController::class, 'confirm'])->name('billing.confirm');
    });
