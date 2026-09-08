<?php

namespace App\Domain\Seo\Services;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One place decides what goes in <head>.
 *
 * Resolution order for every field: an editor's override in `seo_meta` for this
 * locale, then a per-type template filled from the model, then the site default.
 * A page always gets a title and a description — an empty meta tag is worse than
 * a templated one, because search engines write their own and usually worse.
 */
class SeoService
{
    /**
     * Caps, not targets. Google renders about 60 characters of a title and 155
     * of a description, but a longer tag is truncated in the result rather than
     * penalised — so these exist to stop a pathological override, and the trim
     * below never leaves a dangling "|" behind.
     */
    public const TITLE_MAX = 95;

    public const DESCRIPTION_MAX = 185;

    /** Templates are stored as strings with :placeholders the model fills. */
    public const TEMPLATES = [
        'product' => [
            'title' => ':name Price :year, Specs & Mileage | :site',
            'description' => ':name price starts at :price. Check :hp HP engine, specifications, mileage, features, EMI and dealer offers in India.',
        ],
        'brand' => [
            'title' => ':name Tractors — Price List :year, All Models | :site',
            'description' => 'All :name tractor models with on-road price, HP, specifications and dealer contacts across India.',
        ],
        'used_listing' => [
            'title' => 'Used :title in :city — ₹:price | :site',
            'description' => 'Second hand :title for sale in :city. :year model, :hours engine hours. Verified seller contact on :site.',
        ],
        'dealer' => [
            'title' => ':name — Tractor Dealer in :city | :site',
            'description' => ':name is a tractor dealer in :city. Showroom address, contact, models on offer and buyer reviews.',
        ],
        'blog' => [
            'title' => ':title | :site',
            'description' => ':excerpt',
        ],
        'page' => [
            'title' => ':title | :site',
            'description' => ':excerpt',
        ],
    ];

    /**
     * @param  array<string, string|null>  $overrides  values the controller already knows
     * @return array<string, string|null>
     */
    public function for(?Model $subject, string $type = 'page', array $overrides = [], array $tokens = []): array
    {
        $stored = $this->stored($subject);
        $template = self::TEMPLATES[$type] ?? self::TEMPLATES['page'];

        $tokens = [
            ':site' => Setting::get('site_name', config('kj.brand.name')),
            ':year' => (string) date('Y'),
            ...$tokens,
        ];

        $title = $stored['meta_title']
            ?? $overrides['title']
            ?? $this->fill($template['title'], $tokens);

        $description = $stored['meta_description']
            ?? $overrides['description']
            ?? $this->fill($template['description'], $tokens);

        return [
            'title' => $this->trim($title, self::TITLE_MAX),
            'description' => $this->trim($description, self::DESCRIPTION_MAX),
            'keywords' => $stored['meta_keywords'] ?? ($overrides['keywords'] ?? null),
            'canonical' => $stored['canonical_url'] ?? ($overrides['canonical'] ?? url()->current()),
            'robots' => $stored['robots'] ?? ($overrides['robots'] ?? 'index,follow'),
            'og_title' => $stored['og_title'] ?? $this->trim($title, self::TITLE_MAX),
            'og_description' => $stored['og_description'] ?? $this->trim($description, self::DESCRIPTION_MAX),
            'og_image' => $stored['og_image'] ?? ($overrides['image'] ?? asset('assets/brand/og-default.png')),
        ];
    }

    /**
     * The editor's overrides for this subject in the current locale.
     *
     * Returned as a plain array with nulls dropped, so `??` falls through to the
     * template rather than stopping on an empty string an editor left behind.
     *
     * @return array<string, string>
     */
    private function stored(?Model $subject): array
    {
        if (! $subject || ! method_exists($subject, 'seo')) {
            return [];
        }

        $row = $subject->relationLoaded('seo')
            ? $subject->seo->firstWhere('locale', app()->getLocale())
            : $subject->seo()->where('locale', app()->getLocale())->first();

        if (! $row) {
            return [];
        }

        return array_filter($row->only([
            'meta_title', 'meta_description', 'meta_keywords',
            'canonical_url', 'og_title', 'og_description', 'og_image', 'robots',
        ]), fn ($value) => filled($value));
    }

    /** @param array<string, string|null> $tokens */
    private function fill(string $template, array $tokens): string
    {
        $filled = strtr($template, array_map(fn ($v) => (string) $v, $tokens));

        // A token nobody supplied would otherwise show up as ":hours" in a title.
        $filled = preg_replace('/\s*:[a-z_]+/', '', $filled);

        return trim(preg_replace('/\s{2,}/', ' ', $filled), " \t\n\r,-—|");
    }

    /** Truncates on a word boundary and never ends on a separator. */
    private function trim(?string $value, int $length): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)));

        if (mb_strlen($value) > $length) {
            $value = Str::limit($value, $length, '');
            $value = preg_replace('/\s+\S*$/', '', $value) ?: $value;
        }

        return trim($value, " \t\n\r|-—·,:;");
    }
}
