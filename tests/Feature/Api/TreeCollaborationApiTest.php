<?php

namespace Tests\Feature\Api;

use App\Models\FamilyTree;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TreeCollaborationApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(?string $email = null): User
    {
        $user = User::factory()->create($email ? ['email' => $email] : []);
        app(TreeAccessService::class)->assignDefaultRole($user);

        return $user;
    }

    private function makeTree(User $owner, string $privacy = 'private'): FamilyTree
    {
        Sanctum::actingAs($owner);
        $tree = $this->postJson('/api/v1/trees', ['name' => 'My Tree', 'privacy' => $privacy])->json('data');

        return FamilyTree::find($tree['id']);
    }

    public function test_inviting_an_existing_user_grants_immediate_access(): void
    {
        $owner = $this->makeUser();
        $invitee = $this->makeUser('friend@example.com');
        $tree = $this->makeTree($owner);

        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/trees/{$tree->id}/invitations", [
            'email' => 'friend@example.com',
            'access_level' => 'observer',
        ])->assertCreated()->assertJsonPath('status', 'accepted');

        // Invitee now shows up as a member and can view the tree.
        $members = $this->getJson("/api/v1/trees/{$tree->id}/members")->json('data');
        $emails = array_column($members, 'email');
        $this->assertContains('friend@example.com', $emails);

        Sanctum::actingAs($invitee);
        $this->getJson("/api/v1/trees/{$tree->id}")->assertOk();
    }

    public function test_member_access_level_can_be_changed_and_revoked(): void
    {
        $owner = $this->makeUser();
        $invitee = $this->makeUser('member@example.com');
        $tree = $this->makeTree($owner);

        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/trees/{$tree->id}/invitations", ['email' => 'member@example.com', 'access_level' => 'observer'])
            ->assertCreated();

        // Promote to manager.
        $this->patchJson("/api/v1/trees/{$tree->id}/members/{$invitee->id}", ['access_level' => 'manager'])
            ->assertOk()->assertJsonPath('access_level', 'manager');

        // Remove entirely.
        $this->deleteJson("/api/v1/trees/{$tree->id}/members/{$invitee->id}")->assertOk();

        Sanctum::actingAs($invitee);
        $this->getJson("/api/v1/trees/{$tree->id}")->assertForbidden();
    }

    public function test_owner_cannot_be_removed(): void
    {
        $owner = $this->makeUser();
        $tree = $this->makeTree($owner);

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/v1/trees/{$tree->id}/members/{$owner->id}")->assertStatus(422);
    }

    public function test_membership_request_can_be_submitted_and_approved(): void
    {
        $owner = $this->makeUser();
        $seeker = $this->makeUser('seeker@example.com');
        $tree = $this->makeTree($owner, 'public');

        // Seeker requests access.
        Sanctum::actingAs($seeker);
        $requestId = $this->postJson("/api/v1/trees/{$tree->id}/membership-requests", ['note' => 'Related to the Smiths'])
            ->assertCreated()->json('id');

        // Owner sees it and approves.
        Sanctum::actingAs($owner);
        $this->getJson("/api/v1/trees/{$tree->id}/membership-requests")->assertOk()->assertJsonCount(1, 'data');
        $this->patchJson("/api/v1/membership-requests/{$requestId}", ['decision' => 'approved'])
            ->assertOk()->assertJsonPath('status', 'approved');

        // Seeker now has access.
        Sanctum::actingAs($seeker);
        $this->getJson("/api/v1/trees/{$tree->id}")->assertOk();
    }

    public function test_non_managers_cannot_manage_collaboration(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $tree = $this->makeTree($owner);

        Sanctum::actingAs($stranger);
        $this->getJson("/api/v1/trees/{$tree->id}/invitations")->assertForbidden();
        $this->postJson("/api/v1/trees/{$tree->id}/invitations", ['email' => 'x@example.com', 'access_level' => 'observer'])
            ->assertForbidden();
    }
}
