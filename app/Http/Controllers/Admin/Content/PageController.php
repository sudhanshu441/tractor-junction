<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Static pages. These claim a bare root slug, so the reserved-word check here
 * is what stops an editor publishing /tractors and shadowing the catalogue.
 */
class PageController extends Controller
{
    /** Slugs the routing already owns. */
    public const RESERVED = [
        'admin', 'dealer', 'account', 'ajax', 'api', 'login', 'logout', 'search', 'sell',
        'used', 'compare', 'dealers', 'loan', 'news', 'videos', 'offers', 'faq', 'contact',
        'tractors', 'implements', 'harvesters', 'tractor-tyres', 'farm-tools', 'brands',
        'documents', 'storage', 'robots.txt', 'sitemap.xml', 'tractor-insurance', 'hi', 'up',
    ];

    public function index(): View
    {
        return view('admin.content.pages.index', [
            'pages' => Page::orderBy('sort_order')->orderBy('title')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('admin.content.pages.form', [
            'page' => new Page(['is_active' => true]),
            'seo' => null,
        ]);
    }

    public function edit(Page $page): View
    {
        return view('admin.content.pages.form', [
            'page' => $page,
            'seo' => $page->seo()->where('locale', 'en')->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = Page::create($this->payload($request, new Page));

        $this->saveSeo($page, $request);
        activity()->performedOn($page)->causedBy($request->user())->log('Created page');

        return redirect()->route('admin.pages.edit', $page)->with('success', __('Page created.'));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->payload($request, $page));

        $this->saveSeo($page, $request);
        activity()->performedOn($page)->causedBy($request->user())->log('Updated page');

        return back()->with('success', __('Page saved.'));
    }

    public function destroy(Request $request, Page $page): RedirectResponse
    {
        $page->delete();

        activity()->performedOn($page)->causedBy($request->user())->log('Deleted page');

        return redirect()->route('admin.pages.index')->with('success', __('Page deleted.'));
    }

    private function payload(Request $request, Page $page): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220'],
            'content' => ['nullable', 'string'],
            'template' => ['required', 'string', 'max:40'],
            'show_in_footer' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($data['slug'] ?: $data['title']);

        if (in_array($slug, self::RESERVED, true)) {
            abort(422, __('That address is already used by the website. Choose another.'));
        }

        return [
            ...$data,
            'slug' => $this->uniqueSlug($slug, $page),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'show_in_footer' => (bool) ($data['show_in_footer'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    private function uniqueSlug(string $slug, Page $page): string
    {
        $base = $slug;
        $i = 1;

        while (Page::where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    private function saveSeo(Page $page, Request $request): void
    {
        $data = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:400'],
            'robots' => ['nullable', 'string', 'max:40'],
        ]);

        if (blank($data['meta_title']) && blank($data['meta_description']) && blank($data['robots'])) {
            return;
        }

        $page->seo()->updateOrCreate(['locale' => 'en'], [
            'meta_title' => $data['meta_title'] ?: null,
            'meta_description' => $data['meta_description'] ?: null,
            'robots' => $data['robots'] ?: 'index,follow',
        ]);
    }
}
