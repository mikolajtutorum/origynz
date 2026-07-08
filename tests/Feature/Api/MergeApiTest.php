<?php

namespace Tests\Feature\Api;

use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MergeApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();
        app(TreeAccessService::class)->assignDefaultRole($user);

        return $user;
    }

    private function makeTree(User $owner): FamilyTree
    {
        Sanctum::actingAs($owner);
        $tree = $this->postJson('/api/v1/trees', ['name' => 'My Tree', 'privacy' => 'private'])->json('data');

        return FamilyTree::find($tree['id']);
    }

    public function test_scan_finds_within_tree_duplicates_and_merge_absorbs_one(): void
    {
        $owner = $this->makeUser();
        $tree = $this->makeTree($owner);

        // Two near-identical people that should score above threshold.
        $a = Person::factory()->for($tree, 'familyTree')->create([
            'given_name' => 'John', 'surname' => 'Smith', 'sex' => 'male',
            'birth_date' => '1900-01-01', 'birth_place' => 'Boston',
        ]);
        $b = Person::factory()->for($tree, 'familyTree')->create([
            'given_name' => 'John', 'surname' => 'Smith', 'sex' => 'male',
            'birth_date' => '1900-01-01', 'birth_place' => 'Boston',
        ]);

        Sanctum::actingAs($owner);

        // Scan → creates a candidate.
        $this->postJson("/api/v1/trees/{$tree->id}/merge-candidates/scan")
            ->assertOk()
            ->assertJsonPath('created', 1);

        // List → one candidate covering both people.
        $candidates = $this->getJson("/api/v1/trees/{$tree->id}/merge-candidates")
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $candidates);
        $candidateId = $candidates[0]['id'];

        // Preview → returns comparable fields.
        $this->getJson("/api/v1/merge-candidates/{$candidateId}/preview")
            ->assertOk()
            ->assertJsonPath('person_a.display_name', fn ($n) => str_contains($n, 'Smith'))
            ->assertJsonStructure(['fields' => [['field', 'label', 'value_a', 'value_b', 'conflict', 'suggested']]]);

        // Merge (keep person A as surviving).
        $this->postJson("/api/v1/merge-candidates/{$candidateId}/merge", ['surviving' => 'a'])
            ->assertOk()
            ->assertJsonPath('surviving_person_id', $a->id)
            ->assertJsonPath('absorbed_person_id', $b->id);

        $this->assertEquals($a->id, $b->fresh()->merged_into_id);
        $this->assertNull($a->fresh()->merged_into_id);
    }

    public function test_dismiss_marks_candidate_resolved(): void
    {
        $owner = $this->makeUser();
        $tree = $this->makeTree($owner);

        Person::factory()->for($tree, 'familyTree')->create([
            'given_name' => 'Mary', 'surname' => 'Jones', 'sex' => 'female',
            'birth_date' => '1920-06-01', 'birth_place' => 'York',
        ]);
        Person::factory()->for($tree, 'familyTree')->create([
            'given_name' => 'Mary', 'surname' => 'Jones', 'sex' => 'female',
            'birth_date' => '1920-06-01', 'birth_place' => 'York',
        ]);

        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/trees/{$tree->id}/merge-candidates/scan")->assertOk();
        $candidateId = $this->getJson("/api/v1/trees/{$tree->id}/merge-candidates")->json('data.0.id');

        $this->postJson("/api/v1/merge-candidates/{$candidateId}/dismiss")
            ->assertOk()
            ->assertJsonPath('status', 'dismissed');

        // No longer listed as pending.
        $this->getJson("/api/v1/trees/{$tree->id}/merge-candidates")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_merge_requires_manage_access(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $tree = $this->makeTree($owner);

        Person::factory()->for($tree, 'familyTree')->create([
            'given_name' => 'Anne', 'surname' => 'Bell', 'sex' => 'female', 'birth_date' => '1930-01-01', 'birth_place' => 'Leeds',
        ]);
        Person::factory()->for($tree, 'familyTree')->create([
            'given_name' => 'Anne', 'surname' => 'Bell', 'sex' => 'female', 'birth_date' => '1930-01-01', 'birth_place' => 'Leeds',
        ]);

        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/trees/{$tree->id}/merge-candidates/scan")->assertOk();
        $candidateId = $this->getJson("/api/v1/trees/{$tree->id}/merge-candidates")->json('data.0.id');

        Sanctum::actingAs($stranger);
        $this->getJson("/api/v1/merge-candidates/{$candidateId}/preview")->assertForbidden();
        $this->postJson("/api/v1/merge-candidates/{$candidateId}/merge", ['surviving' => 'a'])->assertForbidden();
    }
}
