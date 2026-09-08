<?php

namespace App\Domain\Seo\Services;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\Page;
use App\Models\Product;
use App\Models\State;
use App\Models\UsedListing;
use App\Support\Locale;
use Illuminate\Support\Facades\Storage;

/**
 * Chunked XML sitemaps.
 *
 * A marketplace outgrows a single sitemap quickly — the 50,000-URL limit is
 * reached by used listings alone — so each section is written in chunks and an
 * index points at them. Every URL carries its hreflang alternates, because the
 * bilingual pages are the same content, not duplicates.
 */
class SitemapBuilder
{
    public const CHUNK = 5000;

    public const DISK = 'public';

    public const DIR = 'sitemaps';

    /** @return array<int, string> the files written, relative to the public disk */
    public function build(): array
    {
        $disk = Storage::disk(self::DISK);
        $disk->deleteDirectory(self::DIR);
        $disk->makeDirectory(self::DIR);

        $files = [];

        foreach ($this->sections() as $name => $urls) {
            foreach (array_chunk($urls, self::CHUNK) as $i => $chunk) {
                $file = self::DIR.'/'.$name.($i > 0 ? '-'.($i + 1) : '').'.xml';
                $disk->put($file, $this->urlset($chunk));
                $files[] = $file;
            }
        }

        $disk->put(self::DIR.'/index.xml', $this->index($files));

        return [...$files, self::DIR.'/index.xml'];
    }

    /**
     * @return array<string, array<int, array{loc: string, lastmod: ?string, priority: string, changefreq: string}>>
     */
    private function sections(): array
    {
        return array_filter([
            'static' => $this->static(),
            'products' => $this->products(),
            'brands' => $this->brands(),
            'dealers' => $this->dealers(),
            'used' => $this->usedListings(),
            'content' => $this->content(),
        ], fn ($urls) => $urls !== []);
    }

    private function static(): array
    {
        $urls = [];

        foreach ([
            ['home', [], '1.0', 'daily'],
            ['used.index', [], '0.9', 'hourly'],
            ['dealers.index', [], '0.8', 'weekly'],
            ['loan.hub', [], '0.7', 'monthly'],
            ['emi.index', [], '0.7', 'monthly'],
            ['insurance.index', [], '0.7', 'monthly'],
            ['sell.start', [], '0.8', 'monthly'],
            ['compare.index', [], '0.6', 'weekly'],
            ['blogs.index', [], '0.8', 'daily'],
            ['videos.index', [], '0.6', 'weekly'],
            ['faqs.index', [], '0.5', 'monthly'],
            ['contact', [], '0.4', 'yearly'],
        ] as [$name, $params, $priority, $freq]) {
            if (! app('router')->has($name)) {
                continue;
            }

            $urls[] = $this->url(route($name, $params), null, $priority, $freq);
        }

        foreach (['tractors', 'implements', 'harvesters', 'tractor-tyres', 'farm-tools'] as $type) {
            if (app('router')->has("catalog.{$type}.index")) {
                $urls[] = $this->url(route("catalog.{$type}.index"), null, '0.9', 'daily');
            }
        }

        // State price lists — high-intent long-tail traffic.
        foreach (State::where('is_active', true)->get(['slug', 'updated_at']) as $state) {
            $urls[] = $this->url(route('products.price-list', $state->slug), $state->updated_at, '0.7', 'weekly');
        }

        return $urls;
    }

    private function products(): array
    {
        $urls = [];

        Product::where('is_active', true)
            ->with('brand:id,slug', 'category:id,type')
            ->select(['id', 'slug', 'brand_id', 'category_id', 'updated_at'])
            ->chunkById(1000, function ($batch) use (&$urls) {
                foreach ($batch as $product) {
                    if (! $product->brand || ! $product->category) {
                        continue;
                    }

                    $urls[] = $this->url(
                        url('/'.$this->segmentFor($product->category->type).'/'.$product->brand->slug.'/'.$product->slug),
                        $product->updated_at,
                        '0.9',
                        'weekly',
                    );
                }
            });

        return $urls;
    }

    private function brands(): array
    {
        return Brand::where('is_active', true)->get(['slug', 'updated_at'])
            ->map(fn (Brand $brand) => $this->url(route('brands.show', $brand->slug), $brand->updated_at, '0.8', 'weekly'))
            ->all();
    }

    private function dealers(): array
    {
        return Dealer::verified()->get(['slug', 'updated_at'])
            ->map(fn (Dealer $dealer) => $this->url(route('dealers.show', $dealer->slug), $dealer->updated_at, '0.7', 'weekly'))
            ->all();
    }

    private function usedListings(): array
    {
        $urls = [];

        UsedListing::where('status', 'live')
            ->select(['id', 'slug', 'updated_at'])
            ->chunkById(1000, function ($batch) use (&$urls) {
                foreach ($batch as $listing) {
                    $urls[] = $this->url(route('used.show', $listing->slug), $listing->updated_at, '0.8', 'daily');
                }
            });

        return $urls;
    }

    private function content(): array
    {
        $urls = [];

        foreach (Page::where('is_active', true)->get(['slug', 'updated_at']) as $page) {
            $urls[] = $this->url(route('pages.show', $page->slug), $page->updated_at, '0.5', 'monthly');
        }

        foreach (Blog::published()->get(['slug', 'updated_at']) as $post) {
            $urls[] = $this->url(route('blogs.show', $post->slug), $post->updated_at, '0.7', 'weekly');
        }

        foreach (Category::active()->get(['slug', 'type', 'updated_at']) as $category) {
            $urls[] = $this->url(
                url('/'.$this->segmentFor($category->type)),
                $category->updated_at,
                '0.7',
                'weekly',
            );
        }

        return $urls;
    }

    /** The URL segment a category type is published under. */
    private function segmentFor(?string $type): string
    {
        return match ($type) {
            'implement' => 'implements',
            'harvester' => 'harvesters',
            'tyre' => 'tractor-tyres',
            'farm_tool' => 'farm-tools',
            default => 'tractors',
        };
    }

    private function url(string $loc, $lastmod = null, string $priority = '0.5', string $changefreq = 'weekly'): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod?->toAtomString(),
            'priority' => $priority,
            'changefreq' => $changefreq,
        ];
    }

    private function urlset(array $urls): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'];

        foreach ($urls as $url) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>'.e($url['loc']).'</loc>';

            if ($url['lastmod']) {
                $xml[] = '    <lastmod>'.$url['lastmod'].'</lastmod>';
            }

            $xml[] = '    <changefreq>'.$url['changefreq'].'</changefreq>';
            $xml[] = '    <priority>'.$url['priority'].'</priority>';

            foreach (array_keys(Locale::supported()) as $code) {
                $alternate = $code === Locale::DEFAULT
                    ? $url['loc']
                    : str_replace(url('/'), url('/'.$code), $url['loc']);

                $xml[] = '    <xhtml:link rel="alternate" hreflang="'.$code.'" href="'.e($alternate).'"/>';
            }

            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml);
    }

    private function index(array $files): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($files as $file) {
            $xml[] = '  <sitemap>';
            $xml[] = '    <loc>'.e(asset('storage/'.$file)).'</loc>';
            $xml[] = '    <lastmod>'.now()->toAtomString().'</lastmod>';
            $xml[] = '  </sitemap>';
        }

        $xml[] = '</sitemapindex>';

        return implode("\n", $xml);
    }
}
