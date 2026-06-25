<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_a_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
            'age_confirmation' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'ada@example.com')
            ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_registration_requires_terms_and_age_confirmation(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'No Consent',
            'email' => 'noconsent@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['terms', 'age_confirmation']);
    }

    public function test_a_user_can_log_in_and_use_the_token(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk()->assertJsonStructure(['data', 'token']);

        $token = $login->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_login_with_bad_credentials_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Within one test the auth guard caches the resolved user across requests;
        // clear it so /me re-authenticates against the (now revoked) token.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }
}
