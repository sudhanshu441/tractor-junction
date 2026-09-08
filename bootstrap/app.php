<?php

use App\Http\Middleware\EnsureUserType;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackPageView;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/account.php'));
            Route::middleware('web')->group(base_path('routes/dealer.php'));
            Route::middleware('web')->group(base_path('routes/admin.php'));
            Route::middleware('web')->group(base_path('routes/ajax.php'));

            /*
             | The public website, registered once per locale. Hindi lives under
             | /hi with an "hi." name prefix; LocalizedUrlGenerator points a
             | route() call at the right one, so views need no locale awareness.
             | English is registered last so its bare names win.
             */
            Route::middleware('web')->prefix('hi')->name('hi.')->group(base_path('routes/public.php'));
            Route::middleware('web')->group(base_path('routes/public.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            SetLocale::class,
            HandleRedirects::class,
            TrackPageView::class,
        ]);

        $middleware->alias([
            'user.type' => EnsureUserType::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('ajax/*') || $request->expectsJson(),
        );
    })->create();
