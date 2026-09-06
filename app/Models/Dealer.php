<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dealer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dealers';

    protected $fillable = [
        'code',
        'owner_user_id',
        'business_name',
        'display_name',
        'slug',
        'dealer_type',
        'gstin',
        'pan',
        'contact_person',
        'mobile',
        'alternate_mobile',
        'email',
        'address',
        'state_id',
        'district_id',
        'city_id',
        'pincode',
        'latitude',
        'longitude',
        'logo',
        'about',
        'working_hours',
        'verification_status',
        'verification_remarks',
        'verified_by',
        'verified_at',
        'rating_avg',
        'rating_count',
        'lead_count',
        'response_score',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:2',
            'longitude' => 'decimal:2',
            'working_hours' => 'array',
            'verified_at' => 'datetime',
            'rating_avg' => 'decimal:2',
            'response_score' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ----- relationships -----

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(DealerBranch::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(DealerUser::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'dealer_brand')->withPivot('authorised_since');
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(DealerInventory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DealerDocument::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(DealerSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(DealerSubscription::class)->where('status', 'active')->latestOfMany();
    }

    public function usedListings(): HasMany
    {
        return $this->hasMany(UsedListing::class);
    }

    public function leadAssignments(): HasMany
    {
        return $this->hasMany(LeadAssignment::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function seo(): MorphMany
    {
        return $this->morphMany(SeoMeta::class, 'seoable');
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

    // ----- scopes -----

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', 'verified')->where('is_active', true);
    }

    public function scopePendingVerification(Builder $query): Builder
    {
        return $query->where('verification_status', 'pending')->orderBy('created_at');
    }

    /** Dealers eligible to receive a lead in a district, optionally for a brand. */
    public function scopeEligibleFor(Builder $query, ?int $districtId, ?int $brandId = null): Builder
    {
        return $query->verified()
            ->when($districtId, fn ($q) => $q->where('district_id', $districtId))
            ->when($brandId, fn ($q) => $q->whereHas('brands', fn ($b) => $b->where('brands.id', $brandId)));
    }
}
