<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewRating extends Model
{
    use HasFactory;

    protected $table = 'review_ratings';

    /** The aspect scores are written once with the review; they carry no timestamps. */
    public $timestamps = false;

    protected $fillable = [
        'review_id',
        'aspect',
        'rating',
    ];

    protected function casts(): array
    {
        return [

        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
