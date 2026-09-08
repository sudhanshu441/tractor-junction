<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsedListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference_no,
            'slug' => $this->slug,
            'title' => $this->title,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand?->name),
            'year' => $this->manufacturing_year,
            'engine_hours' => $this->engine_hours,
            'condition' => $this->condition,
            'price' => ['amount' => (float) $this->expected_price, 'currency' => 'INR'],
            'is_verified' => (bool) $this->is_verified,
            'is_featured' => (bool) $this->is_featured,
            'location' => [
                'city' => $this->whenLoaded('city', fn () => $this->city?->name),
                'district' => $this->whenLoaded('district', fn () => $this->district?->name),
                'state' => $this->whenLoaded('state', fn () => $this->state?->name),
            ],
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'url' => $image->url(),
                'thumbnail' => $image->thumbnailUrl(),
                'angle' => $image->angle,
            ])->values()),
            // The seller's number is never in a list response; it is revealed
            // only through the verified-contact endpoint, exactly as on the web.
            'seller' => ['name' => $this->whenLoaded('seller', fn () => $this->seller?->name)],
            'posted_at' => $this->published_at?->toIso8601String() ?? $this->created_at?->toIso8601String(),
            'url' => route('used.show', $this->slug),
        ];
    }
}
