<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a page view for the admin dashboards.
 *
 * Fire-and-forget by design: this runs after the response is sent, swallows its
 * own failures, and skips bots. Analytics must never slow down or break a page
 * a farmer is trying to read.
 */
class TrackPageView
{
    /** What a controller viewed, set with `view()->share()` or the helper below. */
    private static array $subject = [];

    /** Called from a controller to attribute the view to a model. */
    public static function attribute(?object $model): void
    {
        self::$subject = $model
            ? ['type' => $model->getMorphClass(), 'id' => $model->getKey()]
            : [];
    }

    private const BOTS = ['bot', 'crawler', 'spider', 'slurp', 'curl', 'wget', 'headless', 'lighthouse', 'python'];

    public function handle(Request $request, Closure $next): Response
    {
        self::$subject = [];

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return;
        }

        if ($request->is('admin/*', 'dealer/*', 'account/*', 'ajax/*', 'api/*')) {
            return;
        }

        if ($this->isBot((string) $request->userAgent())) {
            return;
        }

        try {
            PageView::create([
                'viewable_type' => self::$subject['type'] ?? null,
                'viewable_id' => self::$subject['id'] ?? null,
                'user_id' => $request->user()?->id,
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'ip' => $request->ip(),
                'referer' => mb_substr((string) $request->headers->get('referer'), 0, 255) ?: null,
                'utm_source' => mb_substr((string) $request->query('utm_source'), 0, 60) ?: null,
                'viewed_on' => today(),
            ]);
        } catch (\Throwable) {
            // A dashboard number is never worth a 500 on a public page.
        }
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
