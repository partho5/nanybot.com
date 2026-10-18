<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

/** Public registration is disabled: users are created by admins only. */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_feature_is_disabled()
    {
        $this->assertFalse(Features::enabled(Features::registration()));
    }

    public function test_registration_screen_is_not_available()
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_visitors_cannot_register()
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(404);

        $this->assertGuest();
        $this->assertDatabaseMissing(User::class, ['email' => 'test@example.com']);
    }
}
