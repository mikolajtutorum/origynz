<?php

namespace Tests\Feature;

use App\Models\FamilyTree;
use App\Models\MediaItem;
use App\Models\Person;
use App\Models\PersonEvent;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GedcomTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_export_a_tree_as_gedcom(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create(['name' => 'Rivera Family']);
        $parent = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Maria',
            'middle_name' => null,
            'surname' => 'Rivera',
            'sex' => 'female',
            'birth_date' => '1970-05-12',
            'birth_date_text' => '12 MAY 1970',
            'headline' => null,
            'notes' => null,
        ]);
        $child = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Alex',
            'middle_name' => null,
            'surname' => 'Rivera',
            'sex' => 'male',
            'headline' => null,
            'notes' => null,
        ]);

        $tree->relationships()->create([
            'person_id' => $parent->id,
            'related_person_id' => $child->id,
            'type' => 'parent',
        ]);

        $source = $tree->sources()->create([
            'created_by' => $user->id,
            'title' => 'Birth register',
            'author' => 'Civil Registry',
            'quality' => 3,
        ]);

        $parent->sourceCitations()->create([
            'source_id' => $source->id,
            'page' => 'Entry 12',
            'quotation' => 'Born 12 May 1970',
            'quality' => 3,
        ]);

        $tree->mediaItems()->create([
            'person_id' => $parent->id,
            'uploaded_by' => $user->id,
            'title' => 'Portrait',
            'file_name' => 'portrait.jpg',
            'file_path' => 'media-items/portrait.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1234,
        ]);

        $response = $this->actingAs($user)->get(route('trees.gedcom.export', $tree));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/plain; charset=UTF-8');
        $response->assertSeeText('0 HEAD', false);
        $response->assertSeeText('2 VERS 7.0', false);
        $response->assertSeeText('1 NAME Maria /Rivera/', false);
        $response->assertSeeText('1 CHIL @I'.$child->id.'@', false);
        $response->assertSeeText('0 @S'.$source->id.'@ SOUR', false);
        $response->assertSeeText('0 @O1@ OBJE', false);
    }

    public function test_export_includes_parent_relationship_qualifiers(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create(['name' => 'Blended Family']);
        $father = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Daniel',
            'surname' => 'Rivera',
            'sex' => 'male',
        ]);
        $mother = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Paula',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);
        $child = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Mia',
            'surname' => 'Rivera',
            'sex' => 'female',
        ]);

        $tree->relationships()->create([
            'person_id' => $father->id,
            'related_person_id' => $mother->id,
            'type' => 'spouse',
        ]);
        $tree->relationships()->create([
            'person_id' => $father->id,
            'related_person_id' => $child->id,
            'type' => 'parent',
            'subtype' => 'step',
        ]);
        $tree->relationships()->create([
            'person_id' => $mother->id,
            'related_person_id' => $child->id,
            'type' => 'parent',
            'subtype' => 'adoptive',
        ]);

        $response = $this->actingAs($user)->get(route('trees.gedcom.export', $tree));

        $response->assertOk();
        $response->assertSeeText('2 _FREL step', false);
        $response->assertSeeText('2 _MREL adopted', false);
    }

    public function test_user_can_import_a_gedcom_file(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $gedcom = <<<GED
0 HEAD
1 GEDC
2 VERS 7.0
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Maria /Rivera/
1 SEX F
1 BIRT
2 DATE 12 MAY 1970
2 PLAC Warsaw, Poland
0 @I2@ INDI
1 NAME Alex /Rivera/
1 SEX M
1 OBJE @O1@
1 SOUR @S1@
2 PAGE Entry 12
2 QUAY 3
2 NOTE Birth noted in register
0 @S1@ SOUR
1 TITL Birth register
1 AUTH Civil Registry
1 QUAY 3
0 @O1@ OBJE
1 FILE portrait.jpg
2 FORM image/jpeg
1 TITL Portrait
0 @F1@ FAM
1 WIFE @I1@
1 CHIL @I2@
0 TRLR
GED;

        $file = UploadedFile::fake()->createWithContent('rivera.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.gedcom.import', $tree), [
            'gedcom_file' => $file,
        ]);

        $firstImportedPerson = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Maria')
            ->first();

        $this->assertNotNull($firstImportedPerson);
        $response->assertRedirect(route('trees.show', [
            'tree' => $tree,
            'focus' => $firstImportedPerson->id,
        ]));
        $this->assertDatabaseHas('people', [
            'family_tree_id' => $tree->id,
            'given_name' => 'Maria',
            'surname' => 'Rivera',
            'birth_date_text' => '12 MAY 1970',
        ]);
        $this->assertDatabaseHas('person_relationships', [
            'family_tree_id' => $tree->id,
            'type' => 'parent',
        ]);
        $this->assertDatabaseHas('sources', [
            'family_tree_id' => $tree->id,
            'title' => 'Birth register',
        ]);
        $this->assertDatabaseHas('media_items', [
            'family_tree_id' => $tree->id,
            'title' => 'Portrait',
            'external_reference' => 'portrait.jpg',
        ]);
    }

    public function test_import_preserves_parent_relationship_qualifiers_from_gedcom(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $gedcom = <<<GED
0 HEAD
1 GEDC
2 VERS 7.0
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Daniel /Rivera/
1 SEX M
0 @I2@ INDI
1 NAME Paula /Rivera/
1 SEX F
0 @I3@ INDI
1 NAME Mia /Rivera/
1 SEX F
1 FAMC @F1@
2 _PEDI adopted (mother)
0 @I4@ INDI
1 NAME Leo /Rivera/
1 SEX M
1 FAMC @F2@
2 PEDI ADOPTED
0 @F1@ FAM
1 HUSB @I1@
1 WIFE @I2@
1 CHIL @I3@
2 _FREL step
0 @F2@ FAM
1 HUSB @I1@
1 WIFE @I2@
1 CHIL @I4@
2 PEDI ADOPTED
0 TRLR
GED;

        $file = UploadedFile::fake()->createWithContent('qualified-relationships.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.gedcom.import', $tree), [
            'gedcom_file' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['import_id']);

        $daniel = Person::query()->where('family_tree_id', $tree->id)->where('given_name', 'Daniel')->firstOrFail();
        $paula = Person::query()->where('family_tree_id', $tree->id)->where('given_name', 'Paula')->firstOrFail();
        $mia = Person::query()->where('family_tree_id', $tree->id)->where('given_name', 'Mia')->firstOrFail();
        $leo = Person::query()->where('family_tree_id', $tree->id)->where('given_name', 'Leo')->firstOrFail();

        $this->assertDatabaseHas('person_relationships', [
            'family_tree_id' => $tree->id,
            'person_id' => $daniel->id,
            'related_person_id' => $mia->id,
            'type' => 'parent',
            'subtype' => 'step',
        ]);
        $this->assertDatabaseHas('person_relationships', [
            'family_tree_id' => $tree->id,
            'person_id' => $paula->id,
            'related_person_id' => $mia->id,
            'type' => 'parent',
            'subtype' => 'adoptive',
        ]);
        $this->assertDatabaseHas('person_relationships', [
            'family_tree_id' => $tree->id,
            'person_id' => $daniel->id,
            'related_person_id' => $leo->id,
            'type' => 'parent',
            'subtype' => 'adoptive',
        ]);
        $this->assertDatabaseHas('person_relationships', [
            'family_tree_id' => $tree->id,
            'person_id' => $paula->id,
            'related_person_id' => $leo->id,
            'type' => 'parent',
            'subtype' => 'adoptive',
        ]);
    }

    public function test_user_can_import_a_gedcom_file_from_the_import_page(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $gedcom = <<<GED
0 HEAD
1 GEDC
2 VERS 7.0
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Elena /Rivera/
1 SEX F
0 TRLR
GED;

        $file = UploadedFile::fake()->createWithContent('rivera-import.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.import.store'), [
            'tree_id' => $tree->id,
            'gedcom_file' => $file,
        ]);

        $firstImportedPerson = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Elena')
            ->first();

        $this->assertNotNull($firstImportedPerson);
        $response->assertRedirect(route('trees.show', [
            'tree' => $tree,
            'focus' => $firstImportedPerson->id,
        ]));
    }

    public function test_user_can_import_a_gedcom_file_and_create_a_tree_automatically(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Mikolaj',
            'last_name' => 'Florysiak',
            'country_of_residence' => 'Spain',
        ]);

        $gedcom = <<<GED
