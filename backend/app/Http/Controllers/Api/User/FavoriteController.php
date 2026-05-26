<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PropertyResource;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::where('user_id', $request->user()->id)
            ->with(['property.images', 'property.mainImage'])
            ->latest()
            ->paginate(12);

        $properties = $favorites->getCollection()->map(function ($fav) {
            $p = $fav->property;
            if ($p) $p->is_favorited = true;
            return $p;
        })->filter();

        return $this->paginated($favorites, PropertyResource::collection($properties));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['property_id' => ['required', 'integer', 'exists:properties,id']]);

        $existing = Favorite::where('user_id', $request->user()->id)
            ->where('property_id', $request->property_id)
            ->first();

        if ($existing) {
            return $this->success(['favorited' => true], 'Już jest w ulubionych.');
        }

        Favorite::create([
            'user_id'     => $request->user()->id,
            'property_id' => $request->property_id,
        ]);

        Property::where('id', $request->property_id)->increment('favorites_count');

        return $this->created(['favorited' => true], 'Dodano do ulubionych.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $fav = Favorite::where('user_id', $request->user()->id)
            ->where('property_id', $id)
            ->first();

        if ($fav) {
            $fav->delete();
            Property::where('id', $id)->decrement('favorites_count');
        }

        return $this->noContent();
    }
}
