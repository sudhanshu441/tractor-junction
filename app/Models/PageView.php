<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    use HasFactory;

    protected $table = 'page_views';

    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'user_id',
        'session_id',
        'ip',
        'referer',
        'utm_source',
        'viewed_on',
    ];

    protected function casts(): array
    {
        return [
            'viewed_on' => 'date',
        ];
    }
}
