<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'loan_applications';

    protected $fillable = [
        'reference_no',
        'user_id',
        'purpose',
        'financeable_type',
        'financeable_id',
        'applicant_name',
        'mobile',
        'email',
        'date_of_birth',
        'pan_masked',
        'aadhaar_masked',
        'annual_income',
        'income_source',
        'land_holding_acres',
        'machinery_price',
        'down_payment',
        'loan_amount',
        'tenure_months',
        'expected_interest_rate',
        'calculated_emi',
        'cibil_score',
        'state_id',
        'district_id',
        'city_id',
        'address',
        'status',
        'remarks',
        'assigned_to',
        'submitted_at',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'annual_income' => 'decimal:2',
            'land_holding_acres' => 'decimal:2',
            'machinery_price' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'loan_amount' => 'decimal:2',
            'expected_interest_rate' => 'decimal:2',
            'calculated_emi' => 'decimal:2',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function financeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LoanDocument::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(LoanStatusLog::class)->latest();
    }

    public function lenders(): BelongsToMany
    {
        return $this->belongsToMany(Lender::class, 'loan_application_lenders')
            ->withPivot(['status', 'sanctioned_amount', 'offered_rate', 'sent_at', 'remarks'])
            ->withTimestamps();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['sanctioned', 'rejected', 'disbursed', 'cancelled']);
    }

    public function scopeDocsPending(Builder $query): Builder
    {
        return $query->where('status', 'docs_pending');
    }
}
