<?php

namespace Tests\Feature\Api;

use App\Models\FamilyTree;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileInteractionApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();
        app(TreeAccessService::class)->assignDefaultRole($user);

        return $user;
    }

    /**
     * Create a Global-Tree-enabled tree owned by $owner and return [treeId, personId].
     *
     * @return array{0:string,1:string}
     */
    private function globalTreeWithPerson(User $owner): array
    {
        Sanctum::actingAs($owner);
        $tree = $this->postJson('/api/v1/trees', ['name' => 'Public Tree', 'privacy' => 'public'])->json('data');
        FamilyTree::find($tree['id'])->update(['global_tree_enabled' => true]);

        return [$tree['id'], $tree['owner_person_id']];
    }

    public function test_watch_can_be_toggled(): void
    {
        $owner = $this->makeUser();
        [, $personId] = $this->globalTreeWithPerson($owner);

        $this->postJson("/api/v1/people/{$personId}/watch")->assertOk()->assertJsonPath('watching', true);
        $this->postJson("/api/v1/people/{$personId}/watch")->assertOk()->assertJsonPath('watching', false);
    }

    public function test_discussions_can_be_posted_listed_and_removed(): void
    {
        $owner = $this->makeUser();
        [, $personId] = $this->globalTreeWithPerson($owner);

        $id = $this->postJson("/api/v1/people/{$personId}/discussions", ['body' => 'Hello!'])
            ->assertCreated()->json('id');

        $this->getJson("/api/v1/people/{$personId}/discussions")->assertOk()->assertJsonCount(1, 'data');

        $this->deleteJson("/api/v1/discussions/{$id}")->assertOk();
        $this->getJson("/api/v1/people/{$personId}/discussions")->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_photo_requests_can_be_made_and_updated(): void
    {
        $owner = $this->makeUser();
        [, $personId] = $this->globalTreeWithPerson($owner);

        $id = $this->postJson("/api/v1/people/{$personId}/photo-requests", ['notes' => 'Please'])
            ->assertCreated()->json('id');

        $this->patchJson("/api/v1/photo-requests/{$id}", ['status' => 'fulfilled'])
            ->assertOk()->assertJsonPath('status', 'fulfilled');
    }

    public function test_a_claim_can_be_submitted_and_reviewed_by_the_owner(): void
    {
        $owner = $this->makeUser();
        $claimant = $this->makeUser();
        [, $personId] = $this->globalTreeWithPerson($owner);

        // Claimant submits.
        Sanctum::actingAs($claimant);
        $claimId = $this->postJson("/api/v1/people/{$personId}/claims", ['message' => 'This is me'])
            ->assertCreated()->json('id');

        // Owner reviews.
        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/claims')->assertOk()->assertJsonCount(1, 'data');
        $this->patchJson("/api/v1/claims/{$claimId}/review", ['decision' => 'approved'])
            ->assertOk()->assertJsonPath('status', 'approved');
    }

    public function test_interactions_are_blocked_outside_the_global_tree(): void
    {
        $owner = $this->makeUser();
        Sanctum::actingAs($owner);
        $tree = $this->postJson('/api/v1/trees', ['name' => 'Private', 'privacy' => 'private'])->json('data');

        $this->postJson("/api/v1/people/{$tree['owner_person_id']}/watch")->assertForbidden();
    }
}
