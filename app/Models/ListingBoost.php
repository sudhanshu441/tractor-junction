<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingBoost extends Model
{
    use HasFactory;

    protected $table = 'listing_boosts';

    protected $fillable = [
        'used_listing_id',
        'plan_id',
        'amount',
        'payment_id',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
