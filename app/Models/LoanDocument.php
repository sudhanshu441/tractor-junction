<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDocument extends Model
{
    use HasFactory;

    protected $table = 'loan_documents';

    protected $fillable = [
        'loan_application_id',
        'doc_type',
        'file_path',
        'original_name',
        'status',
        'remarks',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