0 HEAD
1 GEDC
2 VERS 7.0
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Elena /Rivera/
1 SEX F
0 TRLR
GED;

        $file = UploadedFile::fake()->createWithContent('rivera-family-branch.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.import.store'), [
            'gedcom_file' => $file,
        ]);

        $tree = FamilyTree::query()->latest('id')->firstOrFail();
        $firstImportedPerson = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Elena')
            ->first();
        $ownerPerson = $tree->people()->find($tree->owner_person_id);

        $this->assertNotNull($firstImportedPerson);
        $this->assertNotNull($ownerPerson);
        $this->assertSame('rivera family branch', strtolower($tree->name));
        $this->assertSame('private', $tree->privacy);
        $this->assertSame('Spain', $tree->home_region);
        $this->assertSame($firstImportedPerson->id, $tree->owner_person_id);
        $this->assertSame('Elena', $ownerPerson->given_name);
        $this->assertSame('Rivera', $ownerPerson->surname);
        $this->assertSame(1, $tree->people()->count());
        $response->assertSessionHas('owner_selection_required', false);
        $response->assertRedirect(route('trees.show', [
            'tree' => $tree,
            'focus' => $firstImportedPerson->id,
        ]));
    }

    public function test_import_reuses_a_matching_imported_person_as_owner_instead_of_keeping_the_placeholder_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Mikolaj Florysiak',
            'first_name' => 'Mikolaj',
            'last_name' => 'Florysiak',
            'birth_date' => '1985-04-12',
        ]);
        $tree = FamilyTree::factory()->for($user)->create();
        $ownerPerson = $tree->people()->create([
            'created_by' => $user->id,
            'given_name' => 'Mikolaj',
            'surname' => 'Florysiak',
            'sex' => 'unknown',
            'is_living' => true,
            'headline' => 'Account holder',
            'notes' => 'This profile was created automatically for the tree owner.',
        ]);
        $tree->update(['owner_person_id' => $ownerPerson->id]);

        $gedcom = <<<GED
