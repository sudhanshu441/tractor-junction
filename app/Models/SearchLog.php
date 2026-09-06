<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    use HasFactory;

    protected $table = 'search_logs';

    protected $fillable = [
        'term',
        'context',
        'results_count',
        'user_id',
        'ip',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
