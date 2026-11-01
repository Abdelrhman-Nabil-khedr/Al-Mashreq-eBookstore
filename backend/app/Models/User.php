<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'preferred_lang',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_USER = 'user';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_EDITOR,
        self::ROLE_USER,
    ];

    /* =========================================================================
     | Filament Authentication & Authorization
     | ========================================================================= */

    /**
     * Determine whether the user can access the Filament admin panel.
     * ONLY users with the 'super_admin' or 'editor' role can access the Filament backend.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_EDITOR], true);
    }

    /**
     * Get the user's name for Filament display.
     */
    public function getFilamentName(): string
    {
        return $this->name ?? $this->email;
    }

    /* =========================================================================
     | Relationships
     | ========================================================================= */

    /**
     * Get user's reading lists / wishlists.
     */
    public function readingLists(): HasMany
    {
        return $this->hasMany(ReadingList::class);
    }

    /**
     * Get blog posts authored by this user.
     */
    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }

    /* =========================================================================
     | Role Helpers
     | ========================================================================= */

    /**
     * Check if user is a super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is an administrator (backward-compatibility alias).
     */
    public function isAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user has editorial rights.
     */
    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR;
    }

    /**
     * Check if user is a standard frontend user.
     */
    public function isRegularUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }
}
