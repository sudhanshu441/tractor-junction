<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'blogs';

    protected $fillable = [
        'blog_category_id',
        'author_id',
        'type',
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image',
        'reading_minutes',
        'view_count',
        'is_featured',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'blog_tag');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function seo(): MorphMany
    {
        return $this->morphMany(SeoMeta::class, 'seoable');
    }

    /**
     * A scheduled post whose time has come is published; the status column is
     * only ever updated by the publisher command, so the date is what decides.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', ['published', 'scheduled'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $query->when($type, fn ($q) => $q->where('type', $type));
    }

    public function isVisible(): bool
    {
        return in_array($this->status, ['published', 'scheduled'], true)
            && $this->published_at !== null
            && $this->published_at->isPast();
    }
}
