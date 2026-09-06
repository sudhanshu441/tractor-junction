<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';

    protected $fillable = [
        'position',
        'title',
        'subtitle',
        'image_desktop',
        'image_mobile',
        'cta_text',
        'cta_url',
        'device',
        'starts_at',
        'ends_at',
        'impression_count',
        'click_count',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
