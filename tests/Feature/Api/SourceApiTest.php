<?php

namespace Tests\Feature\Api;

use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SourceApiTest extends TestCase
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
        $tree = $this->postJson('/api/v1/trees', ['name' => 'Sourced Tree', 'privacy' => 'private'])->json('data');

        return FamilyTree::find($tree['id']);
    }

    public function test_a_source_can_be_created_cited_and_removed(): void
    {
        $owner = $this->makeUser();
        $tree = $this->makeTree($owner);
        $person = Person::factory()->for($tree, 'familyTree')->create();

        Sanctum::actingAs($owner);

        // Create a source.
        $sourceId = $this->postJson("/api/v1/trees/{$tree->id}/sources", [
            'title' => '1911 Census of England',
            'author' => 'GRO',
            'repository' => 'The National Archives',
            'url' => 'https://example.com/census',
        ])->assertCreated()->json('id');

        $this->getJson("/api/v1/trees/{$tree->id}/sources")->assertOk()->assertJsonCount(1, 'data');

        // Cite it on the person.
        $citationId = $this->postJson("/api/v1/people/{$person->id}/citations", [
            'source_id' => $sourceId,
            'page' => 'District 4, p. 12',
            'quotation' => 'John Smith, head, age 41',
            'quality' => 3,
        ])->assertCreated()->json('id');

        $this->getJson("/api/v1/people/{$person->id}/citations")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source_title', '1911 Census of England');

        // Remove the citation.
        $this->deleteJson("/api/v1/citations/{$citationId}")->assertOk();
        $this->getJson("/api/v1/people/{$person->id}/citations")->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_citation_source_must_belong_to_the_same_tree(): void
    {
        $owner = $this->makeUser();
        $treeA = $this->makeTree($owner);
        $treeB = $this->makeTree($owner);
        $person = Person::factory()->for($treeA, 'familyTree')->create();

        Sanctum::actingAs($owner);
        $foreignSourceId = $this->postJson("/api/v1/trees/{$treeB->id}/sources", ['title' => 'Other tree source'])
            ->json('id');

        $this->postJson("/api/v1/people/{$person->id}/citations", ['source_id' => $foreignSourceId])
            ->assertStatus(422);
    }

    public function test_observers_cannot_create_sources(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $tree = $this->makeTree($owner);

        Sanctum::actingAs($stranger);
        $this->postJson("/api/v1/trees/{$tree->id}/sources", ['title' => 'Nope'])->assertForbidden();
    }
}
