<?php

namespace App\Http\Controllers\Web;

use App\Domain\Content\Services\ContentService;
use App\Domain\Seo\Services\SeoService;
use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackPageView;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * News and guides. Fully server-rendered — this is the section that earns
 * long-tail search traffic, so it has to work with JavaScript off.
 */
class BlogController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly SeoService $seo,
    ) {}

    public function index(Request $request, ?string $categorySlug = null): View
    {
        $category = $categorySlug
            ? BlogCategory::active()->where('slug', $categorySlug)->firstOrFail()
            : null;

        $posts = Blog::published()
            ->with(['category', 'author'])
            ->ofType($request->query('type'))
            ->when($category, fn ($q) => $q->where('blog_category_id', $category->id))
            ->when($request->query('tag'), fn ($q, $tag) => $q->whereHas('tags', fn ($t) => $t->where('slug', $tag)))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('web.content.blogs', [
            'posts' => $posts,
            'category' => $category,
            'categories' => BlogCategory::active()->orderBy('sort_order')->get(),
            'featured' => $category ? null : Blog::published()->where('is_featured', true)
                ->with('category')->latest('published_at')->first(),
            'tags' => Tag::orderBy('name')->limit(20)->get(),
            'seo' => $this->seo->for($category, 'page', [
                'title' => $category
                    ? __(':name — Tractor News & Guides', ['name' => $category->name])
                    : __('Tractor News, Reviews & Farming Guides'),
                'description' => __('Tractor launches, price updates, government schemes and buying guides for Indian farmers.'),
            ]),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $post = Blog::published()
            ->with(['category', 'author', 'tags', 'seo'])
            ->where('slug', $slug)
            ->firstOrFail();

        $post->incrementQuietly('view_count');
        TrackPageView::attribute($post);

        return view('web.content.blog', [
            'post' => $post,
            'related' => $this->content->related($post),
            'comments' => $post->comments()->approved()->with('user')->whereNull('parent_id')
                ->with('replies', fn ($q) => $q->where('status', 'approved')->with('user'))
                ->latest()->get(),
            'seo' => $this->seo->for($post, 'blog', [], [
                ':title' => $post->title,
                ':excerpt' => $post->excerpt ?? $this->content->excerptFrom($post->content),
            ]),
        ]);
    }

    /** Comments are moderated: nothing a stranger writes appears unread. */
    public function comment(Request $request, Blog $post): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:5', 'max:2000'],
            'parent_id' => ['nullable', 'exists:blog_comments,id'],
        ]);

        BlogComment::create([
            'blog_id' => $post->id,
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => __('Thank you. Your comment appears once our team has read it.'),
        ]);
    }
}
