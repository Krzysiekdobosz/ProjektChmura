<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Models\BlogCategory;
use Illuminate\Http\JsonResponse;

class BlogCategoryController extends ApiController
{
    public function index(): JsonResponse
    {
        $categories = BlogCategory::withCount(['posts' => fn($q) => $q->published()])
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'posts_count' => $c->posts_count,
            ]);

        return $this->success($categories);
    }
}
