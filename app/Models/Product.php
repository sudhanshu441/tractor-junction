<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'slug',
        'model_code',
        'status',
        'hp_min',
        'hp_max',
        'price_min',
        'price_max',
        'launch_year',
        'expected_launch_date',
        'short_description',
        'description',
        'highlights',
        'brochure_path',
        'rating_avg',
        'rating_count',
        'view_count',
        'lead_count',
        'popularity_score',
        'is_featured',
        'is_popular',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'hp_min' => 'decimal:2',
            'hp_max' => 'decimal:2',
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'expected_launch_date' => 'date',
            'highlights' => 'array',
            'rating_avg' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ----- relationships -----

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function specValues(): HasMany
    {
        return $this->hasMany(ProductSpecValue::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFaq::class)->orderBy('sort_order');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class)->orderBy('sort_order');
    }

    public function competitors(): HasMany
    {
        return $this->hasMany(ProductCompetitor::class);
    }

    public function filterCache(): HasOne
    {
        return $this->hasOne(ProductFilterCache::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->orderBy('sort_order');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function seo(): MorphMany
    {
        return $this->morphMany(SeoMeta::class, 'seoable');
    }

    public function usedListings(): HasMany
    {
        return $this->hasMany(UsedListing::class);
    }

    public function dealerInventory(): HasMany
    {
        return $this->hasMany(DealerInventory::class);
    }

    // ----- scopes -----

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'upcoming');
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->where('is_popular', true)->orderByDesc('popularity_score');
    }

    public function scopeOfCategoryType(Builder $query, string $type): Builder
    {
        return $query->whereHas('category', fn ($q) => $q->where('type', $type));
    }

    // ----- helpers -----

    /**
     * Price for a state, falling back to the national row.
     * Callers should eager-load prices to avoid an N+1 across a listing grid.
     */
    public function priceForState(?int $stateId): ?ProductPrice
    {
        return $this->prices
            ->where('is_active', true)
            ->sortByDesc(fn ($p) => $p->state_id === $stateId ? 1 : 0)
            ->first(fn ($p) => $p->state_id === $stateId || $p->state_id === null);
    }

    public function getPrimaryImageAttribute(): ?Media
    {
        return $this->media->firstWhere('collection', 'primary') ?? $this->media->first();
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->brand?->name ?? '').' '.$this->name);
    }

    public function getHpLabelAttribute(): ?string
    {
        if ($this->hp_min === null) {
            return null;
        }

        return $this->hp_max && $this->hp_max != $this->hp_min
            ? rtrim(rtrim((string) $this->hp_min, '0'), '.').'-'.rtrim(rtrim((string) $this->hp_max, '0'), '.').' HP'
            : rtrim(rtrim((string) $this->hp_min, '0'), '.').' HP';
    }
}
