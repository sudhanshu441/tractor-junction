<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotFoundLog extends Model
{
    use HasFactory;

    protected $table = 'not_found_logs';

    protected $fillable = [
        'url',
        'referer',
        'hit_count',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }
}
