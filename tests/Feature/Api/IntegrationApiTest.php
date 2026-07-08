<?php

namespace Tests\Feature\Api;

use App\Enums\TreeAccessLevel;
use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();
        app(TreeAccessService::class)->assignDefaultRole($user);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_index_lists_providers_with_connection_status(): void
    {
        $this->makeUser();

        $data = $this->getJson('/api/v1/integrations')->assertOk()->json('data');
        $providers = array_column($data, 'provider');

        $this->assertContains('familysearch', $providers);
        $this->assertContains('wikitree', $providers);
        $this->assertContains('geni', $providers);

        // WikiTree needs no server config; none are connected yet.
        $wikitree = collect($data)->firstWhere('provider', 'wikitree');
        $this->assertTrue($wikitree['configured']);
        $this->assertFalse($wikitree['connected']);
    }

    public function test_wikitree_can_be_connected_and_disconnected(): void
    {
        $user = $this->makeUser();

        Http::fake([
            '*' => Http::response(['result' => 'Success', 'token' => 'tok-123', 'userid' => 42, 'username' => 'Smith-1']),
        ]);

        $this->postJson('/api/v1/integrations/wikitree', ['email' => 'me@example.com', 'password' => 'secret'])
            ->assertCreated()
            ->assertJsonPath('connected', true);

        $this->assertDatabaseHas('user_integrations', [
            'user_id' => $user->id,
            'provider' => 'wikitree',
            'provider_username' => 'Smith-1',
        ]);

        $this->getJson('/api/v1/integrations')->assertOk()
            ->assertJsonPath('data', fn ($data) => collect($data)->firstWhere('provider', 'wikitree')['connected'] === true);

        $this->deleteJson('/api/v1/integrations/wikitree')->assertOk()->assertJsonPath('disconnected', true);
        $this->assertDatabaseMissing('user_integrations', ['user_id' => $user->id, 'provider' => 'wikitree']);
    }

    public function test_oauth_authorize_is_blocked_when_provider_is_not_configured(): void
    {
        $this->makeUser();
        config(['integrations.familysearch.client_id' => null]);

        $this->postJson('/api/v1/integrations/familysearch/authorize')->assertStatus(422);
    }

    public function test_oauth_authorize_returns_url_when_configured(): void
    {
        $this->makeUser();
        config(['integrations.familysearch.client_id' => 'test-client']);

        $this->postJson('/api/v1/integrations/familysearch/authorize')
            ->assertOk()
            ->assertJsonPath('url', fn ($url) => str_contains($url, 'client_id=test-client') && str_contains($url, 'state='));
    }

    public function test_research_links_are_generated_for_a_visible_person(): void
    {
        $user = $this->makeUser();
        $tree = FamilyTree::factory()->create(['user_id' => $user->id]);
        app(TreeAccessService::class)->grantTreeAccess($user, $tree, TreeAccessLevel::Owner);
        $person = Person::factory()->for($tree, 'familyTree')->create(['given_name' => 'Ada', 'surname' => 'Lovelace']);

        $this->getJson("/api/v1/people/{$person->id}/research-links")
            ->assertOk()
            ->assertJsonPath('findagrave.search', fn ($url) => str_contains($url, 'firstname=Ada') && str_contains($url, 'lastname=Lovelace'))
            ->assertJsonStructure(['findagrave' => ['search', 'memorial'], 'billiongraves' => ['search', 'grave']]);
    }
}
