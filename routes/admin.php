<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PriceController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RoleController;
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
