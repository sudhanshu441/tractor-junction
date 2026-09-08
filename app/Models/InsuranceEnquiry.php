<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceEnquiry extends Model
{
    use HasFactory;

    protected $table = 'insurance_enquiries';

    protected $fillable = [
        'reference_no',
        'user_id',
        'insurance_partner_id',
        'product_id',
        'applicant_name',
        'mobile',
        'registration_number',
        'manufacturing_year',
        'coverage_type',
        'previous_policy_expiry',
        'has_claim_history',
        'idv_expected',
        'status',
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'previous_policy_expiry' => 'date',
            'has_claim_history' => 'boolean',
            'idv_expected' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(InsurancePartner::class, 'insurance_partner_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
