<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFilterCache extends Model
{
    use HasFactory;

    protected $table = 'product_filter_cache';

    protected $fillable = [
        'product_id',
        'brand_id',
        'category_id',
        'hp',
        'price',
        'wheel_drive',
        'cylinders',
        'fuel_type',
        'lift_capacity',
        'transmission',
        'power_steering',
        'ac_cabin',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hp' => 'decimal:2',
            'price' => 'decimal:2',
            'lift_capacity' => 'decimal:2',
            'power_steering' => 'boolean',
            'ac_cabin' => 'boolean',
        ];
    }
}
