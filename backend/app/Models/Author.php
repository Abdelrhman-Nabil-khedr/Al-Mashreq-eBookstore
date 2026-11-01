<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Author extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'authors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'biography',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Author $author) {
            if (empty($author->slug) && !empty($author->name)) {
                $author->slug = Str::slug($author->name) ?: urlencode($author->name);
            }
        });
    }

    /* =========================================================================
     | Relationships
     | ========================================================================= */

    /**
     * Get all books written by this author.
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
