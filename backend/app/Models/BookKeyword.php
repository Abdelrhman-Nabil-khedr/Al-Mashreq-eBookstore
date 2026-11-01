<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookKeyword extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'book_keywords';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'book_id',
        'keyword',
    ];

    /* =========================================================================
     | Relationships
     | ========================================================================= */

    /**
     * Get the book associated with this keyword.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
