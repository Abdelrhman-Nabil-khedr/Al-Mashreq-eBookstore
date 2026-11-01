<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'blog_posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'cover_image',
        'excerpt',
        'content',
        'is_published',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'cover_image_url',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (BlogPost $post) {
            if (empty($post->slug) && !empty($post->title)) {
                $post->slug = Str::slug($post->title) ?: urlencode($post->title);
            }

            if ($post->is_published && empty($post->published_at)) {
                $post->published_at = now();
            }
        });

        static::updating(function (BlogPost $post) {
            if (empty($post->slug) && !empty($post->title)) {
                $post->slug = Str::slug($post->title) ?: urlencode($post->title);
            }

            if ($post->is_published && empty($post->published_at)) {
                $post->published_at = now();
            }
        });
    }

    /* =========================================================================
     | Relationships
     | ========================================================================= */

    /**
     * Get the author (admin/editor user) of the post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /* =========================================================================
     | Accessors & Mutators
     | ========================================================================= */

    /**
     * Get public URL for the cover image.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (empty($this->cover_image)) {
            return asset('assets/logo.png');
        }

        if (Str::startsWith($this->cover_image, ['http://', 'https://'])) {
            return $this->cover_image;
        }

        return asset('storage/' . ltrim($this->cover_image, '/'));
    }

    /* =========================================================================
     | Query Scopes
     | ========================================================================= */

    /**
     * Scope to only published posts.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
                     ->where(function (Builder $sub) {
                         $sub->whereNull('published_at')
                             ->orWhere('published_at', '<=', now());
                     });
    }
}
