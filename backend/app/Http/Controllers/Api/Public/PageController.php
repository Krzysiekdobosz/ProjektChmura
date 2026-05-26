<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends ApiController
{
    public function show(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->where('status', 'published')->firstOrFail();

        return $this->success([
            'id'               => $page->id,
            'title'            => $page->title,
            'slug'             => $page->slug,
            'content'          => $page->content,
            'meta_title'       => $page->meta_title ?: $page->title,
            'meta_description' => $page->meta_description,
        ]);
    }
}
