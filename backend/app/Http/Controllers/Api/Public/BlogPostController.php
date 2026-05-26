<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogPostController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::published()
            ->with(['category:id,name,slug', 'author:id,name', 'tags:id,name,slug'])
            ->when($request->category, fn($q, $c) => $q->whereHas('category', fn($cq) => $cq->where('slug', $c)))
            ->when($request->tag,      fn($q, $t) => $q->whereHas('tags', fn($tq) => $tq->where('slug', $t)))
            ->when($request->search,   fn($q, $s) => $q->where('title', 'like', "%$s%"))
            ->orderByDesc('published_at')
            ->paginate(10);

        return $this->paginated($posts, $posts->getCollection()->map(fn($p) => $this->formatList($p)));
    }

    public function show(string $slug): JsonResponse
    {
        $post = BlogPost::published()
            ->with(['category:id,name,slug', 'author:id,name', 'tags:id,name,slug'])
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->success([
            'id'               => $post->id,
            'title'            => $post->title,
            'slug'             => $post->slug,
            'excerpt'          => $post->excerpt,
            'content'          => $post->content,
            'category'         => $post->category ? ['id' => $post->category->id, 'name' => $post->category->name, 'slug' => $post->category->slug] : null,
            'author'           => $post->author ? ['id' => $post->author->id, 'name' => $post->author->name] : null,
            'tags'             => $post->tags?->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug]),
            'meta_title'       => $post->meta_title ?: $post->title,
            'meta_description' => $post->meta_description ?: $post->excerpt,
            'published_at'     => $post->published_at?->toIso8601String(),
        ]);
    }

    private function formatList(BlogPost $p): array
    {
        return [
            'id'           => $p->id,
            'title'        => $p->title,
            'slug'         => $p->slug,
            'excerpt'      => $p->excerpt,
            'category'     => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name, 'slug' => $p->category->slug] : null,
            'author'       => $p->author ? ['name' => $p->author->name] : null,
            'tags'         => $p->tags?->map(fn($t) => ['name' => $t->name, 'slug' => $t->slug]),
            'published_at' => $p->published_at?->toIso8601String(),
        ];
    }
}
