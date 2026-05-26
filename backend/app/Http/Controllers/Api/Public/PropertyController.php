<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PropertyResource;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Property::where('status', PropertyStatus::Published)
            ->with(['images', 'mainImage'])
            ->withCount('favorites');

        if ($request->type)         $query->where('property_type', $request->type);
        if ($request->city)         $query->where('city', 'like', '%' . $request->city . '%');
        if ($request->voivodeship)  $query->where('voivodeship', $request->voivodeship);
        if ($request->district)     $query->where('district', 'like', '%' . $request->district . '%');
        if ($request->price_min)    $query->where('price', '>=', $request->price_min);
        if ($request->price_max)    $query->where('price', '<=', $request->price_max);
        if ($request->area_min)     $query->where('area', '>=', $request->area_min);
        if ($request->area_max)     $query->where('area', '<=', $request->area_max);
        if ($request->rooms_min)    $query->where('rooms_count', '>=', $request->rooms_min);
        if ($request->rooms_max)    $query->where('rooms_count', '<=', $request->rooms_max);
        if ($request->furnishing)   $query->where('furnishing', $request->furnishing);
        if ($request->pets !== null && $request->pets !== '') $query->where('pets_allowed', filter_var($request->pets, FILTER_VALIDATE_BOOLEAN));
        if ($request->smoking !== null && $request->smoking !== '') $query->where('smoking_allowed', filter_var($request->smoking, FILTER_VALIDATE_BOOLEAN));
        if ($request->students !== null && $request->students !== '') $query->where('students_allowed', filter_var($request->students, FILTER_VALIDATE_BOOLEAN));
        if ($request->parking !== null && $request->parking !== '') $query->where('parking', filter_var($request->parking, FILTER_VALIDATE_BOOLEAN));
        if ($request->available_from) $query->where('available_from', '<=', $request->available_from);

        $sort = match($request->sort) {
            'price_asc'    => ['price', 'asc'],
            'price_desc'   => ['price', 'desc'],
            'area_desc'    => ['area', 'desc'],
            'newest'       => ['published_at', 'desc'],
            default        => ['published_at', 'desc'],
        };
        $query->orderBy($sort[0], $sort[1]);

        $properties = $query->paginate(12);

        // Oznaczymy ulubione zalogowanego użytkownika
        if ($request->user()) {
            $favIds = Favorite::where('user_id', $request->user()->id)
                ->pluck('property_id')
                ->flip();
            $properties->getCollection()->transform(function ($p) use ($favIds) {
                $p->is_favorited = $favIds->has($p->id);
                return $p;
            });
        }

        return $this->paginated($properties, PropertyResource::collection($properties));
    }

    public function show(string $slug, Request $request): JsonResponse
    {
        $property = Property::where('slug', $slug)
            ->where('status', PropertyStatus::Published)
            ->with(['images', 'mainImage', 'owner.profile'])
            ->withCount('favorites')
            ->firstOrFail();

        $property->increment('views_count');

        if ($request->user()) {
            $property->is_favorited = Favorite::where('user_id', $request->user()->id)
                ->where('property_id', $property->id)
                ->exists();
        }

        return $this->success(new PropertyResource($property));
    }
}
