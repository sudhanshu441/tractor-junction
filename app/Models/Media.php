<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'model_type',
        'model_id',
        'collection',
        'file_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'conversions',
        'alt_text',
        'caption',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'conversions' => 'array',
        ];
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /** URL for a named conversion, falling back to the original. */
    public function url(string $conversion = 'card'): string
    {
        $path = $this->conversions[$conversion] ?? $this->path;

        return asset('storage/'.ltrim($path, '/'));
    }
}
