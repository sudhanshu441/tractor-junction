<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingReport extends Model
{
    use HasFactory;

    protected $table = 'listing_reports';

    protected $fillable = [
        'used_listing_id',
        'reported_by',
        'reason',
        'details',
        'status',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [

        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(UsedListing::class, 'used_listing_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }
}
