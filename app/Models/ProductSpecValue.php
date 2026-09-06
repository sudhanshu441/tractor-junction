<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecValue extends Model
{
    use HasFactory;

    protected $table = 'product_spec_values';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'spec_attribute_id',
        'value_string',
        'value_number',
        'value_boolean',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:2',
            'value_boolean' => 'boolean',
            'value_json' => 'array',
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

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(SpecAttribute::class, 'spec_attribute_id');
    }

    /** The typed column this attribute actually stores its value in. */
    public function getValueAttribute(): mixed
    {
        return $this->value_number ?? $this->value_string ?? $this->value_boolean ?? $this->value_json;
    }
}
