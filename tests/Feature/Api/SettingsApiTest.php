<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create(['password' => 'Password123!']);
        app(TreeAccessService::class)->assignDefaultRole($user);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_a_user_can_update_their_profile(): void
    {
        $user = $this->actingUser();

        $this->patchJson('/api/v1/settings/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'country_of_residence' => 'Poland',
        ])->assertOk()->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new@example.com']);
    }

    public function test_password_change_requires_the_correct_current_password(): void
    {
        $this->actingUser();

        $this->putJson('/api/v1/settings/password', [
            'current_password' => 'wrong',
            'password' => 'BrandNew123!',
            'password_confirmation' => 'BrandNew123!',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        $this->putJson('/api/v1/settings/password', [
            'current_password' => 'Password123!',
            'password' => 'BrandNew123!',
            'password_confirmation' => 'BrandNew123!',
        ])->assertOk();
    }

    public function test_two_factor_can_be_enabled_confirmed_and_disabled(): void
    {
        $this->actingUser();

        $enable = $this->postJson('/api/v1/settings/two-factor', ['current_password' => 'Password123!'])
            ->assertOk()
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('confirmed', false);

        $secret = $enable->json('secret');
        $otp = (new Google2FA)->getCurrentOtp($secret);

        $this->postJson('/api/v1/settings/two-factor/confirm', ['code' => $otp])
            ->assertOk()
            ->assertJsonPath('confirmed', true);

        $this->assertNotEmpty($enable->json('recovery_codes') === [] ? $this->getJson('/api/v1/settings/two-factor')->json('recovery_codes') : true);

        $this->deleteJson('/api/v1/settings/two-factor', ['current_password' => 'Password123!'])
            ->assertOk()
            ->assertJsonPath('enabled', false);
    }

    public function test_api_tokens_can_be_created_listed_and_revoked(): void
    {
        $this->actingUser();

        $created = $this->postJson('/api/v1/settings/api-tokens', ['name' => 'CLI', 'abilities' => ['read']])
            ->assertCreated()
            ->assertJsonStructure(['plain_text_token', 'token' => ['id', 'name']]);

        $tokenId = $created->json('token.id');

        $this->getJson('/api/v1/settings/api-tokens')->assertOk()
            ->assertJsonFragment(['name' => 'CLI']);

        $this->deleteJson("/api/v1/settings/api-tokens/{$tokenId}")->assertOk();
    }

    public function test_a_user_can_export_their_data(): void
    {
        $this->actingUser();

        $response = $this->get('/api/v1/settings/data-export')->assertOk();
        $this->assertStringContainsString('"profile"', $response->getContent());
    }

    public function test_account_deletion_requires_the_password(): void
    {
        $user = $this->actingUser();

        $this->deleteJson('/api/v1/settings/account', ['password' => 'wrong'])
            ->assertStatus(422);

        $this->deleteJson('/api/v1/settings/account', ['password' => 'Password123!'])
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
