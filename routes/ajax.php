<?php

use App\Http\Controllers\Ajax\GeoController;
use App\Http\Controllers\Ajax\LeadController;
use App\Http\Controllers\Ajax\ListingReportController;
use App\Http\Controllers\Ajax\ProductFilterController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Web\BlogController;
use App\Http\Controllers\Web\CompareController;
use App\Http\Controllers\Web\EmiController;
use App\Http\Controllers\Web\InsuranceController;
use App\Http\Controllers\Web\LoanController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\ReviewController;
use App\Http\Controllers\Web\SearchController;
use App\Http\Controllers\Web\SellController;
use App\Http\Controllers\Web\UsedListingController;
use Illuminate\Support\Facades\Route;

/*
| AJAX endpoints. All CSRF-protected by the web group, all returning the same
| envelope: {status, message?, data?, errors?}.
*/

Route::prefix('ajax')->name('ajax.')->group(function () {
    Route::post('/auth/otp/send', [OtpLoginController::class, 'send'])
        ->middleware('throttle:12,1')->name('otp.send');
    Route::post('/auth/otp/verify', [OtpLoginController::class, 'verify'])
        ->middleware('throttle:12,1')->name('otp.verify');

    Route::get('/geo/states', [GeoController::class, 'states'])->name('geo.states');
    Route::get('/geo/states/{state}/districts', [GeoController::class, 'districts'])->name('geo.districts');
    Route::get('/geo/districts/{district}/cities', [GeoController::class, 'cities'])->name('geo.cities');

    // Catalogue
    Route::get('/products/filter', ProductFilterController::class)->name('products.filter');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])
        ->middleware('throttle:60,1')->name('search.suggest');

    // Used marketplace
    Route::get('/used/filter', [UsedListingController::class, 'filter'])->name('used.filter');
    Route::post('/used/{listing}/report', [ListingReportController::class, 'store'])
        ->middleware('throttle:10,60')->name('used.report');

    // Sell wizard — each step is saved server-side so a dropped connection loses little
    Route::post('/sell/step/{step}', [SellController::class, 'saveStep'])
        ->whereNumber('step')->middleware('throttle:60,1')->name('sell.step');
    Route::get('/sell/models', [SellController::class, 'models'])->name('sell.models');
    Route::post('/sell/photo', [SellController::class, 'uploadPhoto'])
        ->middleware('throttle:40,1')->name('sell.photo');
    Route::delete('/sell/photo/{image}', [SellController::class, 'deletePhoto'])->name('sell.photo.destroy');
    Route::post('/sell/submit', [SellController::class, 'submit'])
        ->middleware('throttle:10,1')->name('sell.submit');

    // Leads
    Route::post('/leads/otp', [LeadController::class, 'sendOtp'])
        ->middleware('throttle:12,1')->name('leads.otp');
    Route::post('/leads', [LeadController::class, 'store'])
        ->middleware('throttle:20,60')->name('leads.store');
    Route::post('/used/{listing}/reveal', [LeadController::class, 'revealContact'])
        ->middleware('throttle:20,60')->name('used.reveal');

    // Finance
    Route::post('/emi/calculate', [EmiController::class, 'calculate'])
        ->middleware('throttle:60,1')->name('emi.calculate');
    Route::post('/loan/eligibility', [LoanController::class, 'eligibility'])
        ->middleware('throttle:30,1')->name('loan.eligibility');
    Route::post('/loan/step/{step}', [LoanController::class, 'saveStep'])
        ->whereNumber('step')->middleware('throttle:60,1')->name('loan.step');
    Route::post('/loan/document', [LoanController::class, 'uploadDocument'])
        ->middleware('throttle:30,1')->name('loan.document');
    Route::post('/loan/submit', [LoanController::class, 'submit'])
        ->middleware('throttle:10,1')->name('loan.submit');

    Route::post('/insurance', [InsuranceController::class, 'store'])
        ->middleware('throttle:10,60')->name('insurance.store');

    // Reviews — signed in, because a review carries a name on a public page
    Route::middleware('auth')->group(function () {
        Route::post('/reviews', [ReviewController::class, 'store'])
            ->middleware('throttle:5,60')->name('reviews.store');
        Route::post('/reviews/{review}/vote', [ReviewController::class, 'vote'])
            ->middleware('throttle:60,1')->name('reviews.vote');
    });

    // Content
    Route::post('/contact', [PageController::class, 'storeContact'])
        ->middleware('throttle:5,60')->name('contact.store');
    Route::post('/newsletter', [PageController::class, 'subscribe'])
        ->middleware('throttle:5,60')->name('newsletter.store');
    Route::post('/news/{post}/comment', [BlogController::class, 'comment'])
        ->middleware(['auth', 'throttle:10,60'])->name('blogs.comment');

    // Comparison
    Route::post('/compare/add', [CompareController::class, 'add'])->name('compare.add');
    Route::post('/compare/remove', [CompareController::class, 'remove'])->name('compare.remove');
    Route::post('/compare/clear', [CompareController::class, 'clear'])->name('compare.clear');
});
