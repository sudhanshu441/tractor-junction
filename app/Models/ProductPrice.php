<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory;

    protected $table = 'product_prices';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'state_id',
        'city_id',
        'ex_showroom',
        'rto_charges',
        'insurance_amount',
        'other_charges',
        'on_road_price',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ex_showroom' => 'decimal:2',
            'rto_charges' => 'decimal:2',
            'insurance_amount' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'on_road_price' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    protected static function booted(): void
    {
        // on_road is always derived, never entered by hand
        static::saving(function (self $price) {
            $price->on_road_price = (float) $price->ex_showroom
                + (float) $price->rto_charges
                + (float) $price->insurance_amount
                + (float) $price->other_charges;
        });
    }
}
