<?php

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Services\SitemapBuilder;
use App\Http\Controllers\Controller;
use App\Http\Middleware\HandleRedirects;
use App\Models\LanguageLine;
use App\Models\NotFoundLog;
use App\Models\Redirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * The SEO desk: redirects, the 404 log that tells you which redirect to write
 * next, sitemap rebuilds, and the editable interface strings.
 */
class SeoController extends Controller
{
    public function redirects(Request $request): View
    {
        return view('admin.seo.redirects', [
            'redirects' => Redirect::orderByDesc('hit_count')->paginate(30),
            'notFound' => NotFoundLog::orderByDesc('hit_count')->limit(25)->get(),
            'counts' => [
                'redirects' => Redirect::where('is_active', true)->count(),
                'not_found' => NotFoundLog::count(),
                'hits' => (int) NotFoundLog::sum('hit_count'),
            ],
        ]);
    }

    public function storeRedirect(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_url' => ['required', 'string', 'max:255'],
            'to_url' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'in:301,302'],
        ]);

        $from = '/'.trim(parse_url($data['from_url'], PHP_URL_PATH) ?: $data['from_url'], '/');
        $to = $data['to_url'];

        if ($from === '/'.trim(parse_url($to, PHP_URL_PATH) ?: $to, '/')) {
            return back()->with('error', __('A redirect cannot point at itself.'));
        }

        // A chain is a redirect to a URL that itself redirects: crawlers follow
        // a limited number, so the second hop is resolved now instead.
        $chained = Redirect::where('from_url', '/'.trim($to, '/'))->where('is_active', true)->first();

        Redirect::updateOrCreate(['from_url' => $from], [
            'to_url' => $chained?->to_url ?? $to,
            'status_code' => (int) $data['status_code'],
            'is_active' => true,
        ]);

        HandleRedirects::flushCache();

        return back()->with('success', $chained
            ? __('Redirect saved, pointed straight at :url to avoid a chain.', ['url' => $chained->to_url])
            : __('Redirect saved.'));
    }

    public function destroyRedirect(Redirect $redirect): RedirectResponse
    {
        $redirect->delete();
        HandleRedirects::flushCache();

        return back()->with('success', __('Redirect removed.'));
    }

    /** Turns a logged 404 into a redirect in one click. */
    public function resolveNotFound(Request $request, NotFoundLog $log): RedirectResponse
    {
        $data = $request->validate(['to_url' => ['required', 'string', 'max:255']]);

        Redirect::updateOrCreate(
            ['from_url' => '/'.trim(parse_url($log->url, PHP_URL_PATH) ?: $log->url, '/')],
            ['to_url' => $data['to_url'], 'status_code' => 301, 'is_active' => true],
        );

        $log->delete();
        HandleRedirects::flushCache();

        return back()->with('success', __('Redirect created from that 404.'));
    }

    public function clearNotFound(): RedirectResponse
    {
        NotFoundLog::query()->delete();

        return back()->with('success', __('404 log cleared.'));
    }

    public function sitemap(Request $request, SitemapBuilder $builder): JsonResponse
    {
        $files = $builder->build();

        activity()->causedBy($request->user())->log('Rebuilt sitemaps');

        return response()->json([
            'status' => 'ok',
            'message' => __('Rebuilt :n sitemap files.', ['n' => count($files)]),
            'data' => ['files' => $files, 'url' => route('sitemap.index')],
        ]);
    }

    /**
     * The interface strings, with the file value beside the override so an
     * editor can see what they are changing rather than guessing.
     */
    public function translations(Request $request): View
    {
        $locale = $request->query('locale', 'hi');
        abort_unless(array_key_exists($locale, config('kj.locales', [])), 404);

        $file = lang_path($locale.'.json');
        $strings = File::exists($file)
            ? json_decode(File::get($file), true) ?: []
            : [];

        $overrides = LanguageLine::where('locale', $locale)->pluck('value', 'key')->all();

        // An override for a string no longer in the file is still shown, so it
        // can be removed rather than lingering invisibly.
        $keys = array_unique([...array_keys($strings), ...array_keys($overrides)]);
        sort($keys);

        if ($search = trim((string) $request->query('q'))) {
            $keys = array_values(array_filter($keys, fn ($key) => str_contains(
                mb_strtolower($key.' '.($strings[$key] ?? '').' '.($overrides[$key] ?? '')),
                mb_strtolower($search),
            )));
        }

        return view('admin.seo.translations', [
            'locale' => $locale,
            'locales' => config('kj.locales'),
            'strings' => $strings,
            'overrides' => $overrides,
            'keys' => array_slice($keys, 0, 400),
            'total' => count($keys),
            'search' => $search,
        ]);
    }

    public function saveTranslation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', 'max:5'],
            'key' => ['required', 'string', 'max:500'],
            'value' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless(array_key_exists($data['locale'], config('kj.locales', [])), 422);

        if (blank($data['value'])) {
            LanguageLine::where('locale', $data['locale'])->where('key', $data['key'])->delete();
            LanguageLine::flush($data['locale']);

            return response()->json(['status' => 'ok', 'message' => __('Override removed — the file value is used again.')]);
        }

        LanguageLine::updateOrCreate(
            ['locale' => $data['locale'], 'group' => '*', 'key' => $data['key']],
            ['value' => $data['value']],
        );

        return response()->json(['status' => 'ok', 'message' => __('Saved.')]);
    }
}
