<?php

namespace App\Models;

use App\Enums\BlogPostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogPost extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'title', 'slug', 'excerpt',
        'content', 'cover_image', 'status',
        'meta_title', 'meta_description', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BlogPostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function scopePublished($query)
    {
        return $query->where('status', BlogPostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
