<?php

use App\Http\Controllers\Web\BlogController;
use App\Http\Controllers\Web\CompareController;
use App\Http\Controllers\Web\DealerDirectoryController;
use App\Http\Controllers\Web\EmiController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\InsuranceController;
use App\Http\Controllers\Web\LoanController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\SearchController;
use App\Http\Controllers\Web\SellController;
use App\Http\Controllers\Web\UsedListingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| Public website. Every route renders a full server-side response so the page is
| crawlable and usable without JavaScript; AJAX only enhances what is already here.
|
| This file is registered TWICE: once bare for English, once under /hi with an
| "hi." name prefix. Views never have to know — LocalizedUrlGenerator rewrites a
| route() call to its Hindi twin when the request locale is Hindi.
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/search', [SearchController::class, 'index'])->name('search');

// ----- comparison (before the catalogue routes so /compare is not eaten by a slug) -----
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::get('/compare/{slug}', [CompareController::class, 'show'])->name('compare.show');

/*
| Used marketplace. Geo segments come before the slug route so /used/listing/x
| is never eaten by a state lookup.
*/
Route::prefix('used')->name('used.')->group(function () {
    Route::get('/', [UsedListingController::class, 'index'])->name('index');
    Route::get('/listing/{slug}', [UsedListingController::class, 'show'])->name('show');
    Route::get('/tractors', [UsedListingController::class, 'index'])->name('tractors');
    Route::get('/tractors/{state}', [UsedListingController::class, 'index'])->name('state');
    Route::get('/tractors/{state}/{district}', [UsedListingController::class, 'index'])->name('district');
});

/*
| Dealer directory. Geo segments sit under a prefix so /dealers/become is not
| mistaken for a state.
*/
Route::get('/dealers/become-a-dealer', [DealerDirectoryController::class, 'joinForm'])->name('dealers.join');
Route::post('/dealers/become-a-dealer', [DealerDirectoryController::class, 'join'])->name('dealers.join.store');
Route::get('/dealers/registered/{code}', [DealerDirectoryController::class, 'joined'])->name('dealers.joined');
Route::get('/dealers', [DealerDirectoryController::class, 'index'])->name('dealers.index');
Route::get('/dealers/profile/{slug}', [DealerDirectoryController::class, 'show'])->name('dealers.show');
Route::get('/dealers/{state}', [DealerDirectoryController::class, 'index'])->name('dealers.state');
Route::get('/dealers/{state}/{district}', [DealerDirectoryController::class, 'index'])->name('dealers.district');

// ----- finance -----
Route::prefix('loan')->group(function () {
    Route::get('/', [LoanController::class, 'hub'])->name('loan.hub');
    Route::get('/apply', [LoanController::class, 'apply'])->name('loan.apply');
    Route::get('/submitted/{reference}', [LoanController::class, 'submitted'])->name('loan.submitted');
    Route::get('/emi-calculator', [EmiController::class, 'index'])->name('emi.index');
    Route::get('/emi-calculator/{brandSlug}/{productSlug}', [EmiController::class, 'index'])->name('emi.product');
});

Route::get('/tractor-insurance', [InsuranceController::class, 'index'])->name('insurance.index');

/*
| Content. The long-tail traffic engine — news, guides, videos and FAQs — so
| every one of these is a full server-rendered page.
*/
Route::get('/news', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/news/category/{category}', [BlogController::class, 'index'])->name('blogs.category');
Route::get('/news/{slug}', [BlogController::class, 'show'])->name('blogs.show');

Route::get('/videos', [PageController::class, 'videos'])->name('videos.index');
Route::get('/videos/{slug}', [PageController::class, 'video'])->name('videos.show');

Route::get('/offers', [PageController::class, 'offers'])->name('offers.index');
Route::get('/offers/{slug}', [PageController::class, 'offer'])->name('offers.show');

Route::get('/faq', [PageController::class, 'faqs'])->name('faqs.index');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/newsletter/unsubscribe', [PageController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// ----- sell wizard -----
Route::get('/sell', [SellController::class, 'start'])->name('sell.start');
Route::get('/sell/submitted/{reference}', [SellController::class, 'submitted'])->name('sell.submitted');

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

/*
| Editor-managed pages claim a bare root slug (/about-us, /privacy-policy), so
| this must stay the last route registered — anything after it is unreachable.
*/
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-z0-9][a-z0-9-]*')
    ->name('pages.show');
