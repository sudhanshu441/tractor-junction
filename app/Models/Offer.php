<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $table = 'offers';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'brand_id',
        'discount_type',
        'discount_value',
        'banner_image',
        'starts_at',
        'ends_at',
        'states',
        'terms',
        'view_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'states' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
