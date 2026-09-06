<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecAttribute extends Model
{
    use HasFactory;

    protected $table = 'spec_attributes';

    protected $fillable = [
        'spec_group_id',
        'name',
        'slug',
        'data_type',
        'unit',
        'options',
        'is_filterable',
        'is_comparable',
        'is_key_spec',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_filterable' => 'boolean',
            'is_comparable' => 'boolean',
            'is_key_spec' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SpecGroup::class, 'spec_group_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductSpecValue::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_spec_attribute');
    }

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    public function scopeKeySpecs($query)
    {
        return $query->where('is_key_spec', true);
    }
}
