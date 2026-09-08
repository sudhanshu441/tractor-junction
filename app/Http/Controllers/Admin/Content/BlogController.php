<?php

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Content\Services\ContentService;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private readonly ContentService $content) {}

    public function index(): View
    {
        return view('admin.content.blogs.index', [
            'counts' => [
                'published' => Blog::where('status', 'published')->count(),
                'draft' => Blog::where('status', 'draft')->count(),
                'scheduled' => Blog::where('status', 'scheduled')->count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Blog::with('category', 'author');

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $total = Blog::count();
        $filtered = (clone $query)->count();

        $rows = $query->latest('id')
            ->skip((int) $request->input('start', 0))
            ->take(min(100, (int) $request->input('length', 25)))
            ->get()
            ->map(fn (Blog $b) => [
                'title' => $b->title,
                'type' => $b->type,
                'category' => $b->category?->name ?? '—',
                'author' => $b->author?->name ?? '—',
                'status' => $b->status,
                'views' => number_format($b->view_count),
                'published' => $b->published_at?->format('d M Y') ?? '—',
                'edit_url' => route('admin.blogs.edit', $b),
                'view_url' => $b->isVisible() ? route('blogs.show', $b->slug) : null,
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
        ]);
    }

    public function create(): View
    {
        return $this->form(new Blog(['status' => 'draft', 'type' => 'blog']));
    }

    public function edit(Blog $post): View
    {
        return $this->form($post->load('tags', 'seo'));
    }

    private function form(Blog $post): View
    {
        return view('admin.content.blogs.form', [
            'post' => $post,
            'categories' => BlogCategory::active()->orderBy('sort_order')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'seo' => $post->exists ? $post->seo->firstWhere('locale', 'en') : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $post = Blog::create($this->payload($request, new Blog));

        $this->syncTags($post, $request);
        $this->saveSeo($post, $request);

        activity()->performedOn($post)->causedBy($request->user())->log('Created post');

        return redirect()->route('admin.blogs.edit', $post)->with('success', __('Post created.'));
    }

    public function update(Request $request, Blog $post): RedirectResponse
    {
        $post->update($this->payload($request, $post));

        $this->syncTags($post, $request);
        $this->saveSeo($post, $request);

        activity()->performedOn($post)->causedBy($request->user())->log('Updated post');

        return back()->with('success', __('Post saved.'));
    }

    public function destroy(Request $request, Blog $post): RedirectResponse
    {
        $post->delete();

        activity()->performedOn($post)->causedBy($request->user())->log('Deleted post');

        return redirect()->route('admin.blogs.index')->with('success', __('Post deleted.'));
    }

    private function payload(Request $request, Blog $post): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220'],
            'type' => ['required', 'in:blog,news,guide,press'],
            'blog_category_id' => ['nullable', 'exists:blog_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'is_featured' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,scheduled,published,archived'],
            'published_at' => ['nullable', 'date'],
        ]);

        $slug = Str::slug($data['slug'] ?: $data['title']);

        // A published post with no date would vanish from the public scope.
        $publishedAt = $data['published_at'] ?? null;

        if ($data['status'] === 'published' && ! $publishedAt) {
            $publishedAt = $post->published_at ?? now();
        }

        $payload = [
            ...$data,
            'slug' => $this->uniqueSlug($slug, $post),
            'author_id' => $post->author_id ?? $request->user()->id,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'published_at' => $publishedAt,
            'excerpt' => $data['excerpt'] ?: $this->content->excerptFrom($data['content'] ?? null),
            'reading_minutes' => $this->content->readingMinutes($data['content'] ?? null),
        ];

        if ($request->hasFile('cover_image')) {
            $payload['cover_image'] = $request->file('cover_image')->store('blogs', 'public');
        } else {
            unset($payload['cover_image']);
        }

        return $payload;
    }

    private function uniqueSlug(string $slug, Blog $post): string
    {
        $base = $slug;
        $i = 1;

        while (Blog::withTrashed()->where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    /** Tags are typed as free text; new ones are created rather than rejected. */
    private function syncTags(Blog $post, Request $request): void
    {
        $names = collect(explode(',', (string) $request->input('tags')))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->take(12);

        $ids = $names->map(fn ($name) => Tag::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name],
        )->id);

        $post->tags()->sync($ids);
    }

    private function saveSeo(Blog $post, Request $request): void
    {
        $data = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:400'],
            'robots' => ['nullable', 'string', 'max:40'],
        ]);

        if (blank($data['meta_title']) && blank($data['meta_description'])) {
            return;
        }

        $post->seo()->updateOrCreate(
            ['locale' => 'en'],
            [
                'meta_title' => $data['meta_title'] ?: null,
                'meta_description' => $data['meta_description'] ?: null,
                'robots' => $data['robots'] ?: 'index,follow',
            ],
        );
    }
}
