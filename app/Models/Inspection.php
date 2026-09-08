<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    use HasFactory;

    protected $table = 'inspections';

    protected $fillable = [
        'reference_no',
        'used_listing_id',
        'inspector_id',
        'scheduled_at',
        'completed_at',
        'status',
        'overall_score',
        'grade',
        'valuation_min',
        'valuation_max',
        'summary',
        'report_pdf_path',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'overall_score' => 'decimal:2',
            'valuation_min' => 'decimal:2',
            'valuation_max' => 'decimal:2',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(UsedListing::class, 'used_listing_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InspectionItem::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['requested', 'scheduled', 'in_progress']);
    }

    public function isApproved(): bool
    {
        return $this->approved_by !== null && $this->status === 'completed';
    }
}
