<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerBranch extends Model
{
    use HasFactory;

    protected $table = 'dealer_branches';

    protected $fillable = [
        'dealer_id',
        'name',
        'address',
        'state_id',
        'district_id',
        'city_id',
        'pincode',
        'mobile',
        'latitude',
        'longitude',
        'is_head_office',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:2',
            'longitude' => 'decimal:2',
            'is_head_office' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
