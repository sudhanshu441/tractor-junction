<?php

namespace App\Models;

use App\Models\Concerns\MasksMobile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, MasksMobile, SoftDeletes;

    protected $table = 'leads';

    protected $fillable = [
        'reference_no',
        'type',
        'leadable_type',
        'leadable_id',
        'user_id',
        'name',
        'mobile',
        'mobile_verified',
        'email',
        'state_id',
        'district_id',
        'city_id',
        'message',
        'meta',
        'lead_source_id',
        'channel',
        'status',
        'quality',
        'lost_reason',
        'duplicate_of_id',
        'next_follow_up_at',
        'first_contacted_at',
        'converted_at',
        'conversion_value',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'mobile_verified' => 'boolean',
            'meta' => 'array',
            'next_follow_up_at' => 'date',
            'first_contacted_at' => 'datetime',
            'converted_at' => 'datetime',
            'conversion_value' => 'decimal:2',
        ];
    }

    // ----- relationships -----

    /** Product | UsedListing | Dealer | Offer — whatever the enquiry was about. */
    public function leadable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LeadAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(LeadAssignment::class)->latestOfMany();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'duplicate_of_id');
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

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->where('status', 'new')->doesntHave('assignments');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['new', 'assigned', 'contacted', 'qualified']);
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->whereDate('next_follow_up_at', '<=', today())->open();
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ----- helpers -----

    public function getAgeInMinutesAttribute(): int
    {
        return (int) $this->created_at->diffInMinutes(now());
    }
}
