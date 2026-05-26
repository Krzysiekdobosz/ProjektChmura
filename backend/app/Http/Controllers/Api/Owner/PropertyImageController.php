<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\ApiController;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyImageController extends ApiController
{
    public function __construct(private ActivityLogService $log) {}

    public function store(Request $request, int $id): JsonResponse
    {
        $property = Property::where('user_id', $request->user()->id)->findOrFail($id);

        $request->validate([
            'images'   => ['required', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $uploaded = [];
        $maxOrder = PropertyImage::where('property_id', $property->id)->max('order') ?? 0;

        foreach ($request->file('images') as $file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path     = $file->storeAs("properties/{$property->id}", $filename, 'public');

            $image = PropertyImage::create([
                'property_id' => $property->id,
                'path'        => $path,
                'filename'    => $file->getClientOriginalName(),
                'order'       => ++$maxOrder,
                'is_main'     => false,
            ]);

            if (!$property->main_image_id) {
                $property->update(['main_image_id' => $image->id]);
            }

            $uploaded[] = [
                'id'      => $image->id,
                'url'     => asset('storage/' . $path),
                'is_main' => $image->id === $property->fresh()->main_image_id,
                'order'   => $image->order,
            ];
        }

        $this->log->log('property.image.upload', $request->user()->id,
            "Wgrano " . count($uploaded) . " zdjęcia do oferty #{$property->id}",
            Property::class, $property->id, $request);

        return $this->created(['images' => $uploaded], 'Zdjęcia zostały wgrane.');
    }

    public function setMain(Request $request, int $id, int $imageId): JsonResponse
    {
        $property = Property::where('user_id', $request->user()->id)->findOrFail($id);
        PropertyImage::where('property_id', $property->id)->findOrFail($imageId);

        $property->update(['main_image_id' => $imageId]);

        return $this->success(null, 'Zdjęcie główne zostało ustawione.');
    }

    public function destroy(Request $request, int $id, int $imageId): JsonResponse
    {
        $property = Property::where('user_id', $request->user()->id)->findOrFail($id);
        $image    = PropertyImage::where('property_id', $property->id)->findOrFail($imageId);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        if ($property->main_image_id === $imageId) {
            $next = PropertyImage::where('property_id', $property->id)->first();
            $property->update(['main_image_id' => $next?->id]);
        }

        $this->log->log('property.image.delete', $request->user()->id,
            "Usunięto zdjęcie #{$imageId} z oferty #{$property->id}",
            Property::class, $property->id, $request);

        return $this->noContent();
    }

    public function reorder(Request $request, int $id): JsonResponse
    {
        $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);
        $property = Property::where('user_id', $request->user()->id)->findOrFail($id);

        foreach ($request->order as $index => $imageId) {
            PropertyImage::where('property_id', $property->id)->where('id', $imageId)->update(['order' => $index]);
        }

        return $this->success(null, 'Kolejność zdjęć zapisana.');
    }
}
