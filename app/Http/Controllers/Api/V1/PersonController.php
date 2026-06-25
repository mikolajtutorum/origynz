<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TreePermission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PersonResource;
use App\Http\Resources\Api\RelationshipResource;
use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Services\RelativeService;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PersonController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
        private readonly RelativeService $relatives,
    ) {}

    public function show(Request $request, Person $person): PersonResource
    {
        $this->authorizeView($request, $person);

        return new PersonResource($person);
    }

    /**
     * Full profile: person + timeline events + spouse relationship facts + imported record.
     */
    public function profile(Request $request, Person $person): JsonResponse
    {
        $this->authorizeView($request, $person);

        $events = $person->events->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->label ?: $e->type,
            'date' => $e->event_date?->format('j M Y') ?: $e->event_date_text,
            'value' => $e->value,
            'place' => collect([$e->place, $e->address_line1, $e->city, $e->country])->filter()->implode(' · ') ?: null,
            'note' => collect([
                $e->age ? __('Age: :v', ['v' => $e->age]) : null,
                $e->cause ? __('Cause: :v', ['v' => $e->cause]) : null,
                $e->email,
            ])->filter()->implode(' · ') ?: null,
            'description' => $e->description,
        ]);

        $facts = $person->familyTree->relationships()
            ->where('type', 'spouse')
            ->where(fn ($q) => $q->where('person_id', $person->id)->orWhere('related_person_id', $person->id))
            ->get()
            ->map(function ($rel) use ($person) {
                $otherId = $rel->person_id === $person->id ? $rel->related_person_id : $rel->person_id;
                $other = Person::find($otherId);

                return [
                    'id' => $rel->id,
                    'with' => $other?->display_name,
                    'subtype' => $rel->subtype,
                    'start' => $rel->start_date_text,
                    'end' => $rel->end_date_text,
                    'place' => $rel->place,
                    'description' => $rel->description,
                ];
            });

        $tree = $person->familyTree;
        $importedRecord = array_filter([
            'alternative_name' => $person->alternative_name,
            'gedcom_updated_at_text' => $person->gedcom_updated_at_text,
            'gedcom_rin' => $person->gedcom_rin,
            'gedcom_uid' => $person->gedcom_uid,
            'source_system' => collect([$tree->gedcom_source_system, $tree->gedcom_source_version, $tree->gedcom_language])->filter()->implode(' · ') ?: null,
            'exported_at' => $tree->gedcom_exported_at_text,
        ], fn ($v) => $v !== null && $v !== '');

        return response()->json([
            'data' => (new PersonResource($person))->resolve($request),
            'events' => $events,
            'relationship_facts' => $facts,
            'imported_record' => $importedRecord,
        ]);
    }

    /**
     * Relationships for a person — both outgoing and incoming.
     */
    public function relationships(Request $request, Person $person): AnonymousResourceCollection
    {
        $this->authorizeView($request, $person);

        $relationships = PersonRelationship::where('person_id', $person->id)
            ->orWhere('related_person_id', $person->id)
            ->get();

        return RelationshipResource::collection($relationships);
    }

    /**
     * Search people across all accessible trees.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'tree_id' => 'nullable|string|exists:family_trees,id',
        ]);

        $accessibleTreeIds = FamilyTree::visibleTo($request->user())->pluck('id');

        $people = Person::whereIn('family_tree_id', $accessibleTreeIds)
            ->whereNull('merged_into_id')
            ->where(fn ($q) => $q
                ->where('given_name', 'like', '%'.$validated['q'].'%')
                ->orWhere('surname', 'like', '%'.$validated['q'].'%'))
            ->when($validated['tree_id'] ?? null, fn ($q, $id) => $q->where('family_tree_id', $id))
            ->limit(50)
            ->get();

        return PersonResource::collection($people);
    }

    /**
     * Add a standalone person to a tree.
     */
    public function store(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $data = $this->validatePerson($request);
        $data['created_by'] = $request->user()->id;
        $data['is_living'] = (bool) ($data['is_living'] ?? false);

        $person = $tree->people()->create($data);

        return (new PersonResource($person))->response()->setStatusCode(201);
    }

    /**
     * Update a person's profile fields.
     */
    public function update(Request $request, Person $person): PersonResource
    {
        $this->treeAccess->authorize($request->user(), $person->familyTree, TreePermission::Manage);

        $data = $this->validatePerson($request, full: true);
        $data['is_living'] = (bool) ($data['is_living'] ?? false);

        $person->update($data);

        return new PersonResource($person);
    }

    /**
     * Create a new person AND wire them to an anchor person by relationship role.
     */
    public function storeRelative(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $personIds = $tree->people()->pluck('id')->all();

        $data = $request->validate([
            'anchor_person_id' => ['required', Rule::in($personIds)],
            'relation_kind' => ['nullable', 'in:parent,spouse,child'],
            'relation_role' => ['nullable', Rule::in($this->relatives->allowedRoles())],
            'relationship_subtype' => ['nullable', 'in:birth,adoptive,foster,guardian,step'],
            'given_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'surname' => ['required', 'string', 'max:80'],
            'birth_surname' => ['nullable', 'string', 'max:80'],
            'sex' => ['required', 'in:female,male,unknown'],
            'birth_date' => ['nullable', 'date'],
            'birth_date_text' => ['nullable', 'string', 'max:120'],
            'death_date' => ['nullable', 'date', 'after_or_equal:birth_date'],
            'death_date_text' => ['nullable', 'string', 'max:120'],
            'birth_place' => ['nullable', 'string', 'max:120'],
            'death_place' => ['nullable', 'string', 'max:120'],
            'is_living' => ['nullable', 'boolean'],
            'headline' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $anchorPerson = $tree->people()->findOrFail($data['anchor_person_id']);
        $role = $this->relatives->resolveRole($data);
        $subtype = $this->relatives->resolveSubtype($role, $data['relationship_subtype'] ?? null);

        $person = $tree->people()->create([
            'created_by' => $request->user()->id,
            'given_name' => $data['given_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'surname' => $data['surname'],
            'birth_surname' => $data['birth_surname'] ?? null,
            'sex' => $data['sex'],
            'birth_date' => $data['birth_date'] ?? null,
            'birth_date_text' => $data['birth_date_text'] ?? null,
            'death_date' => $data['death_date'] ?? null,
            'death_date_text' => $data['death_date_text'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'death_place' => $data['death_place'] ?? null,
            'is_living' => (bool) ($data['is_living'] ?? false),
            'headline' => $data['headline'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $label = $this->relatives->attach($tree, $anchorPerson, $person, $role, $subtype);

        return (new PersonResource($person))
            ->additional(['relationship_label' => $label])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove a person from a tree (their relationship edges cascade).
     */
    public function destroy(Request $request, Person $person): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $person->familyTree, TreePermission::Manage);

        PersonRelationship::where('person_id', $person->id)
            ->orWhere('related_person_id', $person->id)
            ->delete();

        $person->delete();

        return response()->json(['message' => 'Person removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePerson(Request $request, bool $full = false): array
    {
        $rules = [
            'given_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'surname' => ['required', 'string', 'max:80'],
            'birth_surname' => ['nullable', 'string', 'max:80'],
            'sex' => ['required', 'in:female,male,unknown'],
            'birth_date' => ['nullable', 'date'],
            'birth_date_text' => ['nullable', 'string', 'max:120'],
            'death_date' => ['nullable', 'date', 'after_or_equal:birth_date'],
            'death_date_text' => ['nullable', 'string', 'max:120'],
            'birth_place' => ['nullable', 'string', 'max:120'],
            'death_place' => ['nullable', 'string', 'max:120'],
            'is_living' => ['nullable', 'boolean'],
            'headline' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];

        if ($full) {
            $rules += [
                'prefix' => ['nullable', 'string', 'max:40'],
                'suffix' => ['nullable', 'string', 'max:40'],
                'nickname' => ['nullable', 'string', 'max:80'],
                'cause_of_death' => ['nullable', 'string', 'max:120'],
                'burial_place' => ['nullable', 'string', 'max:120'],
                'physical_description' => ['nullable', 'string', 'max:4000'],
            ];
        }

        return $request->validate($rules);
    }

    private function authorizeView(Request $request, Person $person): void
    {
        abort_unless(
            FamilyTree::visibleTo($request->user())->where('id', $person->family_tree_id)->exists(),
            403,
        );
    }
}
