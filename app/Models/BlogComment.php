<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogComment extends Model
{
    use HasFactory;

    protected $table = 'blog_comments';

    protected $fillable = [
        'blog_id',
        'user_id',
        'parent_id',
        'body',
        'status',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
