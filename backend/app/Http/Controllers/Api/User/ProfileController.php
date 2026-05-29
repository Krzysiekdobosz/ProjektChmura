<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends ApiController
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => $request->bio,
                'city' => $request->city,
                'voivodeship' => $request->voivodeship,
                'date_of_birth' => $request->date_of_birth,
            ]
        );

        return $this->success(
            new UserResource($user->fresh()->load('profile')),
            'Profil zaktualizowany.'
        );
    }
}
