<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lender extends Model
{
    use HasFactory;

    protected $table = 'lenders';

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'lender_type',
        'interest_min',
        'interest_max',
        'tenure_min_months',
        'tenure_max_months',
        'amount_min',
        'amount_max',
        'processing_fee_percent',
        'max_ltv_percent',
        'states_served',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'interest_min' => 'decimal:2',
            'interest_max' => 'decimal:2',
            'amount_min' => 'decimal:2',
            'amount_max' => 'decimal:2',
            'processing_fee_percent' => 'decimal:2',
            'max_ltv_percent' => 'decimal:2',
            'states_served' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(LoanApplication::class, 'loan_application_lenders')
            ->withPivot(['status', 'sanctioned_amount', 'offered_rate', 'sent_at', 'remarks'])
            ->withTimestamps();
    }
}
