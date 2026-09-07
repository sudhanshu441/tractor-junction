<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsedListingImage extends Model
{
    use HasFactory;

    protected $table = 'used_listing_images';

    protected $fillable = [
        'used_listing_id',
        'path',
        'thumbnail_path',
        'angle',
        'is_primary',
        'image_hash',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(UsedListing::class, 'used_listing_id');
    }

    public function url(): string
    {
        return asset('storage/'.ltrim($this->path, '/'));
    }

    public function thumbnailUrl(): string
    {
        return asset('storage/'.ltrim($this->thumbnail_path ?: $this->path, '/'));
    }
}
