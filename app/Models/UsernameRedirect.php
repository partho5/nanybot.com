<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A former username of a user; keeps the old author URL alive as a redirect. */
class UsernameRedirect extends Model
{
    protected $fillable = ['user_id', 'username'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
