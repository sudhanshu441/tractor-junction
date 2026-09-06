<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerInventory extends Model
{
    use HasFactory;

    protected $table = 'dealer_inventory';

    protected $fillable = [
        'dealer_id',
        'dealer_branch_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'offer_price',
        'availability',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'offer_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
