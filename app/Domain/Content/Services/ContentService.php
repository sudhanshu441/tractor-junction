<?php

namespace App\Domain\Content\Services;

use App\Models\Banner;
use App\Models\Blog;
use App\Models\Faq;
use App\Models\Menu;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Reads for the editor-managed furniture of the site: menus, banners, FAQs,
 * testimonials.
 *
 * Everything here is cached and everything returns **plain arrays** — a
 * serialised Eloquent collection comes back as an incomplete class on a warm
 * cache, which has cost this project two production-shaped bugs already.
 */
class ContentService
{
    public const CACHE_TTL_MINUTES = 60;

    /** @return array<int, array<string, mixed>> */
    public function menu(string $slug): array
    {
        return Cache::remember("kj.menu.{$slug}", now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($slug) {
            $menu = Menu::where('slug', $slug)->with(['rootItems.children'])->first();

            if (! $menu) {
                return [];
            }

            return $menu->rootItems
                ->where('is_active', true)
                ->map(fn ($item) => [
                    'label' => $item->label,
                    'url' => $item->url,
                    'icon' => $item->icon,
                    'children' => $item->children->map(fn ($child) => [
                        'label' => $child->label,
                        'url' => $child->url,
                        'icon' => $child->icon,
                    ])->values()->all(),
                ])
                ->values()
                ->all();
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function banners(string $position, string $device = 'all'): array
    {
        $all = Cache::remember('kj.banners', now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            return Banner::where('is_active', true)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', today()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', today()))
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Banner $b) => [
                    'id' => $b->id,
                    'position' => $b->position,
                    'device' => $b->device,
                    'title' => $b->title,
                    'subtitle' => $b->subtitle,
                    'image_desktop' => $b->image_desktop,
                    'image_mobile' => $b->image_mobile,
                    'cta_text' => $b->cta_text,
                    'cta_url' => $b->cta_url,
                ])
                ->all();
        });

        return array_values(array_filter(
            $all,
            fn ($b) => $b['position'] === $position && in_array($b['device'], ['all', $device], true),
        ));
    }

    /** @return array<int, array{question: string, answer: string}> */
    public function faqs(string $category = 'general'): array
    {
        return Cache::remember("kj.faqs.{$category}", now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($category) {
            return Faq::active()->where('category', $category)
                ->get(['question', 'answer'])
                ->map(fn (Faq $f) => ['question' => $f->question, 'answer' => $f->answer])
                ->all();
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function testimonials(int $limit = 6): array
    {
        return Cache::remember('kj.testimonials', now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($limit) {
            return Testimonial::active()->limit($limit)->get()
                ->map(fn (Testimonial $t) => [
                    'name' => $t->name,
                    'designation' => $t->designation,
                    'city' => $t->city,
                    'photo' => $t->photo,
                    'message' => $t->message,
                    'rating' => (int) $t->rating,
                ])
                ->all();
        });
    }

    /**
     * Reading time from the body. Rounded up, never zero — "0 min read" reads
     * like a bug, not a short article.
     */
    public function readingMinutes(?string $html): int
    {
        $words = str_word_count(strip_tags((string) $html));

        return max(1, (int) ceil($words / 200));
    }

    public function excerptFrom(?string $html, int $length = 200): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));

        return $text === '' ? null : Str::limit($text, $length);
    }

    /** Clears everything this service caches. Called from the admin writes. */
    public static function flush(): void
    {
        Cache::forget('kj.banners');
        Cache::forget('kj.testimonials');

        foreach (['header', 'footer_1', 'footer_2', 'mobile'] as $menu) {
            Cache::forget("kj.menu.{$menu}");
        }

        foreach (['general', 'buying', 'selling', 'loan', 'dealer'] as $category) {
            Cache::forget("kj.faqs.{$category}");
        }
    }

    /** Posts related to one, by shared tags then category. */
    public function related(Blog $post, int $limit = 3): array
    {
        return Blog::published()
            ->where('id', '!=', $post->id)
            ->where(function ($q) use ($post) {
                $q->where('blog_category_id', $post->blog_category_id)
                    ->orWhereHas('tags', fn ($t) => $t->whereIn('tags.id', $post->tags->pluck('id')));
            })
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->all();
    }
}
