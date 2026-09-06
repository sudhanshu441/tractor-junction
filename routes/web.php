<?php

use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PasswordLoginController;
use App\Http\Controllers\Web\HomeController;
use Illuminate\Support\Facades\Route;

/*
| Public website. Every route here renders a full server-side response so the
| page is crawlable and usable without JavaScript.
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [OtpLoginController::class, 'show'])->name('login');
    Route::get('/login/password', [PasswordLoginController::class, 'show'])->name('login.password');
    Route::post('/login/password', [PasswordLoginController::class, 'store'])->name('login.password.store');
});

Route::post('/logout', [OtpLoginController::class, 'logout'])->middleware('auth')->name('logout');
