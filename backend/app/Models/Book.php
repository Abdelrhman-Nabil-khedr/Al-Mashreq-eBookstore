<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'books';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'isbn',
        'title',
        'subtitle',
        'series',
        'series_position',
        'synopsis',
        'author_bio',
        'pages',
        'language',
        'original_language',
        'publication_date',
        'format',
        'age_range',
        'cover_path',
        'cover',
        'search_vector',
        'is_featured',
        'is_published',
        'author_id',
        'publisher_id',
        'category_id',
        'sub_category_id',
        'contributor_name',
        'contributor_role',
        'illustrator',
        'translator',
        'editor',
        'bisac_code',
        'wgs_code',
        'thema_code',
        'catalog_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pages' => 'integer',
        'publication_date' => 'date',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'cover_url',
        'book_url',
    ];

    /* =========================================================================
     | Relationships
     | ========================================================================= */

    /**
     * Get the author associated with the book.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * Get the publisher associated with the book.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    /**
     * Get the main category of the book.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the secondary/sub category of the book.
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    /**
     * Get external platform purchase / preview links for the book.
     */
    public function platformLinks(): HasMany
    {
        return $this->hasMany(BookPlatformLink::class)->orderBy('sort_order', 'asc');
    }

    /**
     * Get BISAC classification codes for the book.
     */
    public function bisacCodes(): HasMany
    {
        return $this->hasMany(BookBisacCode::class);
    }

    /**
     * Get indexing keywords associated with the book.
     */
    public function keywords(): HasMany
    {
        return $this->hasMany(BookKeyword::class);
    }

    /**
     * Get user reading lists containing this book.
     */
    public function readingLists(): BelongsToMany
    {
        return $this->belongsToMany(ReadingList::class, 'reading_list_book')
            ->withTimestamps();
    }

    /* =========================================================================
     | Query Scopes
     | ========================================================================= */

    /**
     * Scope a query to only include published books.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to only include featured books.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to search books by title, synopsis, or keywords.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('isbn', 'like', "%{$term}%")
              ->orWhere('title', 'like', "%{$term}%")
              ->orWhere('subtitle', 'like', "%{$term}%")
              ->orWhere('search_vector', 'like', "%{$term}%")
              ->orWhereHas('author', function (Builder $authorQ) use ($term) {
                  $authorQ->where('name', 'like', "%{$term}%");
              })
              ->orWhereHas('keywords', function (Builder $keywordQ) use ($term) {
                  $keywordQ->where('keyword', 'like', "%{$term}%");
              });
        });
    }

    /**
     * Scope a query to filter by language.
     */
    public function scopeLanguage(Builder $query, ?string $language): Builder
    {
        if (blank($language)) {
            return $query;
        }

        return $query->where('language', $language);
    }

    /**
     * Scope a query to filter by category slug.
     */
    public function scopeCategorySlug(Builder $query, ?string $slug): Builder
    {
        if (blank($slug)) {
            return $query;
        }

        return $query->whereHas('category', function (Builder $q) use ($slug) {
            $q->where('slug', $slug);
        });
    }

    /* =========================================================================
     | Accessors & Mutators
     | ========================================================================= */

    /**
     * Set the cover path attribute and keep cover column in sync.
     */
    public function setCoverPathAttribute(?string $value): void
    {
        $this->attributes['cover_path'] = $value;
        $this->attributes['cover'] = $value;
    }

    /**
     * Set the cover attribute and keep cover_path in sync.
     */
    public function setCoverAttribute(?string $value): void
    {
        $this->attributes['cover'] = $value;
        $this->attributes['cover_path'] = $value;
    }

    /**
     * Get cover attribute fallback to cover_path.
     */
    public function getCoverAttribute(): ?string
    {
        return $this->attributes['cover'] ?? $this->attributes['cover_path'] ?? null;
    }

    /**
     * Get standard cover image URL.
     */
    public function getCoverUrlAttribute(): string
    {
        $path = $this->cover_path ?: $this->cover;
        if (!empty($path)) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            if (str_starts_with($path, '/')) {
                return $path;
            }
            return '/storage/' . ltrim($path, '/');
        }

        return "/storage/covers/{$this->isbn}.jpg";
    }

    /**
     * Get SEO friendly canonical URL for this book.
     */
    public function getBookUrlAttribute(): string
    {
        return "/book/{$this->isbn}";
    }
}
