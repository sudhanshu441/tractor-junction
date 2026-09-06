<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCompetitor extends Model
{
    use HasFactory;

    protected $table = 'product_competitors';

    protected $fillable = [
        'product_id',
        'competitor_product_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
