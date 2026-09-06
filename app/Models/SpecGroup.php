<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecGroup extends Model
{
    use HasFactory;

    protected $table = 'spec_groups';

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(SpecAttribute::class)->orderBy('sort_order');
    }
}
