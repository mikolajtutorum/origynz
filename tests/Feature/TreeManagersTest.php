<?php

namespace Tests\Feature;

use App\Enums\TreeAccessLevel;
use App\Models\FamilyTree;
use App\Models\FamilyTreeMembershipRequest;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreeManagersTest extends TestCase
{
    use RefreshDatabase;

    public function test_tree_owner_can_view_tree_managers_page(): void
    {
        $user = User::factory()->create(['name' => 'Alice Rivera']);
        $tree = FamilyTree::factory()->for($user)->create(['name' => 'Rivera Tree']);

        $response = $this->actingAs($user)->get(route('trees.managers.show', $tree));

        $response->assertOk();
        $response->assertSeeText('Rivera Tree');
        $response->assertSeeText('Alice Rivera');
        $response->assertSeeText('Members');
    }

    public function test_tree_owner_can_create_invitation_by_email(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('trees.managers.invitations.store', $tree), [
            'email' => 'cousin@example.com',
            'access_level' => 'observer',
        ]);

        $response->assertRedirect(route('trees.managers.show', $tree));
        $this->assertDatabaseHas('family_tree_invitations', [
            'family_tree_id' => $tree->id,
            'email' => 'cousin@example.com',
            'access_level' => 'observer',
            'status' => 'pending',
        ]);
    }

    public function test_tree_owner_can_review_membership_request(): void
    {
        $user = User::factory()->create(['name' => 'Tree Owner']);
        $tree = FamilyTree::factory()->for($user)->create();
        $requestModel = FamilyTreeMembershipRequest::query()->create([
            'family_tree_id' => $tree->id,
            'requester_name' => 'Ben Carter',
            'requester_email' => 'ben@example.com',
            'note' => 'I help maintain this branch.',
        ]);

        $response = $this->actingAs($user)->patch(route('trees.managers.requests.review', [$tree, $requestModel]), [
            'decision' => 'approved',
        ]);

        $response->assertRedirect(route('trees.managers.show', $tree));
        $this->assertDatabaseHas('family_tree_membership_requests', [
            'id' => $requestModel->id,
            'status' => 'approved',
            'reviewed_by' => $user->id,
        ]);
    }

    public function test_inviting_an_existing_user_grants_tree_access_immediately(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'cousin@example.com']);
        $tree = FamilyTree::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->post(route('trees.managers.invitations.store', $tree), [
            'email' => 'cousin@example.com',
            'access_level' => 'manager',
        ]);

        $response->assertRedirect(route('trees.managers.show', $tree));
        $this->assertSame(
            TreeAccessLevel::Manager,
            app(TreeAccessService::class)->getTreeAccessLevel($invitee, $tree),
        );
        $this->assertDatabaseHas('family_tree_invitations', [
            'family_tree_id' => $tree->id,
            'email' => 'cousin@example.com',
            'access_level' => 'manager',
            'status' => 'accepted',
        ]);
    }

    public function test_tree_observer_cannot_access_manager_screen(): void
    {
        $owner = User::factory()->create();
        $observer = User::factory()->create();
        $tree = FamilyTree::factory()->for($owner)->create();

        app(TreeAccessService::class)->grantTreeAccess($observer, $tree, TreeAccessLevel::Observer);

        $response = $this->actingAs($observer)->get(route('trees.managers.show', $tree));

        $response->assertForbidden();
    }
}
