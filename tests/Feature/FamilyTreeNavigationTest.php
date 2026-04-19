<?php

namespace Tests\Feature;

use App\Enums\TreeAccessLevel;
use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyTreeNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_visit_manage_trees_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trees.manage'));

        $response->assertOk();
        $response->assertSeeText('Manage Trees');
    }

    public function test_authenticated_users_can_visit_import_gedcom_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trees.import.index'));

        $response->assertOk();
        $response->assertSeeText('Import GEDCOM');
    }

    public function test_my_family_tree_route_redirects_to_the_users_first_tree(): void
    {
        $user = User::factory()->create();
        $firstTree = FamilyTree::factory()->for($user)->create();
        $secondTree = FamilyTree::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('trees.first'));

        $response->assertRedirect(route('trees.show', $firstTree));
        $this->assertNotSame($secondTree->id, $firstTree->id);
    }

    public function test_my_family_tree_route_redirects_to_manage_page_when_user_has_no_trees(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trees.first'));

        $response->assertRedirect(route('trees.manage'));
    }

    public function test_new_tree_creation_maps_account_profile_data_to_owner_person_and_defaults_home_region(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Ewa Rivera',
            'first_name' => 'Maria',
            'middle_name' => 'Ewa',
            'last_name' => 'Rivera',
            'birth_date' => '1985-04-12',
            'country_of_residence' => 'Spain',
            'preferred_locale' => 'en',
        ]);

        $response = $this->actingAs($user)->post(route('trees.store'), [
            'name' => 'Rivera Family',
            'description' => 'Family line',
            'home_region' => '',
            'privacy' => 'private',
        ]);

        $tree = FamilyTree::query()->where('name', 'Rivera Family')->firstOrFail();
        $ownerPerson = Person::query()->where('family_tree_id', $tree->id)->firstOrFail();

        $response->assertRedirect(route('trees.show', ['tree' => $tree, 'focus' => $ownerPerson->id]));
        $this->assertSame('Spain', $tree->home_region);
        $this->assertSame('Maria', $ownerPerson->given_name);
        $this->assertSame('Ewa', $ownerPerson->middle_name);
        $this->assertSame('Rivera', $ownerPerson->surname);
        $this->assertSame('1985-04-12', optional($ownerPerson->birth_date)->format('Y-m-d'));
    }

    public function test_tree_observer_can_open_a_shared_tree(): void
    {
        $owner = User::factory()->create();
        $observer = User::factory()->create();
        $tree = FamilyTree::factory()->for($owner)->create(['name' => 'Rivera Family']);

        app(TreeAccessService::class)->grantTreeAccess($observer, $tree, TreeAccessLevel::Observer);

        $response = $this->actingAs($observer)->get(route('trees.show', $tree));

        $response->assertOk();
        $response->assertSeeText('Rivera Family');
    }
}
