<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePartner extends Model
{
    use HasFactory;

    protected $table = 'insurance_partners';

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'coverage_types',
        'description',
        'states_served',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'coverage_types' => 'array',
            'states_served' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
