<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response headers that close off whole classes of attack cheaply.
 *
 * The CSP is deliberately not `unsafe-eval` and lists only the hosts this site
 * genuinely talks to: its own origin, the YouTube embed, and — when configured
 * — Google Tag Manager and Razorpay. Everything else is vendored locally, which
 * is what makes a policy this tight possible at all.
 *
 * `unsafe-inline` for scripts is still required: the admin screens and the
 * enhancement layer use inline handlers. Removing it means nonces on every
 * inline block, which is a worthwhile follow-up but not a silent one.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(self), camera=(), microphone=(), payment=(self), interest-cohort=()',
        );

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->policy());
        }

        return $response;
    }

    private function policy(): string
    {
        $scripts = ["'self'", "'unsafe-inline'"];
        $frames = ['https://www.youtube-nocookie.com', 'https://www.youtube.com'];
        $connect = ["'self'"];

        if (config('kj.payments.driver') === 'razorpay') {
            $scripts[] = 'https://checkout.razorpay.com';
            $frames[] = 'https://api.razorpay.com';
            $connect[] = 'https://lumberjack.razorpay.com';
        }

        if (Setting::get('seo.gtm_id') || Setting::get('seo.google_analytics_id')) {
            $scripts[] = 'https://www.googletagmanager.com';
            $connect[] = 'https://www.google-analytics.com';
            $connect[] = 'https://region1.google-analytics.com';
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scripts),
            "style-src 'self' 'unsafe-inline'",
            // Fonts are self-hosted, so no third-party font host is needed.
            "font-src 'self'",
            "img-src 'self' data: https://i.ytimg.com https://www.googletagmanager.com",
            'connect-src '.implode(' ', $connect),
            'frame-src '.implode(' ', $frames),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}
