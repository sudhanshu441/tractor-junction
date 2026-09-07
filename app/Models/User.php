<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'mobile', 'mobile_verified_at', 'email', 'email_verified_at', 'password',
        'user_type', 'state_id', 'district_id', 'city_id', 'locale', 'avatar',
        'referral_code', 'is_active', 'blocked_reason', 'blocked_at',
        'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'mobile_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'blocked_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            $user->referral_code ??= strtoupper(Str::random(8));
        });
    }

    // ----- relationships -----

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
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

    public function usedListings(): HasMany
    {
        return $this->hasMany(UsedListing::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function leadAssignments(): HasMany
    {
        return $this->hasMany(LeadAssignment::class);
    }

    public function leadActivities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    /** Dealer memberships — a user can be owner of one dealer and staff at another. */
    public function dealerLinks(): HasMany
    {
        return $this->hasMany(DealerUser::class);
    }

    public function ownedDealer(): HasOne
    {
        return $this->hasOne(Dealer::class, 'owner_user_id');
    }

    // ----- scopes -----

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('user_type', 'customer');
    }

    public function scopeStaff(Builder $query): Builder
    {
        return $query->where('user_type', 'staff');
    }

    public function scopeDealers(Builder $query): Builder
    {
        return $query->where('user_type', 'dealer');
    }

    // ----- helpers -----

    public function isStaff(): bool
    {
        return $this->user_type === 'staff';
    }

    public function isDealer(): bool
    {
        return $this->user_type === 'dealer';
    }

    public function hasVerifiedMobile(): bool
    {
        return $this->mobile_verified_at !== null;
    }

    /** 98XXXXXX12 — what non-privileged roles see in lead and listing screens. */
    public function getMaskedMobileAttribute(): string
    {
        $m = $this->mobile;

        return strlen($m) < 10 ? $m : substr($m, 0, 2).str_repeat('X', strlen($m) - 4).substr($m, -2);
    }

    public function getInitialsAttribute(): string
    {
        return Str::of($this->name)->explode(' ')->take(2)
            ->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode('');
    }
}
