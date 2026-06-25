<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TreeApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        app(TreeAccessService::class)->assignDefaultRole($user);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_a_user_can_create_a_tree_with_an_owner_person(): void
    {
        $this->actingUser();

        $response = $this->postJson('/api/v1/trees', [
            'name' => 'The Lovelace Family',
            'privacy' => 'private',
        ])->assertCreated()->assertJsonPath('data.name', 'The Lovelace Family');

        $treeId = $response->json('data.id');
        $this->assertNotNull($response->json('data.owner_person_id'));
        $this->assertDatabaseHas('family_trees', ['id' => $treeId, 'name' => 'The Lovelace Family']);
        $this->assertDatabaseCount('people', 1);
    }

    public function test_owner_can_add_people_and_a_relative_and_read_the_graph(): void
    {
        $this->actingUser();

        $tree = $this->postJson('/api/v1/trees', ['name' => 'Graph Tree', 'privacy' => 'private'])->json('data');
        $ownerPersonId = $tree['owner_person_id'];

        // Add a father as a relative of the owner person.
        $this->postJson("/api/v1/trees/{$tree['id']}/people/relative", [
            'anchor_person_id' => $ownerPersonId,
            'relation_role' => 'father',
            'given_name' => 'George',
            'surname' => 'Lovelace',
            'sex' => 'male',
        ])->assertCreated()->assertJsonPath('relationship_label', 'Father');

        // Add a standalone person.
        $this->postJson("/api/v1/trees/{$tree['id']}/people", [
            'given_name' => 'Mary',
            'surname' => 'Lovelace',
            'sex' => 'female',
        ])->assertCreated();

        $graph = $this->getJson("/api/v1/trees/{$tree['id']}/graph")->assertOk();

        $graph->assertJsonCount(3, 'people')
            ->assertJsonCount(1, 'relationships')
            ->assertJsonPath('owner_person_id', $ownerPersonId)
            ->assertJsonPath('can_manage', true)
            ->assertJsonPath('access_level', 'owner');

        $this->assertSame('parent', $graph->json('relationships.0.type'));
    }

    public function test_people_can_be_linked_and_a_person_updated(): void
    {
        $this->actingUser();
        $tree = $this->postJson('/api/v1/trees', ['name' => 'Link Tree', 'privacy' => 'private'])->json('data');

        $a = $this->postJson("/api/v1/trees/{$tree['id']}/people", [
            'given_name' => 'Ann', 'surname' => 'Smith', 'sex' => 'female',
        ])->json('data');
        $b = $this->postJson("/api/v1/trees/{$tree['id']}/people", [
            'given_name' => 'Bob', 'surname' => 'Smith', 'sex' => 'male',
        ])->json('data');

        $this->postJson("/api/v1/trees/{$tree['id']}/relationships", [
            'person_id' => $a['id'],
            'related_person_id' => $b['id'],
            'type' => 'spouse',
        ])->assertCreated()->assertJsonPath('data.type', 'spouse');

        $this->patchJson("/api/v1/people/{$a['id']}", [
            'given_name' => 'Annie', 'surname' => 'Smith', 'sex' => 'female',
        ])->assertOk()->assertJsonPath('data.given_name', 'Annie');
    }

    public function test_owner_person_can_be_changed_to_another_person_in_the_tree(): void
    {
        $this->actingUser();
        $tree = $this->postJson('/api/v1/trees', ['name' => 'Home Tree', 'privacy' => 'private'])->json('data');

        $me = $this->postJson("/api/v1/trees/{$tree['id']}/people", [
            'given_name' => 'Real', 'surname' => 'Me', 'sex' => 'male',
        ])->json('data');

        $this->patchJson("/api/v1/trees/{$tree['id']}", ['owner_person_id' => $me['id']])
            ->assertOk()
            ->assertJsonPath('data.owner_person_id', $me['id']);

        $this->assertDatabaseHas('family_trees', ['id' => $tree['id'], 'owner_person_id' => $me['id']]);
    }

    public function test_owner_person_cannot_be_set_to_a_person_from_another_tree(): void
    {
        $this->actingUser();
        $tree = $this->postJson('/api/v1/trees', ['name' => 'Tree A', 'privacy' => 'private'])->json('data');
        $other = $this->postJson('/api/v1/trees', ['name' => 'Tree B', 'privacy' => 'private'])->json('data');

        $outsider = $this->postJson("/api/v1/trees/{$other['id']}/people", [
            'given_name' => 'Out', 'surname' => 'Sider', 'sex' => 'female',
        ])->json('data');

        $this->patchJson("/api/v1/trees/{$tree['id']}", ['owner_person_id' => $outsider['id']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('owner_person_id');
    }

    public function test_a_stranger_cannot_read_anothers_tree(): void
    {
        $this->actingUser();
        $tree = $this->postJson('/api/v1/trees', ['name' => 'Private Tree', 'privacy' => 'private'])->json('data');

        // Switch to a different user.
        $stranger = User::factory()->create();
        app(TreeAccessService::class)->assignDefaultRole($stranger);
        Sanctum::actingAs($stranger);

        $this->getJson("/api/v1/trees/{$tree['id']}/graph")->assertForbidden();
        $this->getJson('/api/v1/trees')->assertOk()->assertJsonCount(0, 'data');
    }
}
