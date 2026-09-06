<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comparison extends Model
{
    use HasFactory;

    protected $table = 'comparisons';

    protected $fillable = [
        'user_id',
        'session_id',
        'slug',
        'category_id',
        'view_count',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
