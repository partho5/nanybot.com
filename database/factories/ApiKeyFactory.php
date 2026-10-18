<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Creates a key whose plaintext is not retained. To get the plaintext in a
 * test, use ApiKey::generate() instead.
 *
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $plain = 'nb_'.Str::random(40);

        return [
            'name' => fake()->words(2, true).' key',
            'token_hash' => hash('sha256', $plain),
            'prefix' => substr($plain, 0, 10),
            'user_id' => User::factory(),
            'last_used_at' => null,
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked_at' => now()]);
    }
}
