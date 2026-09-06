<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a page view for analytics. Deliberately fire-and-forget: view
 * tracking must never slow down or break a page render.
 */
class TrackPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return;
        }

        // Phase 2 attaches the viewable model; phase 1 only wires the hook.
    }
}
