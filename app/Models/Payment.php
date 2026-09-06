<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'reference_no',
        'user_id',
        'payable_type',
        'payable_id',
        'amount',
        'currency',
        'gateway',
        'gateway_order_id',
        'gateway_payment_id',
        'status',
        'gateway_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
        ];
    }
}
