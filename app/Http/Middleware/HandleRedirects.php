<?php

namespace App\Http\Middleware;

use App\Models\NotFoundLog;
use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

/**
 * Editor-managed redirects, and a log of what 404s.
 *
 * A marketplace changes its URL shapes over the years — a slug is corrected, a
 * category is renamed — and every one of those changes strands inbound links
 * and rankings unless something catches them. The 404 log is what tells the
 * team which redirect to write next.
 */
class HandleRedirects
{
    /** Redirects are read on every request, so the whole (small) table is cached. */
    private const CACHE_KEY = 'kj.redirects';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $path = '/'.trim($request->path(), '/');
        $target = $this->map()[$path] ?? null;

        if ($target) {
            // Counted after the response is sent, so a redirect never waits on a write.
            defer(fn () => Redirect::where('from_url', $path)->incrementQuietly('hit_count'));

            return redirect($target['to'], $target['status']);
        }

        return $next($request);
    }

    /**
     * 404 logging belongs here, not in handle(): most 404s on this site come
     * from firstOrFail() inside a controller, and that exception is turned into
     * a response by the kernel's handler — above every route middleware. Only
     * terminate() sees the status the visitor actually got.
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($request->isMethod('GET') && $response->getStatusCode() === 404) {
            $this->log($request);
        }
    }

    /**
     * Plain arrays only: a cached Eloquent collection comes back as an
     * incomplete class once the store actually serialises.
     *
     * @return array<string, array{to: string, status: int}>
     */
    private function map(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            return Redirect::where('is_active', true)
                ->get(['from_url', 'to_url', 'status_code'])
                ->mapWithKeys(fn (Redirect $r) => [
                    '/'.trim($r->from_url, '/') => ['to' => $r->to_url, 'status' => (int) $r->status_code],
                ])
                ->all();
        });
    }

    /** One row per URL with a hit count, so a crawler cannot flood the table. */
    private function log(Request $request): void
    {
        $url = mb_substr($request->getRequestUri(), 0, 255);

        try {
            $existing = NotFoundLog::where('url', $url)->first();

            if ($existing) {
                $existing->forceFill([
                    'hit_count' => $existing->hit_count + 1,
                    'last_seen_at' => now(),
                ])->saveQuietly();

                return;
            }

            NotFoundLog::create([
                'url' => $url,
                'referer' => mb_substr((string) $request->headers->get('referer'), 0, 255) ?: null,
                'hit_count' => 1,
                'last_seen_at' => now(),
            ]);
        } catch (\Throwable) {
            // Logging a broken link must never itself break the error page.
        }
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
