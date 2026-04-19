<?php

namespace App\Http\Controllers;

use App\Enums\TreePermission;
use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function store(Request $request, FamilyTree $tree): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $data = $request->validate([
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

        $data['created_by'] = $request->user()->id;
        $data['is_living'] = (bool) ($data['is_living'] ?? false);

        $person = $tree->people()->create($data);

        if ($redirect = $this->workspaceRedirect($request)) {
            return redirect()->to($redirect)->with('status', 'Person added to tree.');
        }

        return redirect()
            ->route('trees.show', ['tree' => $tree, 'focus' => $person->id])
            ->with('status', 'Person added to tree.');
    }

    public function update(Request $request, Person $person): RedirectResponse
    {
        $tree = $person->familyTree;

        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $data = $request->validate([
            'given_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'surname' => ['required', 'string', 'max:80'],
            'birth_surname' => ['nullable', 'string', 'max:80'],
            'prefix' => ['nullable', 'string', 'max:40'],
            'suffix' => ['nullable', 'string', 'max:40'],
            'nickname' => ['nullable', 'string', 'max:80'],
            'sex' => ['required', 'in:female,male,unknown'],
            'birth_date' => ['nullable', 'date'],
            'birth_date_text' => ['nullable', 'string', 'max:120'],
            'death_date' => ['nullable', 'date', 'after_or_equal:birth_date'],
            'death_date_text' => ['nullable', 'string', 'max:120'],
            'birth_place' => ['nullable', 'string', 'max:120'],
            'death_place' => ['nullable', 'string', 'max:120'],
            'cause_of_death' => ['nullable', 'string', 'max:120'],
            'burial_place' => ['nullable', 'string', 'max:120'],
            'is_living' => ['nullable', 'boolean'],
            'headline' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'physical_description' => ['nullable', 'string', 'max:4000'],
        ]);

        $data['is_living'] = (bool) ($data['is_living'] ?? false);

        $person->update($data);

        // Update any relationship sub-fields submitted as _rel_{id}_{field}
        $this->updateRelationshipSubfields($request, $tree, $person);

        if ($redirect = $this->workspaceRedirect($request)) {
            return redirect()->to($redirect)->with('status', 'Person updated.');
        }

        return redirect()
            ->route('trees.show', ['tree' => $tree, 'focus' => $person->id])
            ->with('status', 'Person updated.');
    }

    public function storeRelative(Request $request, FamilyTree $tree): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $personIds = $tree->people()->pluck('id')->all();

        $data = $request->validate([
            'anchor_person_id' => ['required', Rule::in($personIds)],
            'relation_kind' => ['nullable', 'in:parent,spouse,child'],
            'relation_role' => ['nullable', 'in:father,mother,brother,sister,son,daughter,partner,parent,spouse,child,stepfather,stepmother,stepchild,stepson,stepdaughter,half-brother,half-sister,adoptive-father,adoptive-mother,adoptive-parent,adopted-son,adopted-daughter,adopted-child,foster-parent,foster-child,guardian'],
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
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $anchorPerson = $tree->people()->findOrFail((int) $data['anchor_person_id']);
        $role = $this->resolveRelationRole($data);
        $relationshipSubtype = $this->resolveRelationshipSubtype($role, $data['relationship_subtype'] ?? null);

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

        $statusLabel = $this->attachRelativeToTree($tree, $anchorPerson, $person, $role, $relationshipSubtype);

        if ($redirect = $this->workspaceRedirect($request)) {
            return redirect()->to($redirect)->with('status', $statusLabel.' added to tree.');
        }

        return redirect()
            ->route('trees.show', ['tree' => $tree, 'focus' => $anchorPerson->id])
            ->with('status', $statusLabel.' added to tree.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveRelationRole(array $data): string
    {
        $role = (string) ($data['relation_role'] ?? '');

        if ($role !== '') {
            return $role;
        }

        return match ($data['relation_kind'] ?? null) {
            'parent' => 'parent',
            'spouse' => 'partner',
            'child' => 'child',
            default => throw new HttpResponseException(redirect()->back()),
        };
    }

    private function resolveRelationshipSubtype(string $role, mixed $requestedSubtype): ?string
    {
        $requested = $this->normalizeRelationshipSubtype($requestedSubtype);

        return match ($role) {
            'stepfather', 'stepmother', 'stepchild', 'stepson', 'stepdaughter' => 'step',
            'adoptive-father', 'adoptive-mother', 'adoptive-parent', 'adopted-son', 'adopted-daughter', 'adopted-child' => 'adoptive',
            'foster-parent', 'foster-child' => 'foster',
            'guardian' => 'guardian',
            default => $requested,
        };
    }

    private function attachRelativeToTree(FamilyTree $tree, Person $anchorPerson, Person $newPerson, string $role, ?string $relationshipSubtype): string
    {
        if (in_array($role, ['father', 'mother', 'parent', 'stepfather', 'stepmother', 'adoptive-father', 'adoptive-mother', 'adoptive-parent', 'foster-parent', 'guardian'], true)) {
            $tree->relationships()->firstOrCreate([
                'person_id' => $newPerson->id,
                'related_person_id' => $anchorPerson->id,
                'type' => 'parent',
            ], array_filter([
                'subtype' => $relationshipSubtype,
            ], fn ($value) => $value !== null && $value !== ''));

            return match ($relationshipSubtype) {
                'step' => ucfirst($role === 'stepmother' ? 'stepmother' : ($role === 'stepfather' ? 'stepfather' : 'step-parent')),
                'adoptive' => match ($role) {
                    'adoptive-father' => 'Adoptive father',
                    'adoptive-mother' => 'Adoptive mother',
                    default => 'Adoptive parent',
                },
                'foster' => 'Foster parent',
                'guardian' => 'Guardian',
                default => ucfirst($role === 'parent' ? 'parent' : $role),
            };
        }

        if (in_array($role, ['son', 'daughter', 'child', 'stepchild', 'stepson', 'stepdaughter', 'adopted-son', 'adopted-daughter', 'adopted-child', 'foster-child'], true)) {
            $tree->relationships()->firstOrCreate([
                'person_id' => $anchorPerson->id,
                'related_person_id' => $newPerson->id,
                'type' => 'parent',
            ], array_filter([
                'subtype' => $relationshipSubtype,
            ], fn ($value) => $value !== null && $value !== ''));

            return match ($relationshipSubtype) {
                'step' => match ($role) {
                    'stepson' => 'Stepson',
                    'stepdaughter' => 'Stepdaughter',
                    default => 'Stepchild',
                },
                'adoptive' => match ($role) {
                    'adopted-son' => 'Adopted son',
                    'adopted-daughter' => 'Adopted daughter',
                    default => 'Adopted child',
                },
                'foster' => 'Foster child',
                default => ucfirst($role === 'child' ? 'child' : $role),
            };
        }

        if (in_array($role, ['partner', 'spouse'], true)) {
            $tree->relationships()->firstOrCreate([
                'person_id' => $anchorPerson->id,
                'related_person_id' => $newPerson->id,
                'type' => 'spouse',
            ]);

            return 'Partner';
        }

        if (in_array($role, ['brother', 'sister', 'half-brother', 'half-sister'], true)) {
            $parentIds = $tree->relationships()
                ->where('type', 'parent')
                ->where('related_person_id', $anchorPerson->id)
                ->pluck('person_id')
                ->all();

            if ($parentIds === []) {
                $bridgeParent = $tree->people()->create([
                    'created_by' => $tree->user_id,
                    'given_name' => 'Unknown',
                    'surname' => $anchorPerson->surname,
                    'sex' => 'unknown',
                    'headline' => 'Auto-created relationship bridge',
                    'notes' => 'Created automatically so sibling relationships remain connected in the graph.',
                ]);

                $parentIds = [$bridgeParent->id];

                $tree->relationships()->firstOrCreate([
                    'person_id' => $bridgeParent->id,
                    'related_person_id' => $anchorPerson->id,
                    'type' => 'parent',
                ]);
            }

            $sharedParentIds = str_starts_with($role, 'half-') ? array_slice($parentIds, 0, 1) : $parentIds;

            foreach ($sharedParentIds as $parentId) {
                $tree->relationships()->firstOrCreate([
                    'person_id' => $parentId,
                    'related_person_id' => $newPerson->id,
                    'type' => 'parent',
                ]);
            }

            return match ($role) {
                'half-brother' => 'Half-brother',
                'half-sister' => 'Half-sister',
                default => ucfirst($role),
            };
        }

        throw new HttpResponseException(redirect()->route('trees.show', $tree));
    }

    private function normalizeRelationshipSubtype(mixed $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '', 'birth', 'biological' => null,
            'adopted', 'adoptive' => 'adoptive',
            'foster' => 'foster',
            'guardian', 'guardianship' => 'guardian',
            'step', 'stepchild' => 'step',
            default => null,
        };
    }

    private function updateRelationshipSubfields(Request $request, FamilyTree $tree, Person $person): void
    {
        $relIds = [];
        foreach ($request->keys() as $key) {
            if (preg_match('/^_rel_(\d+)_id$/', $key, $m)) {
                $relIds[] = (int) $m[1];
            }
        }

        foreach ($relIds as $relId) {
            $rel = PersonRelationship::find($relId);
            if (! $rel || $rel->family_tree_id !== $tree->id) {
                continue;
            }
            if ($rel->person_id !== $person->id && $rel->related_person_id !== $person->id) {
                continue;
            }

            $rel->update([
                'subtype'         => $request->input("_rel_{$relId}_subtype") ?: null,
                'start_date'      => $request->input("_rel_{$relId}_start_date") ?: null,
                'start_date_text' => $request->input("_rel_{$relId}_start_date_text") ?: null,
                'place'           => $request->input("_rel_{$relId}_place") ?: null,
            ]);
        }
    }

    private function workspaceRedirect(Request $request): ?string
    {
        $returnTo = $request->string('return_to')->trim()->value();

        if ($returnTo === '') {
            return null;
        }

        if (str_starts_with($returnTo, '/trees/')) {
            return $returnTo;
        }

        $returnHost = parse_url($returnTo, PHP_URL_HOST);
        $requestHost = parse_url($request->fullUrl(), PHP_URL_HOST);
        $returnPath = parse_url($returnTo, PHP_URL_PATH) ?: '';

        if ($returnHost && $requestHost && $returnHost === $requestHost && str_starts_with($returnPath, '/trees/')) {
            return $returnTo;
        }

        return null;
    }
}
