<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_get_a_signature(): void
    {
        $this->postJson('/admin/uploads/sign')->assertUnauthorized();
    }

    public function test_signed_in_users_get_a_valid_signature_without_the_secret(): void
    {
        config(['services.cloudinary' => [
            'cloud_name' => 'demo', 'api_key' => 'k', 'api_secret' => 'topsecret', 'upload_preset' => 'nanybot', 'folder' => 'nanybot',
        ]]);

        $res = $this->actingAs(User::factory()->create())->postJson('/admin/uploads/sign')->assertOk();

        $this->assertSame(sha1("folder=nanybot&timestamp={$res['timestamp']}&upload_preset=nanybot".'topsecret'), $res['signature']);
        $this->assertSame('nanybot', $res['folder']);
        $this->assertStringNotContainsString('topsecret', $res->getContent());
    }

    public function test_returns_503_when_not_configured(): void
    {
        config(['services.cloudinary.upload_preset' => null]);

        $this->actingAs(User::factory()->create())->postJson('/admin/uploads/sign')->assertStatus(503);
    }
}
