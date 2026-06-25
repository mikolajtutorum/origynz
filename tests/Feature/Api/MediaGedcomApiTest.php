<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaGedcomApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        app(TreeAccessService::class)->assignDefaultRole($user);
        Sanctum::actingAs($user);

        return $user;
    }

    private function createTree(): array
    {
        return $this->postJson('/api/v1/trees', ['name' => 'Media Tree', 'privacy' => 'private'])->json('data');
    }

    public function test_a_user_can_upload_and_list_media(): void
    {
        Storage::fake('local');
        $this->actingUser();
        $tree = $this->createTree();

        $upload = $this->postJson("/api/v1/trees/{$tree['id']}/media", [
            'title' => 'Family photo',
            'media_file' => UploadedFile::fake()->create('photo.jpg', 50, 'image/jpeg'),
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Family photo')
            ->assertJsonPath('data.is_image', true);

        $this->assertNotNull($upload->json('data.preview_url'));

        $this->getJson("/api/v1/trees/{$tree['id']}/media")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/media')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_media_file_requires_a_valid_signature(): void
    {
        Storage::fake('local');
        $this->actingUser();
        $tree = $this->createTree();

        $previewUrl = $this->postJson("/api/v1/trees/{$tree['id']}/media", [
            'title' => 'Signed photo',
            'media_file' => UploadedFile::fake()->create('p.jpg', 50, 'image/jpeg'),
        ])->json('data.preview_url');

        // The signed URL works; tampering with it fails.
        $this->get($previewUrl)->assertOk();
        $this->get(preg_replace('/signature=[a-f0-9]+/', 'signature=deadbeef', $previewUrl))->assertForbidden();
    }

    public function test_a_tree_can_be_exported_as_gedcom(): void
    {
        $this->actingUser();
        $tree = $this->createTree();

        $response = $this->get("/api/v1/trees/{$tree['id']}/gedcom/export");
        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('0 HEAD', $response->getContent());
    }

    public function test_gedcom_import_accepts_a_file_and_returns_an_import_id(): void
    {
        Storage::fake('local');
        $this->actingUser();

        $gedcom = "0 HEAD\n1 SOUR Test\n1 GEDC\n2 VERS 5.5.1\n1 CHAR UTF-8\n0 @I1@ INDI\n1 NAME John /Smith/\n1 SEX M\n0 TRLR\n";

        $this->postJson('/api/v1/gedcom/import', [
            'tree_name' => 'Imported',
            'gedcom_file' => UploadedFile::fake()->createWithContent('tree.ged', $gedcom),
        ])->assertOk()
            ->assertJsonStructure(['import_id', 'tree_id']);
    }

    public function test_relationship_calculator_rejects_people_outside_the_global_tree(): void
    {
        $this->actingUser();
        $tree = $this->createTree();

        $a = $this->postJson("/api/v1/trees/{$tree['id']}/people", ['given_name' => 'A', 'surname' => 'X', 'sex' => 'male'])->json('data');
        $b = $this->postJson("/api/v1/trees/{$tree['id']}/people", ['given_name' => 'B', 'surname' => 'X', 'sex' => 'female'])->json('data');

        $this->postJson('/api/v1/global-tree/relationship', [
            'person_a_id' => $a['id'],
            'person_b_id' => $b['id'],
        ])->assertForbidden();
    }
}
