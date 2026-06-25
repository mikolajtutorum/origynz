<?php

namespace Tests\Feature\Api;

use App\Enums\SiteRole;
use App\Models\FamilyTree;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $access = app(TreeAccessService::class);
        $access->ensureBaseRecordsExist();
        setPermissionsTeamId(TreeAccessService::SITE_TEAM_ID);
        $user->assignRole(SiteRole::SuperAdmin->value);
        Sanctum::actingAs($user);

        return $user;
    }

    private function member(): User
    {
        $user = User::factory()->create();
        app(TreeAccessService::class)->assignDefaultRole($user);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_non_admins_are_forbidden(): void
    {
        $this->member();
        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
        $this->getJson('/api/v1/admin/users')->assertForbidden();
    }

    public function test_admin_dashboard_returns_stats(): void
    {
        $this->superAdmin();
        FamilyTree::factory()->count(2)->create();

        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['users', 'trees', 'people', 'recent_users', 'recent_trees']);
    }

    public function test_admin_can_list_users_and_trees(): void
    {
        $this->superAdmin();
        FamilyTree::factory()->create();

        $this->getJson('/api/v1/admin/users')->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->getJson('/api/v1/admin/trees')->assertOk()->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_toggle_and_delete_a_tree(): void
    {
        $this->superAdmin();
        $tree = FamilyTree::factory()->create(['global_tree_enabled' => false]);

        $this->patchJson("/api/v1/admin/trees/{$tree->id}/global")
            ->assertOk()
            ->assertJsonPath('global_tree_enabled', true);

        $this->deleteJson("/api/v1/admin/trees/{$tree->id}")->assertOk();
        $this->assertModelMissing($tree);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->superAdmin();
        $this->deleteJson("/api/v1/admin/users/{$admin->id}")->assertStatus(422);
    }
}
