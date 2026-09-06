<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanApplicationLender extends Model
{
    use HasFactory;

    protected $table = 'loan_application_lenders';

    protected $fillable = [
        'loan_application_id',
        'lender_id',
        'sent_at',
        'status',
        'sanctioned_amount',
        'offered_rate',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'sanctioned_amount' => 'decimal:2',
            'offered_rate' => 'decimal:2',
        ];
    }
}
