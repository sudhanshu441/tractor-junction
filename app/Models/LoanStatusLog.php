<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanStatusLog extends Model
{
    use HasFactory;

    protected $table = 'loan_status_logs';

    protected $fillable = [
        'loan_application_id',
        'from_status',
        'to_status',
        'changed_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
