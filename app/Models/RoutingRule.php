<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
