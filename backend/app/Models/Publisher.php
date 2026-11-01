<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Publisher extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'publishers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'url',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Publisher $publisher) {
            if (empty($publisher->slug) && !empty($publisher->name)) {
                $publisher->slug = Str::slug($publisher->name) ?: urlencode($publisher->name);
            }
        });
    }

    /* =========================================================================
     | Relationships
     | ========================================================================= */

    /**
     * Get all books published by this publisher.
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
