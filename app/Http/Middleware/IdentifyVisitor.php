<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives each browser a stable, random id so a journey survives the session.
 *
 * It identifies a browser, not a person: a random UUID, no fingerprinting, and
 * nothing derived from the device. A name or a number is only ever known
 * because somebody typed it into a form.
 *
 * The cookie is not set for a crawler — otherwise the visitor report fills up
 * with Googlebot.
 */
class IdentifyVisitor
{
    public const COOKIE = 'kj_visitor';

    public const LIFETIME_DAYS = 180;

    private const BOTS = ['bot', 'crawler', 'spider', 'slurp', 'curl', 'wget', 'headless', 'lighthouse', 'python'];

    public function handle(Request $request, Closure $next): Response
    {
        $existing = $request->cookie(self::COOKIE);
        $id = $this->valid($existing) ? $existing : (string) Str::uuid();

        // Available to the rest of the request without re-reading the cookie.
        $request->attributes->set('visitor_id', $id);

        $response = $next($request);

        if ($existing !== $id && ! $this->isBot((string) $request->userAgent())) {
            $response->headers->setCookie(new Cookie(
                name: self::COOKIE,
                value: $id,
                expire: now()->addDays(self::LIFETIME_DAYS)->getTimestamp(),
                path: '/',
                secure: $request->secure(),
                httpOnly: true,
                sameSite: Cookie::SAMESITE_LAX,
            ));
        }

        return $response;
    }

    /** A cookie a visitor edited by hand must not reach a query. */
    private function valid(mixed $value): bool
    {
        return is_string($value) && Str::isUuid($value);
    }

    private function isBot(string $agent): bool
    {
        $agent = strtolower($agent);

        foreach (self::BOTS as $needle) {
            if (str_contains($agent, $needle)) {
                return true;
            }
        }

        return $agent === '';
    }
}
