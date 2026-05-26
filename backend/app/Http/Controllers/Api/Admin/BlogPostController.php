<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BlogPostStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::with(['category:id,name,slug', 'author:id,name', 'tags:id,name,slug'])
            ->when($request->status,      fn($q, $s) => $q->where('status', $s))
            ->when($request->category_id, fn($q, $c) => $q->where('category_id', $c))
            ->when($request->search,      fn($q, $s) => $q->where('title', 'like', "%$s%"))
            ->orderByDesc('created_at')
            ->paginate(15);

        return $this->paginated($posts, $posts->getCollection()->map(fn($p) => $this->format($p)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'content'          => ['required', 'string'],
            'category_id'      => ['nullable', 'integer', 'exists:blog_categories,id'],
            'status'           => ['required', Rule::enum(BlogPostStatus::class)],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags'             => ['nullable', 'array'],
            'tags.*'           => ['string', 'max:50'],
        ]);

        $slug = $this->uniqueSlug($data['title']);
        $post = BlogPost::create([
            ...$data,
            'user_id'      => $request->user()->id,
            'slug'         => $slug,
            'published_at' => $data['status'] === BlogPostStatus::Published->value ? now() : null,
        ]);

        if (!empty($data['tags'])) {
            $tagIds = collect($data['tags'])->map(fn($name) =>
                BlogTag::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)])->id
            );
            $post->tags()->sync($tagIds);
        }

        return $this->created($this->format($post->load(['category', 'tags', 'author'])), 'Wpis utworzony.');
    }

    public function show(int $id): JsonResponse
    {
        $post = BlogPost::with(['category', 'tags', 'author:id,name'])->findOrFail($id);
        return $this->success($this->format($post));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $data = $request->validate([
            'title'            => ['sometimes', 'string', 'max:255'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'content'          => ['sometimes', 'string'],
            'category_id'      => ['nullable', 'integer', 'exists:blog_categories,id'],
            'status'           => ['sometimes', Rule::enum(BlogPostStatus::class)],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags'             => ['nullable', 'array'],
            'tags.*'           => ['string', 'max:50'],
        ]);

        if (isset($data['status']) && $data['status'] === BlogPostStatus::Published->value && !$post->published_at) {
            $data['published_at'] = now();
        }

        $post->update($data);

        if (array_key_exists('tags', $data)) {
            $tagIds = collect($data['tags'] ?? [])->map(fn($name) =>
                BlogTag::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)])->id
            );
            $post->tags()->sync($tagIds);
        }

        return $this->success($this->format($post->fresh(['category', 'tags', 'author'])), 'Wpis zaktualizowany.');
    }

    public function destroy(int $id): JsonResponse
    {
        BlogPost::findOrFail($id)->delete();
        return $this->noContent();
    }

    private function format(BlogPost $p): array
    {
        return [
            'id'               => $p->id,
            'title'            => $p->title,
            'slug'             => $p->slug,
            'excerpt'          => $p->excerpt,
            'content'          => $p->content,
            'status'           => $p->status?->value,
            'category'         => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name, 'slug' => $p->category->slug] : null,
            'author'           => $p->author ? ['id' => $p->author->id, 'name' => $p->author->name] : null,
            'tags'             => $p->tags?->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug]),
            'meta_title'       => $p->meta_title,
            'meta_description' => $p->meta_description,
            'published_at'     => $p->published_at?->toIso8601String(),
            'created_at'       => $p->created_at?->toIso8601String(),
        ];
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;
        while (BlogPost::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
