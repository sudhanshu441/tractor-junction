<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionItem extends Model
{
    use HasFactory;

    protected $table = 'inspection_items';

    protected $fillable = [
        'inspection_id',
        'inspection_checklist_item_id',
        'score',
        'remarks',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
