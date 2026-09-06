<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
