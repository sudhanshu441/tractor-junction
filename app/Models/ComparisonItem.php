<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComparisonItem extends Model
{
    use HasFactory;

    protected $table = 'comparison_items';

    protected $fillable = [
        'comparison_id',
        'product_id',
        'position',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
