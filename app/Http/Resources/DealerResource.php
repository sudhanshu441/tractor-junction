<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->slug,
            'name' => $this->display_name,
            'type' => $this->dealer_type,
            'about' => $this->about,
            'mobile' => $this->masked_mobile,
            'brands' => $this->whenLoaded('brands', fn () => $this->brands->pluck('name')),
            'location' => [
                'address' => $this->address,
                'city' => $this->whenLoaded('city', fn () => $this->city?->name),
                'district' => $this->whenLoaded('district', fn () => $this->district?->name),
                'state' => $this->whenLoaded('state', fn () => $this->state?->name),
                'pincode' => $this->pincode,
                'latitude' => $this->latitude ? (float) $this->latitude : null,
                'longitude' => $this->longitude ? (float) $this->longitude : null,
            ],
            'rating' => ['average' => (float) $this->rating_avg, 'count' => (int) $this->rating_count],
            'is_verified' => $this->verification_status === 'verified',
            'url' => route('dealers.show', $this->slug),
        ];
    }
}
