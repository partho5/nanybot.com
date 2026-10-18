<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token auth for the webhook. The key (and its user) are exposed as
 * the `api_key` request attribute and the request's user.
 */
class AuthenticateApiKey
{
    /** Failed attempts allowed per IP per minute before answering 429. */
    private const MAX_FAILURES = 10;

    public function handle(Request $request, Closure $next): Response
    {
        $failKey = 'api-key-failures:'.$request->ip();

        if (RateLimiter::tooManyAttempts($failKey, self::MAX_FAILURES)) {
            return response()->json(['message' => 'Too many failed attempts.'], 429, [
                'Retry-After' => RateLimiter::availableIn($failKey),
            ]);
        }

        $token = $request->bearerToken();
        $key = $token ? ApiKey::findActive($token) : null;
        $user = $key?->user;

        if (! $key || ! $user) {
            RateLimiter::hit($failKey, 60);

            return response()->json(['message' => 'Invalid or missing API key.'], 401, [
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        // Skip updated_at: only record usage, and at most once a minute.
        if (! $key->last_used_at || $key->last_used_at->lt(now()->subMinute())) {
            ApiKey::whereKey($key->id)->toBase()->update(['last_used_at' => now()]);
        }

        $request->attributes->set('api_key', $key);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
