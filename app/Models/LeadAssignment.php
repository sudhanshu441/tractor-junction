<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadAssignment extends Model
{
    use HasFactory;

    protected $table = 'lead_assignments';

    protected $fillable = [
        'lead_id',
        'assignee_type',
        'dealer_id',
        'user_id',
        'assigned_by',
        'routing_rule_id',
        'status',
        'assigned_at',
        'responded_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }
}
