<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale: URL prefix → authenticated preference → cookie
 * → Accept-Language → app default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('kj.locales', ['en' => 'English']));

        $locale = $request->segment(1);

        if (! in_array($locale, $supported, true)) {
            $locale = $request->user()?->locale
                ?: $request->cookie('kj_locale')
                ?: $request->getPreferredLanguage($supported)
                ?: config('app.locale');
        }

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
