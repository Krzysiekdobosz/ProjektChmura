<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'role' => $this->roles->first()?->name ?? 'user',
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'profile' => $this->whenLoaded('profile', fn() => [
                'avatar' => $this->profile?->avatar,
                'bio' => $this->profile?->bio,
                'city' => $this->profile?->city,
                'voivodeship' => $this->profile?->voivodeship,
                'date_of_birth' => $this->profile?->date_of_birth,
            ]),
        ];
    }
}
