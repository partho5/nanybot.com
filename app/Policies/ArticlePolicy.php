<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

/**
 * Admins manage every article; authors only their own. Auto-discovered by
 * Laravel (App\Models\Article → App\Policies\ArticlePolicy).
 */
class ArticlePolicy
{
    /** Every signed-in user is an author (or admin). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Article $article): bool
    {
        return $this->owns($user, $article);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Article $article): bool
    {
        return $this->owns($user, $article);
    }

    public function delete(User $user, Article $article): bool
    {
        return $this->owns($user, $article);
    }

    private function owns(User $user, Article $article): bool
    {
        return $user->isAdmin() || $article->author_id === $user->id;
    }
}
