<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** Assignments a dealer has not responded to inside the SLA. */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('assigned_at', '<=', now()->subMinutes((int) config('kj.leads.response_sla_minutes')));
    }
}
