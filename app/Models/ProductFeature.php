<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFeature extends Model
{
    use HasFactory;

    protected $table = 'product_features';

    protected $fillable = [
        'product_id',
        'title',
        'description',
        'icon',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