0 HEAD
1 GEDC
2 VERS 5.5.1
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Mikolaj /Florysiak/
1 SEX M
1 BIRT
2 DATE 12 APR 1985
0 @I2@ INDI
1 NAME Elena /Rivera/
1 SEX F
0 TRLR
GED;

        $file = UploadedFile::fake()->createWithContent('owner-match.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.gedcom.import', $tree), [
            'gedcom_file' => $file,
        ]);

        $importedOwner = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Mikolaj')
            ->where('surname', 'Florysiak')
            ->firstOrFail();

        $response->assertRedirect(route('trees.show', [
            'tree' => $tree,
            'focus' => $importedOwner->id,
        ]));
        $response->assertSessionHas('owner_selection_required', false);

        $tree->refresh();

        $this->assertSame($importedOwner->id, $tree->owner_person_id);
        $this->assertDatabaseMissing('people', [
            'id' => $ownerPerson->id,
        ]);
    }

    public function test_user_can_import_a_legacy_encoded_gedcom_file_with_concatenated_notes(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $gedcomUtf8 = <<<GED
0 HEAD
1 GEDC
2 VERS 5.5.1
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Jerzy /JAROCIŃSKI/
1 SEX M
1 NOTE Pedagog, po studiach na Uniwersytecie Poznańskim (1948 r.). Prezes tamtejszego Koła Przewodnik
2 CONC ów Terenowych i Sudeckich.
0 TRLR
GED;

        $gedcom = iconv('UTF-8', 'CP1250//IGNORE', $gedcomUtf8);

        $this->assertNotFalse($gedcom);

        $file = UploadedFile::fake()->createWithContent('jarocinski.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.gedcom.import', $tree), [
            'gedcom_file' => $file,
        ]);

        $firstImportedPerson = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Jerzy')
            ->first();

        $this->assertNotNull($firstImportedPerson);
        $response->assertRedirect(route('trees.show', [
            'tree' => $tree,
            'focus' => $firstImportedPerson->id,
        ]));
        $this->assertDatabaseHas('people', [
            'family_tree_id' => $tree->id,
            'given_name' => 'Jerzy',
            'surname' => 'JAROCIŃSKI',
            'notes' => 'Pedagog, po studiach na Uniwersytecie Poznańskim (1948 r.). Prezes tamtejszego Koła Przewodników Terenowych i Sudeckich.',
        ]);
    }

    public function test_user_can_import_mixed_encoded_text_fields_without_corrupting_utf8_lines(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $lines = [
            "0 HEAD",
            "1 GEDC",
            "2 VERS 5.5.1",
            "2 FORM LINEAGE-LINKED",
            "1 CHAR UTF-8",
            "0 @I1@ INDI",
            "1 NAME Izabela Ewa /Różańska/",
            "1 SEX F",
            "1 BIRT",
            "2 PLAC Gocław, Warszawa",
            "1 NOTE Urodzona w parafii św. Piotra i Pawła.",
            "0 @I2@ INDI",
            "1 NAME Jerzy /JAROCIŃSKI/",
            "1 SEX M",
            "1 BIRT",
            "2 PLAC Wielątki, Pułtusk, Poland",
            "1 NOTE Pedagog po studiach na Uniwersytecie Poznańskim.",
            "0 TRLR",
        ];

        $legacyEncodedIndexes = [12, 15, 16];

        foreach ($legacyEncodedIndexes as $index) {
            $lines[$index] = iconv('UTF-8', 'CP1250//IGNORE', $lines[$index]);
        }

        $gedcom = implode("\n", $lines);
        $file = UploadedFile::fake()->createWithContent('mixed-polish.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.gedcom.import', $tree), [
            'gedcom_file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('people', [
            'family_tree_id' => $tree->id,
            'given_name' => 'Izabela',
            'surname' => 'Różańska',
            'birth_place' => 'Gocław, Warszawa',
            'notes' => 'Urodzona w parafii św. Piotra i Pawła.',
        ]);
        $this->assertDatabaseHas('people', [
            'family_tree_id' => $tree->id,
            'given_name' => 'Jerzy',
            'surname' => 'JAROCIŃSKI',
            'birth_place' => 'Wielątki, Pułtusk, Poland',
            'notes' => 'Pedagog po studiach na Uniwersytecie Poznańskim.',
        ]);
    }

    public function test_birth_dates_are_not_overwritten_by_later_fact_dates(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $gedcom = <<<GED
0 HEAD
1 GEDC
2 VERS 5.5.1
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Piotr Marek /Kołodziej/
1 SEX M
1 BIRT
2 DATE 3 APR 1969
2 PLAC Oświęcim, Lesser Poland
1 OCCU Carpenter
2 DATE FROM 1990
0 @I2@ INDI
1 NAME Maksymilian Antoni /Kołodziej/
1 SEX M
1 BIRT
2 DATE 19 MAY 2007
2 PLAC Warwick, Warwickshire, England, United Kingdom
1 CONF Name taken: Krzysztof
2 DATE 14 MAY 2023
1 EVEN Royal Air Force - Cadet Oath
2 TYPE Accomplishment
2 DATE 14 MAY 2024
0 TRLR
GED;

        $file = UploadedFile::fake()->createWithContent('dates.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.gedcom.import', $tree), [
            'gedcom_file' => $file,
        ]);

        $response->assertRedirect();
        $piotr = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Piotr')
            ->firstOrFail();

        $maksymilian = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Maksymilian')
            ->firstOrFail();

        $this->assertSame('1969-04-03', $piotr->birth_date?->toDateString());
        $this->assertSame('3 APR 1969', $piotr->birth_date_text);
        $this->assertSame('2007-05-19', $maksymilian->birth_date?->toDateString());
        $this->assertSame('19 MAY 2007', $maksymilian->birth_date_text);
    }

    public function test_import_prompts_user_to_choose_which_imported_person_becomes_the_owner_profile(): void
    {
        $user = User::factory()->create(['name' => 'Mikołaj Florysiak']);
        $tree = FamilyTree::factory()->for($user)->create();
        $ownerPerson = $tree->people()->create([
            'created_by' => $user->id,
            'given_name' => 'Mikołaj',
            'surname' => 'Florysiak',
            'sex' => 'unknown',
            'is_living' => true,
            'headline' => 'Account holder',
            'notes' => 'This profile was created automatically for the tree owner.',
        ]);
        $tree->update(['owner_person_id' => $ownerPerson->id]);

        $gedcom = <<<GED
0 HEAD
1 GEDC
2 VERS 5.5.1
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Izabela Ewa /Różańska/
1 SEX F
1 BIRT
2 DATE 26 MAR 1969
2 PLAC Warszawa, Poland
0 @I2@ INDI
1 NAME Maksymilian Antoni /Kołodziej/
1 SEX M
1 BIRT
2 DATE 19 MAY 2007
0 TRLR
GED;

        $file = UploadedFile::fake()->createWithContent('owner-reuse.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.gedcom.import', $tree), [
            'gedcom_file' => $file,
        ]);

        $importedPerson = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Izabela')
            ->firstOrFail();

        $response->assertRedirect(route('trees.show', [
            'tree' => $tree,
            'focus' => $importedPerson->id,
        ]));
        $response->assertSessionHas('owner_selection_required', true);

        $tree->refresh();
        $ownerPerson->refresh();

        $this->assertSame($ownerPerson->id, $tree->owner_person_id);
        $this->assertSame('Mikołaj', $ownerPerson->given_name);
        $this->assertSame('Florysiak', $ownerPerson->surname);
        $this->assertSame(3, $tree->people()->count());
    }

    public function test_user_can_choose_imported_person_as_owner_profile_after_import(): void
    {
        $user = User::factory()->create(['name' => 'Mikołaj Florysiak']);
        $tree = FamilyTree::factory()->for($user)->create();
        $ownerPerson = $tree->people()->create([
            'created_by' => $user->id,
            'given_name' => 'Mikołaj',
            'surname' => 'Florysiak',
            'sex' => 'unknown',
            'is_living' => true,
            'headline' => 'Account holder',
            'notes' => 'This profile was created automatically for the tree owner.',
        ]);
        $tree->update(['owner_person_id' => $ownerPerson->id]);

        $importedPerson = Person::factory()->for($tree)->create([
            'created_by' => $user->id,
            'given_name' => 'Izabela',
            'surname' => 'Florysiak',
            'birth_surname' => 'Różańska',
            'sex' => 'female',
        ]);

        $response = $this->actingAs($user)->post(route('trees.owner-person', $tree), [
            'person_id' => $importedPerson->id,
            'return_to' => route('trees.show', ['tree' => $tree, 'focus' => $importedPerson->id]),
        ]);

        $response->assertRedirect(route('trees.show', ['tree' => $tree, 'focus' => $importedPerson->id]));
        $tree->refresh();

        $this->assertSame($importedPerson->id, $tree->owner_person_id);
        $this->assertDatabaseMissing('people', [
            'id' => $ownerPerson->id,
        ]);
    }

    public function test_import_maps_married_and_maiden_surnames_to_the_right_fields(): void
    {
        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $gedcom = <<<GED
0 HEAD
1 GEDC
2 VERS 5.5.1
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Izabela Ewa /Różańska/
2 GIVN Izabela Ewa
2 SURN Różańska
2 _MARNM Florysiak
1 SEX F
0 @I2@ INDI
1 NAME Piotr Marek /Kołodziej/
2 GIVN Piotr Marek
2 SURN Kołodziej
1 SEX M
0 TRLR
GED;

        $file = UploadedFile::fake()->createWithContent('names.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.gedcom.import', $tree), [
            'gedcom_file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('people', [
            'family_tree_id' => $tree->id,
            'given_name' => 'Izabela',
            'middle_name' => 'Ewa',
            'surname' => 'Florysiak',
            'birth_surname' => 'Różańska',
            'sex' => 'female',
        ]);
        $this->assertDatabaseHas('people', [
            'family_tree_id' => $tree->id,
            'given_name' => 'Piotr',
            'middle_name' => 'Marek',
            'surname' => 'Kołodziej',
            'birth_surname' => null,
            'sex' => 'male',
        ]);
    }

    public function test_import_preserves_rich_gedcom_events_relationships_media_and_provenance(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://example.com/photo.jpg' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $gedcom = <<<GED
0 HEAD
1 GEDC
2 VERS 5.5.1
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
1 LANG Polish
1 SOUR MYHERITAGE
2 NAME MyHeritage Family Tree Builder
2 VERS 5.5.1
1 DEST MYHERITAGE
1 DATE 15 APR 2026
1 FILE Exported by MyHeritage.com from Test Tree
1 _PROJECT_GUID TEST-GUID-123
1 _EXPORTED_FROM_SITE_ID 1526590242
0 @I1@ INDI
1 _UPD 13 DEC 2025 11:59:51 GMT -0500
1 NAME Mikołaj Jerzy /Florysiak/
2 GIVN Mikołaj Jerzy
2 SURN Florysiak
2 _RNAME Paul
1 SEX M
1 BIRT
2 DATE 24 FEB 1997
2 PLAC Warszawa, Poland
1 OCCU Software Engineer
2 DATE FROM OCT 2021
2 NOTE Current occupation
1 RELI Christian
2 DATE 24 FEB 1997
1 RESI
2 DATE FROM 2007 TO 2009
2 ADDR
3 CITY Bishops Itchington, Warwickshire
3 CTRY United Kingdom
1 RESI
2 EMAIL mikolaj@example.com
1 IMMI
2 DATE 25 JUN 2005
2 PLAC United Kingdom
1 SOUR @S1@
2 PAGE Profile page
2 QUAY 3
2 DATA
3 DATE 28 JAN 2023
3 TEXT Added by confirming a Smart Match
2 EVEN Smart Matching
3 ROLE 1517306
1 RIN MH:I500003
1 _UID 63D54984D047967611C8D16926D8B933
1 OBJE
2 FORM jpg
2 FILE https://example.com/photo.jpg
2 _FILESIZE 45838
2 _PRIM Y
2 _CUTOUT Y
2 _PERSONALPHOTO Y
2 _PHOTO_RIN MH:P501822
2 TITL Portrait
0 @I2@ INDI
1 NAME Izabela /Florysiak/
1 SEX F
0 @S1@ SOUR
1 _UPD 20 AUG 2023 04:26:01 GMT -0500
1 AUTH Civil Registry
1 TITL Birth register
1 TEXT Imported source body
1 _TYPE Smart Matching
1 _MEDI 34062611-14
1 RIN MH:S1
0 @F1@ FAM
1 HUSB @I1@
1 WIFE @I2@
1 MARR
2 DATE 1 JUN 2020
2 PLAC Warsaw, Poland
2 TYPE Civil
1 DIV
2 DATE 1 JUN 2022
2 NOTE Separated
0 TRLR
GED;

        $file = UploadedFile::fake()->createWithContent('rich-import.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.gedcom.import', $tree), [
            'gedcom_file' => $file,
        ]);

        $response->assertRedirect();

        $person = Person::query()
            ->where('family_tree_id', $tree->id)
            ->where('given_name', 'Mikołaj')
            ->firstOrFail();

        $tree->refresh();

        $this->assertSame('Paul', $person->alternative_name);
        $this->assertSame('MH:I500003', $person->gedcom_rin);
        $this->assertSame('63D54984D047967611C8D16926D8B933', $person->gedcom_uid);
        $this->assertSame('MYHERITAGE', $tree->gedcom_source_system);
        $this->assertSame('Polish', $tree->gedcom_language);
        $this->assertSame('15 APR 2026', $tree->gedcom_exported_at_text);
        $this->assertSame('TEST-GUID-123', $tree->gedcom_project_guid);

        $this->assertDatabaseHas('person_events', [
            'person_id' => $person->id,
            'type' => 'occu',
            'label' => 'Occupation',
            'value' => 'Software Engineer',
            'event_date_text' => 'FROM OCT 2021',
        ]);
        $this->assertDatabaseHas('person_events', [
            'person_id' => $person->id,
            'type' => 'reli',
            'label' => 'Religion',
            'value' => 'Christian',
        ]);
        $this->assertDatabaseHas('person_events', [
            'person_id' => $person->id,
            'type' => 'resi',
            'label' => 'Residence',
            'city' => 'Bishops Itchington, Warwickshire',
            'country' => 'United Kingdom',
        ]);
        $this->assertDatabaseHas('person_events', [
            'person_id' => $person->id,
            'type' => 'resi',
            'email' => 'mikolaj@example.com',
        ]);

        $citation = $person->sourceCitations()->firstOrFail();
        $this->assertSame('Smart Matching', $citation->event_name);
        $this->assertSame('1517306', $citation->role);
        $this->assertSame('28 JAN 2023', $citation->entry_date_text);
        $this->assertSame('Added by confirming a Smart Match', $citation->entry_text);

        $source = Source::query()->where('family_tree_id', $tree->id)->firstOrFail();
        $this->assertSame('Smart Matching', $source->source_type);
        $this->assertSame('34062611-14', $source->source_medium);
        $this->assertSame('MH:S1', $source->gedcom_rin);

        $media = MediaItem::query()->where('family_tree_id', $tree->id)->firstOrFail();
        $this->assertSame($person->id, $media->person_id);
        $this->assertTrue($media->is_primary);
        $this->assertTrue($media->is_cutout);
        $this->assertTrue($media->is_personal_photo);
        $this->assertSame('MH:P501822', $media->gedcom_external_id);
        $this->assertNotNull($media->file_path);
        Storage::disk('local')->assertExists($media->file_path);

        $spouseRelationship = $tree->relationships()
            ->where('type', 'spouse')
            ->firstOrFail();

        $this->assertSame('2020-06-01', $spouseRelationship->start_date?->format('Y-m-d'));
        $this->assertSame('2022-06-01', $spouseRelationship->end_date?->format('Y-m-d'));
        $this->assertSame('Warsaw, Poland', $spouseRelationship->place);
        $this->assertSame('Civil', $spouseRelationship->subtype);
        $this->assertSame('Separated', $spouseRelationship->description);
        $this->assertCount(5, PersonEvent::query()->where('person_id', $person->id)->get());
    }

    public function test_import_keeps_external_reference_when_remote_media_cannot_be_downloaded(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://example.com/missing.jpg' => Http::response('', 404),
        ]);

        $user = User::factory()->create();
        $tree = FamilyTree::factory()->for($user)->create();

        $gedcom = <<<GED
0 HEAD
1 GEDC
2 VERS 7.0
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
0 @I1@ INDI
1 NAME Maria /Rivera/
1 SEX F
1 OBJE
2 FORM image/jpeg
2 FILE https://example.com/missing.jpg
2 TITL Portrait
0 TRLR
GED;

        $file = UploadedFile::fake()->createWithContent('missing-media.ged', $gedcom);

        $response = $this->actingAs($user)->post(route('trees.gedcom.import', $tree), [
            'gedcom_file' => $file,
        ]);

        $response->assertRedirect();

        $media = MediaItem::query()->where('family_tree_id', $tree->id)->firstOrFail();

        $this->assertNull($media->file_path);
        $this->assertSame('https://example.com/missing.jpg', $media->external_reference);
    }
}
