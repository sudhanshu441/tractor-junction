<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmiCalculation extends Model
{
    use HasFactory;

    protected $table = 'emi_calculations';

    protected $fillable = [
        'user_id',
        'product_id',
        'price',
        'down_payment',
        'interest_rate',
        'tenure_months',
        'frequency',
        'emi',
        'total_interest',
        'total_payable',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'emi' => 'decimal:2',
            'total_interest' => 'decimal:2',
            'total_payable' => 'decimal:2',
        ];
    }
}
