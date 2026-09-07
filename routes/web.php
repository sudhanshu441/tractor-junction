<?php

use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PasswordLoginController;
use App\Http\Controllers\Web\CompareController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| Public website. Every route renders a full server-side response so the page is
| crawlable and usable without JavaScript; AJAX only enhances what is already here.
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/search', [SearchController::class, 'index'])->name('search');

// ----- comparison (before the catalogue routes so /compare is not eaten by a slug) -----
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::get('/compare/{slug}', [CompareController::class, 'show'])->name('compare.show');

// ----- state price lists -----
Route::get('/tractors/price-list/{state}', [ProductController::class, 'priceList'])->name('products.price-list');

/*
| Catalogue. One controller serves every machinery type; the URL segment maps to
| the category type, so /implements and /harvesters are the same code path.
*/
$types = [
    'tractors' => 'tractor',
    'implements' => 'implement',
    'harvesters' => 'harvester',
    'tractor-tyres' => 'tyre',
    'farm-tools' => 'farm_tool',
];

foreach ($types as $segment => $type) {
    Route::prefix($segment)->group(function () use ($type, $segment) {
        Route::get('/', fn (Request $r) => app(ProductController::class)->index($r, $type))
            ->name("catalog.{$segment}.index");

        Route::get('/popular', fn (Request $r) => app(ProductController::class)->collection($r, $type, 'popular'))
            ->name("catalog.{$segment}.popular");
        Route::get('/latest', fn (Request $r) => app(ProductController::class)->collection($r, $type, 'latest'))
            ->name("catalog.{$segment}.latest");
        Route::get('/upcoming', fn (Request $r) => app(ProductController::class)->collection($r, $type, 'upcoming'))
            ->name("catalog.{$segment}.upcoming");

        Route::get('/brand/{brand}', fn (Request $r, string $brand) => app(ProductController::class)->brand($r, $type, $brand))
            ->name("catalog.{$segment}.brand");

        Route::get('/hp/{band}', fn (Request $r, string $band) => app(ProductController::class)->band($r, $type, 'hp', $band))
            ->name("catalog.{$segment}.hp");
        Route::get('/price/{band}', fn (Request $r, string $band) => app(ProductController::class)->band($r, $type, 'price', $band))
            ->name("catalog.{$segment}.price");

        Route::get('/{brandSlug}/{productSlug}', [ProductController::class, 'show'])
            ->name($segment === 'tractors' ? 'products.show' : "catalog.{$segment}.show");
    });
}

Route::get('/brands/{brand}', fn (string $brand) => redirect()->route('catalog.tractors.brand', $brand))->name('brands.show');

// ----- auth -----
Route::middleware('guest')->group(function () {
    Route::get('/login', [OtpLoginController::class, 'show'])->name('login');
    Route::get('/login/password', [PasswordLoginController::class, 'show'])->name('login.password');
    Route::post('/login/password', [PasswordLoginController::class, 'store'])->name('login.password.store');
});

Route::post('/logout', [OtpLoginController::class, 'logout'])->middleware('auth')->name('logout');
