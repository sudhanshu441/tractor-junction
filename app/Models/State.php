<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    use HasFactory;

    protected $table = 'states';

    protected $fillable = [
        'country_id',
        'name',
        'slug',
        'code',
        'latitude',
        'longitude',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:2',
            'longitude' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function dealers(): HasMany
    {
        return $this->hasMany(Dealer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
