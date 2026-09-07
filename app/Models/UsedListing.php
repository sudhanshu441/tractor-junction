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

class UsedListing extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'used_listings';

    protected $attributes = [
        'status' => 'draft',
        'seller_type' => 'owner',
        'view_count' => 0,
        'lead_count' => 0,
        'is_verified' => false,
        'is_featured' => false,
        'is_price_negotiable' => true,
    ];

    protected $fillable = [
        'reference_no',
        'user_id',
        'dealer_id',
        'seller_type',
        'category_id',
        'brand_id',
        'product_id',
        'title',
        'slug',
        'manufacturing_year',
        'engine_hours',
        'hp',
        'condition',
        'tyre_condition_front',
        'tyre_condition_rear',
        'has_rc',
        'has_insurance',
        'insurance_valid_till',
        'is_financed',
        'registration_number',
        'expected_price',
        'negotiable_to',
        'is_price_negotiable',
        'description',
        'state_id',
        'district_id',
        'city_id',
        'pincode',
        'latitude',
        'longitude',
        'status',
        'rejection_reason',
        'is_verified',
        'is_featured',
        'featured_till',
        'view_count',
        'lead_count',
        'published_at',
        'expires_at',
        'sold_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'hp' => 'decimal:2',
            'has_rc' => 'boolean',
            'has_insurance' => 'boolean',
            'insurance_valid_till' => 'date',
            'is_financed' => 'boolean',
            'expected_price' => 'decimal:2',
            'negotiable_to' => 'decimal:2',
            'is_price_negotiable' => 'boolean',
            'latitude' => 'decimal:2',
            'longitude' => 'decimal:2',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'sold_at' => 'datetime',
        ];
    }

    // ----- relationships -----

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(UsedListingImage::class)->orderBy('sort_order');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(UsedListingStatusLog::class)->latest();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ListingReport::class);
    }

    public function boosts(): HasMany
    {
        return $this->hasMany(ListingBoost::class);
    }

    public function inspection(): HasOne
    {
        return $this->hasOne(Inspection::class)->latestOfMany();
    }

    public function leads(): MorphMany
    {
        return $this->morphMany(Lead::class, 'leadable');
    }

    public function seo(): MorphMany
    {
        return $this->morphMany(SeoMeta::class, 'seoable');
    }

    // ----- scopes -----

    /** Publicly visible listings: approved, live and not past expiry. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'live')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', 'pending')->orderBy('created_at');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeInDistrict(Builder $query, ?int $districtId): Builder
    {
        return $districtId ? $query->where('district_id', $districtId) : $query;
    }

    // ----- helpers -----

    public function getPrimaryImageAttribute(): ?UsedListingImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    /** Registration numbers are never shown in full on public pages. */
    public function getMaskedRegistrationAttribute(): ?string
    {
        if (! $this->registration_number) {
            return null;
        }

        return substr($this->registration_number, 0, 4).str_repeat('X', max(0, strlen($this->registration_number) - 6)).substr($this->registration_number, -2);
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expires_at !== null && $this->expires_at->isBefore(now()->addDays($days));
    }
}
