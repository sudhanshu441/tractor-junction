<?php

namespace App\Domain\Seo\Services;

use App\Models\Blog;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\UsedListing;
use App\Models\Video;
use Illuminate\Support\Collection;

/**
 * Structured data builders.
 *
 * Every builder returns a plain array; the layout encodes it. Nothing here
 * invents a value: a field we cannot state truthfully is omitted, because a
 * price or a rating that does not match the page is a manual action waiting to
 * happen, not a ranking win.
 */
class JsonLd
{
    public function organisation(): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => Setting::get('site_name', config('kj.brand.name')),
            'url' => url('/'),
            'logo' => asset('assets/brand/logo-horizontal.svg'),
            'sameAs' => array_values(array_filter([
                Setting::get('social.facebook'),
                Setting::get('social.youtube'),
                Setting::get('social.instagram'),
            ])),
            'contactPoint' => array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'telephone' => Setting::get('support_mobile') ?: null,
                'email' => Setting::get('support_email') ?: null,
                'areaServed' => 'IN',
                'availableLanguage' => ['en', 'hi'],
            ]),
        ], fn ($v) => filled($v));
    }

    public function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => Setting::get('site_name', config('kj.brand.name')),
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/search').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @param array<int, array{name: string, url: string|null}> $crumbs */
    public function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_map(fn ($i, $crumb) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'] ?? null,
            ]), array_keys($crumbs), $crumbs)),
        ];
    }

    /**
     * A model page. The offer is only emitted when we hold a real price, and the
     * aggregate rating only when approved reviews actually exist.
     */
    public function product(Product $product, ?float $price = null, ?array $breadcrumbs = null): array
    {
        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->full_name ?? $product->name,
            'sku' => $product->slug,
            'description' => $product->short_description,
            'image' => $product->media->first()?->url(),
            'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
            'category' => $product->category?->name,
        ], fn ($v) => filled($v));

        if ($price && $price > 0) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'INR',
                'price' => round($price, 2),
                'availability' => $product->status === 'available'
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/PreOrder',
                'url' => url()->current(),
            ];
        }

        if ($product->rating_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $product->rating_avg,
                'reviewCount' => (int) $product->rating_count,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        return $schema;
    }

    /** @param Collection<int, Review> $reviews */
    public function reviews($reviews): array
    {
        return $reviews->take(10)->map(fn ($review) => array_filter([
            '@type' => 'Review',
            'author' => ['@type' => 'Person', 'name' => $review->user?->name ?? 'Verified owner'],
            'datePublished' => $review->created_at?->toDateString(),
            'name' => $review->title,
            'reviewBody' => $review->body,
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => (int) $review->rating,
                'bestRating' => 5,
                'worstRating' => 1,
            ],
        ], fn ($v) => filled($v)))->values()->all();
    }

    /** A used machine is a real, single, physical item for sale. */
    public function usedListing(UsedListing $listing): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $listing->title,
            'sku' => $listing->reference_no,
            'description' => $listing->description,
            'image' => $listing->images->first()?->url(),
            'brand' => $listing->brand ? ['@type' => 'Brand', 'name' => $listing->brand->name] : null,
            'itemCondition' => 'https://schema.org/UsedCondition',
            'productionDate' => $listing->manufacturing_year ? (string) $listing->manufacturing_year : null,
            'offers' => array_filter([
                '@type' => 'Offer',
                'priceCurrency' => 'INR',
                'price' => (float) $listing->expected_price,
                'availability' => $listing->status === 'live'
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/SoldOut',
                'url' => url()->current(),
                'availableAtOrFrom' => $listing->city ? [
                    '@type' => 'Place',
                    'address' => array_filter([
                        '@type' => 'PostalAddress',
                        'addressLocality' => $listing->city->name,
                        'addressRegion' => $listing->state?->name,
                        'addressCountry' => 'IN',
                    ]),
                ] : null,
            ], fn ($v) => filled($v)),
        ], fn ($v) => filled($v));
    }

    public function dealer(Dealer $dealer): array
    {
        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'AutoDealer',
            'name' => $dealer->display_name,
            'url' => route('dealers.show', $dealer->slug),
            'telephone' => $dealer->mobile,
            'email' => $dealer->email,
            'image' => $dealer->logo ? asset('storage/'.$dealer->logo) : null,
            'description' => $dealer->about,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $dealer->address,
                'addressLocality' => $dealer->city?->name,
                'addressRegion' => $dealer->state?->name,
                'postalCode' => $dealer->pincode,
                'addressCountry' => 'IN',
            ]),
        ], fn ($v) => filled($v));

        if ($dealer->latitude && $dealer->longitude) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $dealer->latitude,
                'longitude' => (float) $dealer->longitude,
            ];
        }

        if ($dealer->rating_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $dealer->rating_avg,
                'reviewCount' => (int) $dealer->rating_count,
                'bestRating' => 5,
            ];
        }

        return $schema;
    }

    public function article(Blog $post): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => $post->type === 'news' ? 'NewsArticle' : 'Article',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'image' => $post->cover_image ? asset('storage/'.$post->cover_image) : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => ['@type' => 'Person', 'name' => $post->author?->name ?? Setting::get('site_name')],
            'publisher' => [
                '@type' => 'Organization',
                'name' => Setting::get('site_name', config('kj.brand.name')),
                'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/brand/logo-horizontal.svg')],
            ],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url()->current()],
        ], fn ($v) => filled($v));
    }

    /** @param iterable<int, array{question: string, answer: string}> $faqs */
    public function faq(iterable $faqs): ?array
    {
        $entities = [];

        foreach ($faqs as $faq) {
            if (blank($faq['question'] ?? null) || blank($faq['answer'] ?? null)) {
                continue;
            }

            $entities[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq['answer'])],
            ];
        }

        // An empty FAQPage is a structured-data error, not an empty section.
        return $entities === [] ? null : [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    public function video(Video $video): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $video->title,
            'description' => $video->description,
            'thumbnailUrl' => $video->youtube_id
                ? "https://i.ytimg.com/vi/{$video->youtube_id}/hqdefault.jpg"
                : null,
            'uploadDate' => $video->created_at?->toIso8601String(),
            'embedUrl' => $video->youtube_id
                ? "https://www.youtube-nocookie.com/embed/{$video->youtube_id}"
                : null,
            'duration' => $video->duration_seconds
                ? 'PT'.intdiv($video->duration_seconds, 60).'M'.($video->duration_seconds % 60).'S'
                : null,
        ], fn ($v) => filled($v));
    }
}
