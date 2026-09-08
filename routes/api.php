<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\MarketplaceController;
use Illuminate\Support\Facades\Route;

/*
| REST API v1 for the mobile app.
|
| Every response uses the same envelope as the website's AJAX layer —
| {status, message?, data?, errors?} — so one client-side helper handles both.
|
| Public endpoints are read-only. Anything that writes, or that could identify a
| person, sits behind a Sanctum token.
*/

Route::prefix('api/v1')->name('api.v1.')->middleware('api')->group(function () {

    // ----- authentication: the same OTP flow as the website -----
    Route::post('/auth/otp', [AuthController::class, 'sendOtp'])
        ->middleware('throttle:10,1')->name('auth.otp');
    Route::post('/auth/verify', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:10,1')->name('auth.verify');

    /*
    | Public reads. A farmer should be able to compare prices before being
    | asked to create anything.
    */
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/products', [CatalogController::class, 'products'])->name('products.index');
        Route::get('/products/{brandSlug}/{productSlug}', [CatalogController::class, 'product'])->name('products.show');
        Route::get('/brands', [CatalogController::class, 'brands'])->name('brands');
        Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
        Route::get('/filters', [CatalogController::class, 'filters'])->name('filters');

        Route::get('/listings', [MarketplaceController::class, 'listings'])->name('listings.index');
        Route::get('/listings/{slug}', [MarketplaceController::class, 'listing'])->name('listings.show');
        Route::get('/dealers', [MarketplaceController::class, 'dealers'])->name('dealers');

        Route::post('/emi', [MarketplaceController::class, 'emi'])->name('emi');
    });

    // ----- token required -----
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/account/listings', [AccountController::class, 'listings'])->name('account.listings');
        Route::get('/account/enquiries', [AccountController::class, 'enquiries'])->name('account.enquiries');
        Route::post('/account/enquiries', [AccountController::class, 'enquire'])
            ->middleware('throttle:20,60')->name('account.enquire');

        Route::get('/account/wishlist', [AccountController::class, 'wishlist'])->name('account.wishlist');
        Route::post('/account/wishlist', [AccountController::class, 'saveToWishlist'])->name('account.wishlist.store');
        Route::delete('/account/wishlist/{item}', [AccountController::class, 'removeFromWishlist'])->name('account.wishlist.destroy');
    });
});
