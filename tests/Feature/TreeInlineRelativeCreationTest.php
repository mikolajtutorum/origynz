<?php

namespace Tests\Feature;

use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreeInlineRelativeCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_parent_from_the_chart(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();
        $anchor = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'surname' => 'Rivera',
        ]);

        $response = $this->actingAs($user)->post(route('trees.people.store-relative', $tree), [
            'anchor_person_id' => $anchor->id,
            'relation_kind' => 'parent',
            'given_name' => 'Maria',
            'surname' => 'Rivera',
            'sex' => 'female',
            'return_to' => route('trees.show', ['tree' => $tree, 'focus' => $anchor->id]),
        ]);

        $response->assertRedirect(route('trees.show', ['tree' => $tree, 'focus' => $anchor->id]));

        $this->assertDatabaseHas('people', [
            'family_tree_id' => $tree->id,
            'given_name' => 'Maria',
            'surname' => 'Rivera',
        ]);

        $parent = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Maria')
            ->firstOrFail();

        $this->assertDatabaseHas('person_relationships', [
            'family_tree_id' => $tree->id,
            'person_id' => $parent->id,
            'related_person_id' => $anchor->id,
            'type' => 'parent',
        ]);
    }

    public function test_user_can_create_a_father_by_role_from_the_chart(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();
        $anchor = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'surname' => 'Rivera',
        ]);

        $response = $this->actingAs($user)->post(route('trees.people.store-relative', $tree), [
            'anchor_person_id' => $anchor->id,
            'relation_role' => 'father',
            'given_name' => 'Carlos',
            'surname' => 'Rivera',
            'sex' => 'male',
            'return_to' => route('trees.show', ['tree' => $tree, 'focus' => $anchor->id]),
        ]);

        $response->assertRedirect(route('trees.show', ['tree' => $tree, 'focus' => $anchor->id]));

        $father = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Carlos')
            ->firstOrFail();

        $this->assertDatabaseHas('person_relationships', [
            'family_tree_id' => $tree->id,
            'person_id' => $father->id,
            'related_person_id' => $anchor->id,
            'type' => 'parent',
        ]);
    }

    public function test_user_can_create_a_sibling_by_role_from_the_chart(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();
        $parent = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Maria',
            'surname' => 'Rivera',
        ]);
        $anchor = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'surname' => 'Rivera',
        ]);

        $tree->relationships()->create([
            'person_id' => $parent->id,
            'related_person_id' => $anchor->id,
            'type' => 'parent',
        ]);

        $response = $this->actingAs($user)->post(route('trees.people.store-relative', $tree), [
            'anchor_person_id' => $anchor->id,
            'relation_role' => 'sister',
            'given_name' => 'Elena',
            'surname' => 'Rivera',
            'sex' => 'female',
            'return_to' => route('trees.show', ['tree' => $tree, 'focus' => $anchor->id]),
        ]);

        $response->assertRedirect(route('trees.show', ['tree' => $tree, 'focus' => $anchor->id]));

        $sibling = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Elena')
            ->firstOrFail();

        $this->assertDatabaseHas('person_relationships', [
            'family_tree_id' => $tree->id,
            'person_id' => $parent->id,
            'related_person_id' => $sibling->id,
            'type' => 'parent',
        ]);
    }

    public function test_tree_sidebar_shows_relationship_to_owner(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();
        $owner = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $mother = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Maria',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);

        $tree->update(['owner_person_id' => $owner->id]);
        $tree->relationships()->create([
            'person_id' => $mother->id,
            'related_person_id' => $owner->id,
            'type' => 'parent',
        ]);

        $response = $this->actingAs($user)->get(route('trees.show', [
            'tree' => $tree,
            'focus' => $mother->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Your mother');
    }

    public function test_tree_sidebar_shows_cousin_relationship_to_owner(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $owner = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $ownerMother = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Maria',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);
        $grandfather = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Jose',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $aunt = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Elena',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);
        $cousin = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Sofia',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);

        $tree->update(['owner_person_id' => $owner->id]);
        $tree->relationships()->create([
            'person_id' => $ownerMother->id,
            'related_person_id' => $owner->id,
            'type' => 'parent',
        ]);
        $tree->relationships()->create([
            'person_id' => $grandfather->id,
            'related_person_id' => $ownerMother->id,
            'type' => 'parent',
        ]);
        $tree->relationships()->create([
            'person_id' => $grandfather->id,
            'related_person_id' => $aunt->id,
            'type' => 'parent',
        ]);
        $tree->relationships()->create([
            'person_id' => $aunt->id,
            'related_person_id' => $cousin->id,
            'type' => 'parent',
        ]);

        $response = $this->actingAs($user)->get(route('trees.show', [
            'tree' => $tree,
            'focus' => $cousin->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Your first cousin');
    }

    public function test_tree_sidebar_shows_sibling_in_law_relationship_to_owner(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $owner = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $spouse = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Jamie',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);
        $spouseBrother = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Chris',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $sharedParent = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Pat',
            'surname' => 'Rivera',
            'sex' => 'unknown',
        ]);

        $tree->update(['owner_person_id' => $owner->id]);
        $tree->relationships()->create([
            'person_id' => $owner->id,
            'related_person_id' => $spouse->id,
            'type' => 'spouse',
        ]);
        $tree->relationships()->create([
            'person_id' => $sharedParent->id,
            'related_person_id' => $spouse->id,
            'type' => 'parent',
        ]);
        $tree->relationships()->create([
            'person_id' => $sharedParent->id,
            'related_person_id' => $spouseBrother->id,
            'type' => 'parent',
        ]);

        $response = $this->actingAs($user)->get(route('trees.show', [
            'tree' => $tree,
            'focus' => $spouseBrother->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Your brother-in-law');
    }

    public function test_tree_sidebar_shows_stepmother_relationship_to_owner(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $owner = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $stepmother = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Jamie',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);

        $tree->update(['owner_person_id' => $owner->id]);
        $tree->relationships()->create([
            'person_id' => $stepmother->id,
            'related_person_id' => $owner->id,
            'type' => 'parent',
            'subtype' => 'step',
        ]);

        $response = $this->actingAs($user)->get(route('trees.show', [
            'tree' => $tree,
            'focus' => $stepmother->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Your stepmother');
    }

    public function test_tree_sidebar_shows_adopted_child_relationship_to_owner(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $owner = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $adoptedDaughter = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Lena',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);

        $tree->update(['owner_person_id' => $owner->id]);
        $tree->relationships()->create([
            'person_id' => $owner->id,
            'related_person_id' => $adoptedDaughter->id,
            'type' => 'parent',
            'subtype' => 'adoptive',
        ]);

        $response = $this->actingAs($user)->get(route('trees.show', [
            'tree' => $tree,
            'focus' => $adoptedDaughter->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Your adopted daughter');
    }

    public function test_tree_sidebar_shows_half_sister_relationship_to_owner(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $owner = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $ownerMother = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Maria',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);
        $ownerFather = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Carlos',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $halfSister = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Eva',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);

        $tree->update(['owner_person_id' => $owner->id]);
        $tree->relationships()->createMany([
            [
                'person_id' => $ownerMother->id,
                'related_person_id' => $owner->id,
                'type' => 'parent',
            ],
            [
                'person_id' => $ownerFather->id,
                'related_person_id' => $owner->id,
                'type' => 'parent',
            ],
            [
                'person_id' => $ownerMother->id,
                'related_person_id' => $halfSister->id,
                'type' => 'parent',
            ],
        ]);

        $response = $this->actingAs($user)->get(route('trees.show', [
            'tree' => $tree,
            'focus' => $halfSister->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Your half-sister');
    }

    public function test_tree_sidebar_shows_stepsister_relationship_to_owner(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $owner = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $ownerMother = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Maria',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);
        $stepfather = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Daniel',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $stepsister = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Mia',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);
        $stepsisterMother = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Paula',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);

        $tree->update(['owner_person_id' => $owner->id]);
        $tree->relationships()->createMany([
            [
                'person_id' => $ownerMother->id,
                'related_person_id' => $owner->id,
                'type' => 'parent',
            ],
            [
                'person_id' => $ownerMother->id,
                'type' => 'spouse',
                'related_person_id' => $stepfather->id,
            ],
            [
                'person_id' => $stepfather->id,
                'related_person_id' => $stepsister->id,
                'type' => 'parent',
            ],
            [
                'person_id' => $stepsisterMother->id,
                'related_person_id' => $stepsister->id,
                'type' => 'parent',
            ],
        ]);

        $response = $this->actingAs($user)->get(route('trees.show', [
            'tree' => $tree,
            'focus' => $stepsister->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Your stepsister');
    }
}
