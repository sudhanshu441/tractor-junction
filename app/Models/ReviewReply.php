<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewReply extends Model
{
    use HasFactory;

    protected $table = 'review_replies';

    protected $fillable = [
        'review_id',
        'user_id',
        'body',
        'status',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
