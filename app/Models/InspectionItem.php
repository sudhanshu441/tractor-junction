<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(InspectionChecklistItem::class, 'inspection_checklist_item_id');
    }
}
