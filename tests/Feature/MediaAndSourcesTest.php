<?php

namespace Tests\Feature;

use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAndSourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_media_for_a_person(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();
        $person = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('trees.media.store', $tree), [
            'person_id' => $person->id,
            'title' => 'Portrait',
            'description' => 'Studio portrait',
            'media_file' => UploadedFile::fake()->image('portrait.jpg'),
            'return_to' => route('trees.show', ['tree' => $tree, 'focus' => $person->id]),
        ]);

        $response->assertRedirect(route('trees.show', ['tree' => $tree, 'focus' => $person->id]));
        $this->assertDatabaseHas('media_items', [
            'family_tree_id' => $tree->id,
            'person_id' => $person->id,
            'title' => 'Portrait',
        ]);
    }

    public function test_user_can_add_source_citation_for_a_person(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();
        $person = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('trees.sources.store', $tree), [
            'person_id' => $person->id,
            'title' => 'Birth certificate',
            'author' => 'Civil Registry',
            'page' => 'Entry 12',
            'quotation' => 'Born on 12 May 1970.',
            'source_quality' => 3,
            'citation_quality' => 3,
            'return_to' => route('trees.show', ['tree' => $tree, 'focus' => $person->id]),
        ]);

        $response->assertRedirect(route('trees.show', ['tree' => $tree, 'focus' => $person->id]));
        $this->assertDatabaseHas('sources', [
            'family_tree_id' => $tree->id,
            'title' => 'Birth certificate',
        ]);
        $this->assertDatabaseHas('person_source_citations', [
            'person_id' => $person->id,
            'page' => 'Entry 12',
            'quality' => 3,
        ]);
    }
}
