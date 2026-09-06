<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerDocument extends Model
{
    use HasFactory;

    protected $table = 'dealer_documents';

    protected $fillable = [
        'dealer_id',
        'doc_type',
        'file_path',
        'original_name',
        'status',
        'remarks',
        'verified_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
