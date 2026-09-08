<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'type' => $this->category->type,
            ]),
            'hp' => ['min' => $this->hp_min ? (float) $this->hp_min : null, 'max' => $this->hp_max ? (float) $this->hp_max : null],
            'price' => [
                'min' => $this->price_min ? (float) $this->price_min : null,
                'max' => $this->price_max ? (float) $this->price_max : null,
                'currency' => 'INR',
            ],
            'rating' => ['average' => (float) $this->rating_avg, 'count' => (int) $this->rating_count],
            'status' => $this->status,
            'image' => $this->primary_image,
            'short_description' => $this->short_description,
            'specifications' => $this->whenLoaded('specValues', fn () => $this->specValues
                ->filter(fn ($v) => $v->attribute !== null)
                ->map(fn ($v) => [
                    'group' => $v->attribute->group?->name,
                    'name' => $v->attribute->name,
                    'value' => $v->display_value ?? $v->value_string ?? $v->value_number,
                    'unit' => $v->attribute->unit,
                ])->values()),
            'url' => route('products.show', [$this->brand?->slug, $this->slug]),
        ];
    }
}
