<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')
    ->middleware(['auth', 'user.type:staff'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Staff users
        Route::middleware('permission:users.view')->group(function () {
            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff/data', [StaffController::class, 'data'])->name('staff.data');
        });
        Route::middleware('permission:users.create')->group(function () {
            Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
            Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        });
        Route::middleware('permission:users.edit')->group(function () {
            Route::get('/staff/{user}/edit', [StaffController::class, 'edit'])->name('staff.edit');
            Route::put('/staff/{user}', [StaffController::class, 'update'])->name('staff.update');
            Route::post('/staff/{user}/toggle', [StaffController::class, 'toggle'])->name('staff.toggle');
        });

        // Roles & permissions
        Route::middleware('permission:roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        });
        Route::middleware('permission:roles.edit')
            ->put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
