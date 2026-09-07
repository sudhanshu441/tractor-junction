<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingRule extends Model
{
    use HasFactory;

    protected $table = 'routing_rules';

    protected $fillable = [
        'name',
        'priority',
        'lead_type',
        'state_id',
        'district_id',
        'brand_id',
        'category_id',
        'assignee_type',
        'fallback_user_id',
        'daily_cap',
        'response_sla_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function fallbackUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fallback_user_id');
    }
}
