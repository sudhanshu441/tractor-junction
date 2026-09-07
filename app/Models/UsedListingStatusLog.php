<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsedListingStatusLog extends Model
{
    use HasFactory;

    protected $table = 'used_listing_status_logs';

    protected $fillable = [
        'used_listing_id',
        'from_status',
        'to_status',
        'changed_by',
        'remarks',
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

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
