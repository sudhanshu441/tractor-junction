<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValuationRule extends Model
{
    use HasFactory;

    protected $table = 'valuation_rules';

    protected $fillable = [
        'category_id',
        'factor_type',
        'key',
        'multiplier',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'multiplier' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
