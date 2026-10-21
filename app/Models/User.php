<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'password',
        'avatar',
        'headline',
        'bio',
        'social_links',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
            'social_links' => 'array',
        ];
    }

    /** @return HasMany<Article, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    /** @return HasMany<UsernameRedirect, $this> */
    public function usernameRedirects(): HasMany
    {
        return $this->hasMany(UsernameRedirect::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Public author page; falls back to the id when no username is set. */
    public function authorUrl(): string
    {
        return route('blog.author', $this->username ?: $this->id);
    }

    /**
     * Social profile URLs (for the author card and Person schema "sameAs").
     *
     * @return array<string, string>
     */
    public function socialProfiles(): array
    {
        return collect($this->social_links ?? [])
            ->filter(fn ($url) => is_string($url) && str_starts_with($url, 'https://'))
            ->all();
    }
}
