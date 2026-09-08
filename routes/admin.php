<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\Content\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\Content\InboxController;
use App\Http\Controllers\Admin\Content\MasterController;
use App\Http\Controllers\Admin\Content\MenuController;
use App\Http\Controllers\Admin\Content\OfferController as AdminOfferController;
use App\Http\Controllers\Admin\Content\PageController as AdminPageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\InspectionController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\PriceController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\Seo\SeoController;
use App\Http\Controllers\Admin\SpecController;
use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')
    ->middleware(['auth', 'user.type:staff'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ----- Catalogue -----
        Route::middleware('permission:brands.view')->group(function () {
            Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
            Route::post('/brands/data', [BrandController::class, 'data'])->name('brands.data');
        });
        Route::middleware('permission:brands.create')->group(function () {
            Route::get('/brands/create', [BrandController::class, 'create'])->name('brands.create');
            Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
        });
        Route::middleware('permission:brands.edit')->group(function () {
            Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
            Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
            Route::post('/brands/{brand}/toggle', [BrandController::class, 'toggle'])->name('brands.toggle');
        });
        Route::middleware('permission:brands.delete')
            ->delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

        Route::middleware('permission:categories.view')
            ->get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::middleware('permission:categories.create')->group(function () {
            Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        });
        Route::middleware('permission:categories.edit')->group(function () {
            Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
            Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::post('/categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');
        });
        Route::middleware('permission:categories.delete')
            ->delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::middleware('permission:specs.view')
            ->get('/specs', [SpecController::class, 'index'])->name('specs.index');
        Route::middleware('permission:specs.create')->group(function () {
            Route::post('/specs/groups', [SpecController::class, 'storeGroup'])->name('specs.groups.store');
            Route::post('/specs/attributes', [SpecController::class, 'storeAttribute'])->name('specs.attributes.store');
        });
        Route::middleware('permission:specs.edit')
            ->put('/specs/attributes/{attribute}', [SpecController::class, 'updateAttribute'])->name('specs.attributes.update');
        Route::middleware('permission:specs.delete')
            ->delete('/specs/attributes/{attribute}', [SpecController::class, 'destroyAttribute'])->name('specs.attributes.destroy');

        Route::middleware('permission:products.view')->group(function () {
            Route::get('/products', [ProductController::class, 'index'])->name('products.index');
            Route::post('/products/data', [ProductController::class, 'data'])->name('products.data');
        });
        Route::middleware('permission:products.create')->group(function () {
            Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
            Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        });
        Route::middleware('permission:products.edit')->group(function () {
            Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::post('/products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
            Route::delete('/products/{product}/media/{media}', [ProductController::class, 'deleteImage'])->name('products.media.destroy');
            Route::post('/products/{product}/media/{media}/primary', [ProductController::class, 'primaryImage'])->name('products.media.primary');
        });
        Route::middleware('permission:products.delete')
            ->delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::middleware('permission:prices.view')
            ->get('/products/{product}/prices', [PriceController::class, 'index'])->name('prices.index');
        Route::middleware('permission:prices.edit')->group(function () {
            Route::post('/products/{product}/prices', [PriceController::class, 'store'])->name('prices.store');
            Route::delete('/products/{product}/prices/{price}', [PriceController::class, 'destroy'])->name('prices.destroy');
            Route::post('/prices/import', [PriceController::class, 'import'])->name('prices.import');
        });

        // ----- Used marketplace moderation -----
        Route::middleware('permission:listings.view')->group(function () {
            Route::get('/used-listings', [ModerationController::class, 'index'])->name('listings.index');
            Route::post('/used-listings/data', [ModerationController::class, 'data'])->name('listings.data');
            Route::get('/used-listings/moderation', [ModerationController::class, 'queue'])->name('listings.queue');
            Route::get('/used-listings/reports', [ModerationController::class, 'reports'])->name('listings.reports');
        });
        Route::middleware('permission:listings.approve')->group(function () {
            Route::post('/used-listings/{listing}/decide', [ModerationController::class, 'decide'])->name('listings.decide');
            Route::post('/used-listings/bulk-approve', [ModerationController::class, 'bulkApprove'])->name('listings.bulk-approve');
            Route::post('/reports/{report}/resolve', [ModerationController::class, 'resolveReport'])->name('listings.reports.resolve');
        });

        // ----- Leads -----
        Route::middleware('permission:leads.view')->group(function () {
            Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
            Route::post('/leads/data', [LeadController::class, 'data'])->name('leads.data');
            Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        });
        Route::middleware('permission:leads.edit')->group(function () {
            Route::post('/leads/{lead}/status', [LeadController::class, 'changeStatus'])->name('leads.status');
            Route::post('/leads/{lead}/note', [LeadController::class, 'addNote'])->name('leads.note');
        });
        Route::middleware('permission:leads.assign')->group(function () {
            Route::post('/leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
            Route::post('/leads/{lead}/merge', [LeadController::class, 'merge'])->name('leads.merge');
            Route::post('/leads/bulk-route', [LeadController::class, 'bulkRoute'])->name('leads.bulk-route');
        });
        Route::middleware('permission:leads.export')
            ->get('/leads-export', [LeadController::class, 'export'])->name('leads.export');

        // ----- Dealers -----
        Route::middleware('permission:dealers.view')->group(function () {
            Route::get('/dealers', [DealerController::class, 'index'])->name('dealers.index');
            Route::post('/dealers/data', [DealerController::class, 'data'])->name('dealers.data');
            Route::get('/dealers/{dealer}', [DealerController::class, 'show'])->name('dealers.show');
        });
        Route::middleware('permission:dealers.approve')
            ->post('/dealers/{dealer}/verify', [DealerController::class, 'verify'])->name('dealers.verify');

        // ----- Loan applications -----
        Route::middleware('permission:loans.view')->group(function () {
            Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
            Route::post('/loans/data', [LoanController::class, 'data'])->name('loans.data');
            Route::get('/loans/{application}', [LoanController::class, 'show'])->name('loans.show');
        });
        Route::middleware('permission:loans.edit')->group(function () {
            Route::post('/loans/{application}/status', [LoanController::class, 'changeStatus'])->name('loans.status');
            Route::post('/loans/{application}/lenders', [LoanController::class, 'sendToLenders'])->name('loans.lenders');
            Route::post('/loans/{application}/lender-decision', [LoanController::class, 'lenderDecision'])->name('loans.lender-decision');
            Route::post('/loan-documents/{document}/verify', [LoanController::class, 'verifyDocument'])->name('loans.documents.verify');
        });

        // ----- Inspections -----
        Route::middleware('permission:inspections.view')->group(function () {
            Route::get('/inspections', [InspectionController::class, 'index'])->name('inspections.index');
            Route::get('/inspections/{inspection}/report', [InspectionController::class, 'form'])->name('inspections.form');
            Route::post('/inspections/{inspection}/complete', [InspectionController::class, 'complete'])->name('inspections.complete');
        });
        Route::middleware('permission:inspections.create')
            ->post('/used-listings/{listing}/inspect', [InspectionController::class, 'request'])->name('inspections.request');
        Route::middleware('permission:inspections.assign')
            ->post('/inspections/{inspection}/schedule', [InspectionController::class, 'schedule'])->name('inspections.schedule');
        Route::middleware('permission:inspections.approve')
            ->post('/inspections/{inspection}/approve', [InspectionController::class, 'approve'])->name('inspections.approve');

        // ----- Reviews -----
        Route::middleware('permission:reviews.view')
            ->get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::middleware('permission:reviews.approve')
            ->post('/reviews/{review}/moderate', [ReviewController::class, 'moderate'])->name('reviews.moderate');

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

        /*
        | Content management. Posts, pages and offers each carry real behaviour;
        | the five small masters share one config-driven controller.
        */
        Route::middleware('permission:blogs.view')->group(function () {
            Route::get('/blogs', [AdminBlogController::class, 'index'])->name('blogs.index');
            Route::post('/blogs/data', [AdminBlogController::class, 'data'])->name('blogs.data');
            Route::get('/blogs/create', [AdminBlogController::class, 'create'])->name('blogs.create');
            Route::post('/blogs', [AdminBlogController::class, 'store'])->name('blogs.store');
            Route::get('/blogs/{post}/edit', [AdminBlogController::class, 'edit'])->name('blogs.edit');
            Route::put('/blogs/{post}', [AdminBlogController::class, 'update'])->name('blogs.update');
            Route::delete('/blogs/{post}', [AdminBlogController::class, 'destroy'])->name('blogs.destroy');

            Route::get('/comments', [InboxController::class, 'comments'])->name('comments.index');
            Route::post('/comments/{comment}/moderate', [InboxController::class, 'moderateComment'])->name('comments.moderate');
        });

        Route::middleware('permission:pages.view')->group(function () {
            Route::get('/pages', [AdminPageController::class, 'index'])->name('pages.index');
            Route::get('/pages/create', [AdminPageController::class, 'create'])->name('pages.create');
            Route::post('/pages', [AdminPageController::class, 'store'])->name('pages.store');
            Route::get('/pages/{page}/edit', [AdminPageController::class, 'edit'])->name('pages.edit');
            Route::put('/pages/{page}', [AdminPageController::class, 'update'])->name('pages.update');
            Route::delete('/pages/{page}', [AdminPageController::class, 'destroy'])->name('pages.destroy');
        });

        Route::middleware('permission:offers.view')->group(function () {
            Route::get('/offers', [AdminOfferController::class, 'index'])->name('offers.index');
            Route::get('/offers/create', [AdminOfferController::class, 'create'])->name('offers.create');
            Route::post('/offers', [AdminOfferController::class, 'store'])->name('offers.store');
            Route::get('/offers/{offer}/edit', [AdminOfferController::class, 'edit'])->name('offers.edit');
            Route::put('/offers/{offer}', [AdminOfferController::class, 'update'])->name('offers.update');
            Route::delete('/offers/{offer}', [AdminOfferController::class, 'destroy'])->name('offers.destroy');
        });

        Route::middleware('permission:menus.view')->group(function () {
            Route::get('/menus/{slug?}', [MenuController::class, 'index'])->name('menus.index');
            Route::post('/menus/{menu}/items', [MenuController::class, 'storeItem'])->name('menus.items.store');
            Route::put('/menu-items/{item}', [MenuController::class, 'updateItem'])->name('menus.items.update');
            Route::delete('/menu-items/{item}', [MenuController::class, 'destroyItem'])->name('menus.items.destroy');
            Route::post('/menus/{menu}/reorder', [MenuController::class, 'reorder'])->name('menus.reorder');
        });

        // The small masters: faqs | banners | testimonials | blog-categories | videos
        Route::middleware('permission:faqs.view')->group(function () {
            Route::get('/content/{resource}', [MasterController::class, 'index'])->name('content.index');
            Route::get('/content/{resource}/create', [MasterController::class, 'create'])->name('content.create');
            Route::post('/content/{resource}', [MasterController::class, 'store'])->name('content.store');
            Route::get('/content/{resource}/{id}/edit', [MasterController::class, 'edit'])->name('content.edit');
            Route::put('/content/{resource}/{id}', [MasterController::class, 'update'])->name('content.update');
            Route::delete('/content/{resource}/{id}', [MasterController::class, 'destroy'])->name('content.destroy');
        });

        // Contact inbox and newsletter list
        Route::middleware('permission:contact.view')->group(function () {
            Route::get('/messages', [InboxController::class, 'messages'])->name('messages.index');
            Route::post('/messages/{message}', [InboxController::class, 'updateMessage'])->name('messages.update');
            Route::get('/subscribers', [InboxController::class, 'subscribers'])->name('subscribers.index');
            Route::get('/subscribers/export', [InboxController::class, 'exportSubscribers'])->name('subscribers.export');
        });

        /*
        | SEO desk: redirects, the 404 log that says which redirect to write
        | next, sitemap rebuilds and the editable interface strings.
        */
        Route::middleware('permission:seo.view')->group(function () {
            Route::get('/seo/redirects', [SeoController::class, 'redirects'])->name('seo.redirects');
            Route::get('/seo/translations', [SeoController::class, 'translations'])->name('seo.translations');
        });
        Route::middleware('permission:seo.edit')->group(function () {
            Route::post('/seo/redirects', [SeoController::class, 'storeRedirect'])->name('seo.redirects.store');
            Route::delete('/seo/redirects/{redirect}', [SeoController::class, 'destroyRedirect'])->name('seo.redirects.destroy');
            Route::post('/seo/not-found/{log}', [SeoController::class, 'resolveNotFound'])->name('seo.notfound.resolve');
            Route::delete('/seo/not-found', [SeoController::class, 'clearNotFound'])->name('seo.notfound.clear');
            Route::post('/seo/sitemap', [SeoController::class, 'sitemap'])->name('seo.sitemap');
            Route::post('/seo/translations', [SeoController::class, 'saveTranslation'])->name('seo.translations.save');
        });

        // Reports
        Route::middleware('permission:reports.view')->group(function () {
            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        });
        Route::middleware('permission:reports.export')
            ->get('/reports/export/{dataset}', [ReportController::class, 'export'])->name('reports.export');

        // Roles & permissions
        Route::middleware('permission:roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        });
        Route::middleware('permission:roles.edit')
            ->put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
