<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'property_type'      => $this->property_type?->value,
            'property_type_label'=> $this->property_type?->label(),
            'status'             => $this->status?->value,
            'status_label'       => $this->status?->label(),
            'title'              => $this->title,
            'slug'               => $this->slug,
            'short_description'  => $this->short_description,
            'description'        => $this->description,

            'voivodeship'        => $this->voivodeship,
            'city'               => $this->city,
            'district'           => $this->district,
            'street'             => $this->street,
            'postal_code'        => $this->postal_code,
            'latitude'           => $this->latitude,
            'longitude'          => $this->longitude,

            'area'               => $this->area,
            'rooms_count'        => $this->rooms_count,
            'bathrooms_count'    => $this->bathrooms_count,
            'floor'              => $this->floor,
            'total_floors'       => $this->total_floors,
            'furnishing'         => $this->furnishing?->value,
            'furnishing_label'   => $this->furnishing?->label(),
            'year_built'         => $this->year_built,
            'parking'            => $this->parking,
            'elevator'           => $this->elevator,
            'balcony'            => $this->balcony,

            'room_area'          => $this->room_area,
            'apartment_area'     => $this->apartment_area,
            'roommates_count'    => $this->roommates_count,
            'total_rooms_count'  => $this->total_rooms_count,

            'price'              => $this->price,
            'admin_fee'          => $this->admin_fee,
            'deposit'            => $this->deposit,
            'additional_costs'   => $this->additional_costs,

            'rental_period'      => $this->rental_period?->value,
            'rental_period_label'=> $this->rental_period?->label(),
            'available_from'     => $this->available_from?->format('Y-m-d'),
            'min_rental_months'  => $this->min_rental_months,

            'pets_allowed'       => $this->pets_allowed,
            'smoking_allowed'    => $this->smoking_allowed,
            'students_allowed'   => $this->students_allowed,
            'only_women'         => $this->only_women,
            'only_men'           => $this->only_men,

            'rules'              => $this->rules,
            'notes'              => $this->notes,

            'views_count'        => $this->views_count,
            'favorites_count'    => $this->favorites_count,

            'published_at'       => $this->published_at?->toIso8601String(),
            'rejected_reason'    => $this->rejected_reason,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),

            'is_favorited'       => $this->when(
                isset($this->is_favorited),
                fn() => (bool) $this->is_favorited
            ),

            'owner'   => $this->whenLoaded('owner', fn() => [
                'id'    => $this->owner->id,
                'name'  => $this->owner->name,
                'phone' => $this->owner->phone,
                'email' => $this->owner->email,
            ]),
            'images'  => $this->whenLoaded('images', fn() =>
                $this->images->map(fn($img) => [
                    'id'       => $img->id,
                    'url'      => asset('storage/' . $img->path),
                    'is_main'  => $img->id === $this->main_image_id,
                    'order'    => $img->order,
                ])
            ),
            'main_image' => $this->whenLoaded('mainImage', fn() =>
                $this->mainImage ? asset('storage/' . $this->mainImage->path) : null
            ),
            'main_image_url' => $this->when(
                $this->relationLoaded('images') && $this->images->isNotEmpty(),
                fn() => asset('storage/' . ($this->images->firstWhere('id', $this->main_image_id) ?? $this->images->first())?->path)
            ),
        ];
    }
}
