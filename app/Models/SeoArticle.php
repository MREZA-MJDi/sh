<?php

namespace App\Models;

use App\Models\Concerns\SanitizesRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoArticle extends Model
{
    use SanitizesRichText;

    protected $fillable = [
        'author_id', 'title', 'slug', 'excerpt', 'body', 'featured_image_path',
        'image_alt', 'meta_title', 'meta_description', 'canonical_url',
        'og_image_path', 'keywords', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
