<?php

use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PasswordLoginController;
use App\Http\Controllers\Web\DocumentController;
use App\Http\Controllers\Web\SitemapController;
use Illuminate\Support\Facades\Route;

/*
| Routes that exist once, at the root, whatever the reader's language.
|
| The public website lives in routes/public.php, which is registered here for
| English and again under /hi for Hindi.
*/

/*
| Private documents: signed, short-lived, and still policy-checked inside the
| controller. Never served from public storage.
*/
Route::middleware(['auth', 'signed'])->group(function () {
    Route::get('/documents/loan/{document}', [DocumentController::class, 'loanDocument'])->name('documents.loan');
    Route::get('/documents/dealer/{document}', [DocumentController::class, 'dealerDocument'])->name('documents.dealer');
});

// ----- crawler files: generated so they can never drift from the routes -----
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-{name}.xml', [SitemapController::class, 'section'])
    ->where('name', '[a-z0-9-]+')->name('sitemap.section');

// ----- auth -----
Route::middleware('guest')->group(function () {
    Route::get('/login', [OtpLoginController::class, 'show'])->name('login');
    Route::get('/login/password', [PasswordLoginController::class, 'show'])->name('login.password');
    Route::post('/login/password', [PasswordLoginController::class, 'store'])->name('login.password.store');
});

Route::post('/logout', [OtpLoginController::class, 'logout'])->middleware('auth')->name('logout');
