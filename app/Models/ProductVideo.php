<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVideo extends Model
{
    use HasFactory;

    protected $table = 'product_videos';

    protected $fillable = [
        'product_id',
        'title',
        'youtube_id',
        'thumbnail',
        'type',
        'duration_seconds',
        'view_count',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
