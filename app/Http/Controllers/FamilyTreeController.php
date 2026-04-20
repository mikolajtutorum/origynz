<?php

namespace App\Http\Controllers;

use App\Enums\TreePermission;
use App\Models\FamilyTree;
use App\Models\MediaItem;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Models\Site;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FamilyTreeController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function openFirst(Request $request): RedirectResponse
    {
        $tree = FamilyTree::query()
            ->visibleTo($request->user())
            ->orderBy('id')
            ->first();

        if (! $tree) {
            return redirect()
                ->route('trees.manage')
                ->with('status', 'Create your first family tree to get started.');
        }

        return redirect()->route('trees.show', $tree);
    }

    public function manage(Request $request): View
    {
        $user = $request->user();
        $trees = FamilyTree::query()
            ->visibleTo($user)
            ->withCount(['people', 'relationships'])
            ->orderBy('name')
            ->get();

        foreach ($trees as $tree) {
            $tree->setAttribute(
                'access_level_label',
                $this->treeAccess->getTreeAccessLevel($user, $tree)?->label() ?? __('Tree observer'),
            );
            $tree->setAttribute(
                'can_manage_tree',
                $this->treeAccess->can($user, $tree, TreePermission::Manage),
            );
        }

        return view('trees.manage', [
            'trees' => $trees,
        ]);
    }

    public function importPage(Request $request): View
    {
        $trees = FamilyTree::query()
            ->manageableBy($request->user())
            ->orderBy('name')
            ->get(['id', 'name', 'home_region']);

        return view('trees.import', [
            'trees' => $trees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'home_region' => ['nullable', 'string', 'max:120'],
            'privacy' => ['required', 'in:private,invited,public'],
        ]);

        $user = $request->user();
        if (blank($data['home_region'] ?? null) && $user->country_of_residence) {
            $data['home_region'] = $user->country_of_residence;
        }
        $data['site_id'] = Site::forUser($user)->id;
        $tree = $user->familyTrees()->create($data);
        $this->treeAccess->grantTreeAccess($user, $tree, \App\Enums\TreeAccessLevel::Owner);

        $ownerPerson = $tree->people()->create([
            'created_by' => $user->id,
            ...$this->ownerPersonSeed($user),
            'sex' => 'unknown',
            'is_living' => true,
            'headline' => 'Account holder',
            'notes' => 'This profile was created automatically for the tree owner.',
        ]);

        $tree->update(['owner_person_id' => $ownerPerson->id]);

        return redirect()
            ->route('trees.show', ['tree' => $tree, 'focus' => $ownerPerson->id])
            ->with('status', 'Family tree created.');
    }

    public function show(Request $request, FamilyTree $tree): View|JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Observe);
        $this->ensureOwnerPerson($tree, $request->user());

        $people = $tree->people()
            ->select([
                'id',
                'family_tree_id',
                'created_by',
                'given_name',
                'middle_name',
                'alternative_name',
                'surname',
                'birth_surname',
                'prefix',
                'suffix',
                'nickname',
                'sex',
                'birth_date',
                'birth_date_text',
                'death_date',
                'death_date_text',
                'birth_place',
                'death_place',
                'cause_of_death',
                'burial_place',
                'is_living',
                'headline',
                'notes',
                'physical_description',
                'gedcom_rin',
                'gedcom_uid',
                'gedcom_updated_at_text',
            ])
            ->orderBy('surname')
            ->orderBy('given_name')
            ->get();

        $relationships = $tree->relationships()
            ->select([
                'id',
                'family_tree_id',
                'person_id',
                'related_person_id',
                'type',
                'start_date',
                'start_date_text',
                'end_date',
                'end_date_text',
                'place',
                'subtype',
                'description',
            ])
            ->get();

        // Build a map of person_id → preview URL for the best available image per person
        $personAvatarUrls = [];
        if ($people->isNotEmpty()) {
            $avatarMedia = MediaItem::query()
                ->whereIn('person_id', $people->pluck('id'))
                ->whereNotNull('file_path')
                ->where('mime_type', 'like', 'image/%')
                ->orderByRaw('is_personal_photo DESC, is_primary DESC, id ASC')
                ->get(['id', 'person_id']);

            foreach ($avatarMedia as $media) {
                if (! isset($personAvatarUrls[$media->person_id])) {
                    $personAvatarUrls[$media->person_id] = route('media.preview', $media->id);
                }
            }
        }

        $peopleById = [];
        $peopleOptions = [];
        $ownerCandidateOptions = [];
        $peopleSearchIndex = [];
        $peoplePositionById = [];

        foreach ($people as $person) {
            $peopleById[$person->id] = $person;
            $peoplePositionById[$person->id] = count($peoplePositionById) + 1;
            $peopleOptions[] = [
                'id' => $person->id,
                'name' => $person->display_name,
            ];
            $ownerCandidateOptions[] = [
                'id' => $person->id,
                'name' => $person->display_name,
                'life_span' => $person->life_span,
                'birth_place' => $person->birth_place ?: 'Place unknown',
                'is_living' => $person->is_living,
                'score' => $this->ownerCandidateScore($person, $request->user(), $tree),
                'match_label' => $this->ownerCandidateMatchLabel($this->ownerCandidateScore($person, $request->user(), $tree)),
            ];
            $peopleSearchIndex[] = [
                'id' => $person->id,
                'name' => $person->display_name,
                'life_span' => $person->life_span,
                'birth_place' => $person->birth_place ?: 'Place unknown',
            ];
        }

        $requestedFocusId = $request->has('focus') ? (string) $request->input('focus') : null;
        $focusPerson = null;

        if ($requestedFocusId !== null && array_key_exists($requestedFocusId, $peopleById)) {
            $focusPerson = $peopleById[$requestedFocusId];
        } elseif ($tree->owner_person_id !== null && array_key_exists($tree->owner_person_id, $peopleById)) {
            $focusPerson = $peopleById[$tree->owner_person_id];
        } elseif ($people->isNotEmpty()) {
            $focusPerson = $people->first();
        }

        if ($focusPerson) {
            $focusPerson->load([
                'mediaItems',
                'sourceCitations.source',
                'events',
            ]);
        }

        [
            $parentsMap,
            $childrenMap,
            $spousesMap,
            $relationshipCount,
            $parentSubtypeMap,
            $lineageParentsMap,
            $lineageChildrenMap,
        ] = $this->normalizeRelationships($relationships);

        $mode = $request->query('mode', 'pedigree');
        $mode = in_array($mode, ['pedigree', 'descendants'], true) ? $mode : 'pedigree';
        $showOwnerChooser = session('owner_selection_required') || $request->boolean('owner_chooser');

        $generations = max(2, min(5, (int) $request->query('generations', 3)));
        $collapsedIds = $this->parseCollapsedIds((string) $request->query('collapsed', ''));

        foreach ($peopleSearchIndex as $index => $entry) {
            $peopleSearchIndex[$index]['focus_url'] = $this->treeUrl($tree, [
                'focus' => $entry['id'],
                'mode' => $mode,
                'generations' => $generations,
                'collapsed' => $collapsedIds,
            ]);
        }

        usort($ownerCandidateOptions, function (array $left, array $right): int {
            $scoreComparison = $right['score'] <=> $left['score'];

            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            return strcasecmp((string) $left['name'], (string) $right['name']);
        });

        $ownerSearch = '';

        $focusFamily = [
            'parents' => [],
            'spouses' => [],
            'children' => [],
        ];
        $focusRelationshipFacts = collect();

        if ($focusPerson) {
            $focusFamily = [
                'parents' => $this->peopleFromIds($peopleById, $parentsMap[$focusPerson->id] ?? []),
                'spouses' => $this->peopleFromIds($peopleById, $spousesMap[$focusPerson->id] ?? []),
                'children' => $this->peopleFromIds($peopleById, $childrenMap[$focusPerson->id] ?? []),
            ];

            $focusRelationshipFacts = $relationships
                ->filter(function (PersonRelationship $relationship) use ($focusPerson): bool {
                    return $relationship->type === 'spouse'
                        && ($relationship->person_id === $focusPerson->id || $relationship->related_person_id === $focusPerson->id);
                })
                ->map(function (PersonRelationship $relationship) use ($focusPerson, $peopleById): array {
                    $otherId = $relationship->person_id === $focusPerson->id
                        ? $relationship->related_person_id
                        : $relationship->person_id;

                    return [
                        'id' => $relationship->id,
                        'person' => $peopleById[$otherId] ?? null,
                        'start_date' => $relationship->start_date,
                        'start_date_text' => $relationship->start_date_text,
                        'start_date_readable' => $relationship->start_date?->format('j M Y') ?: $relationship->start_date_text,
                        'end_date_text' => $relationship->end_date?->format('j M Y') ?: $relationship->end_date_text,
                        'place' => $relationship->place,
                        'subtype' => $relationship->subtype,
                        'description' => $relationship->description,
                    ];
                })
                ->filter(fn (array $fact): bool => $fact['person'] !== null)
                ->values();
        }

        $ownerPerson = $tree->owner_person_id !== null ? ($peopleById[$tree->owner_person_id] ?? null) : null;
        $ownerRelationshipLabel = $this->relationshipToOwnerLabel(
            $focusPerson,
            $ownerPerson,
            $parentsMap,
            $childrenMap,
            $spousesMap,
            $parentSubtypeMap,
            $lineageParentsMap,
            $lineageChildrenMap
        );

        $sidebarOnly = $request->boolean('partial') && $request->boolean('sidebar_only');

        if (! $sidebarOnly) {
            [$chartNodes, $chartLines, $chartMeta] = $this->buildChart(
                $tree,
                $focusPerson,
                $peopleById,
                $parentsMap,
                $childrenMap,
                $spousesMap,
                $mode,
                $generations,
                $collapsedIds
            );
        } else {
            $chartNodes = [];
            $chartLines = [];
            $chartMeta  = ['width' => 0, 'height' => 0];
        }

        $toolbarCollapsed = $collapsedIds ? implode(',', $collapsedIds) : null;
        $focusPersonPosition = $focusPerson ? ($peoplePositionById[$focusPerson->id] ?? 0) : 0;

        $currentUrl = $this->treeUrl($tree, [
            'focus' => $focusPerson?->id,
            'mode' => $mode,
            'generations' => $generations,
            'collapsed' => $collapsedIds,
        ]);

        $focusMedia = $focusPerson?->mediaItems ?? collect();
        $focusImageMedia = $focusMedia->filter(
            fn ($item) => $item->file_path && str_starts_with((string) $item->mime_type, 'image/')
        )->values();
        $focusCitations = $focusPerson?->sourceCitations ?? collect();
        $focusEvents = $focusPerson?->events ?? collect();
        $immediateFamily = collect($focusFamily['parents'])
            ->merge($focusFamily['spouses'])
            ->merge($focusFamily['children']);

        $user = $request->user();

        $userTrees = FamilyTree::query()
            ->visibleTo($user)
            ->orderBy('name')
            ->get(['id', 'name']);

        $currentSite = $tree->site ?? Site::forUser($user);

        $accessibleSites = Site::query()
            ->accessibleTo($user)
            ->orderBy('name')
            ->get(['id', 'name', 'user_id']);

        $viewData = [
            'tree' => $tree,
            'userTrees' => $userTrees,
            'currentSite' => $currentSite,
            'accessibleSites' => $accessibleSites,
            'personAvatarUrls' => $personAvatarUrls,
            'peopleCount' => $people->count(),
            'focusPersonPosition' => $focusPersonPosition,
            'peopleOptions' => $peopleOptions,
            'ownerCandidateOptions' => $ownerCandidateOptions,
            'ownerSearch' => $ownerSearch,
            'showOwnerChooser' => $showOwnerChooser,
            'peopleSearchIndex' => $peopleSearchIndex,
            'relationshipCount' => $relationshipCount,
            'focusPerson' => $focusPerson,
            'focusFamily' => $focusFamily,
            'focusRelationshipFacts' => $focusRelationshipFacts,
            'ownerRelationshipLabel' => $ownerRelationshipLabel,
            'chartNodes' => $chartNodes,
            'chartLines' => $chartLines,
            'chartMeta' => $chartMeta,
            'chartMode' => $mode,
            'chartGenerations' => $generations,
            'chartCollapsedIds' => $collapsedIds,
            'toolbarCollapsed' => $toolbarCollapsed,
            'currentUrl' => $currentUrl,
            'focusMedia' => $focusMedia,
            'focusImageMedia' => $focusImageMedia,
            'focusCitations' => $focusCitations,
            'focusEvents' => $focusEvents,
            'immediateFamily' => $immediateFamily,
        ];

        if ($request->boolean('partial')) {
            $json = [
                'sidebar_html'    => view('trees.partials.sidebar-inner', $viewData)->render(),
                'edit_modal_html' => $focusPerson
                    ? view('trees.partials.edit-modal', $viewData)->render()
                    : '',
            ];

            if (! $sidebarOnly) {
                $json['canvas_html'] = view('trees.partials.canvas', $viewData)->render();
                $json['toolbar']     = [
                    'focus_name'       => $focusPerson?->display_name,
                    'focus_position'   => $focusPersonPosition,
                    'focus_person_id'  => $focusPerson?->id,
                    'chart_mode'       => $mode,
                    'chart_generations'=> $generations,
                    'chart_meta'       => $chartMeta,
                ];
            }

            return response()->json($json);
        }

        return view('trees.show', $viewData);
    }

    public function assignOwnerPerson(Request $request, FamilyTree $tree): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Owner);

        $data = $request->validate([
            'person_id' => ['required', 'string'],
            'return_to' => ['nullable', 'url'],
        ]);

        $person = $tree->people()->findOrFail($data['person_id']);
        $previousOwner = $tree->owner_person_id !== null ? $tree->people()->find($tree->owner_person_id) : null;

        $tree->update(['owner_person_id' => $person->id]);

        if ($previousOwner && $previousOwner->id !== $person->id && $this->isDisposablePlaceholderOwner($tree, $previousOwner)) {
            $previousOwner->delete();
        }

        $redirect = $data['return_to'] ?? route('trees.show', ['tree' => $tree, 'focus' => $person->id]);

        return redirect()->to($redirect)->with('status', 'Main account profile updated.');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, PersonRelationship>  $relationships
     * @return array{
     *     0: array<int, list<int>>,
     *     1: array<int, list<int>>,
     *     2: array<int, list<int>>,
     *     3: int,
     *     4: array<int, array<int, string|null>>,
     *     5: array<int, list<int>>,
     *     6: array<int, list<int>>
     * }
     */
    private function normalizeRelationships($relationships): array
    {
        $parentsMap = [];
        $childrenMap = [];
        $spousesMap = [];
        $parentSubtypeMap = [];
        $lineageParentsMap = [];
        $lineageChildrenMap = [];

        foreach ($relationships as $relationship) {
            if ($relationship->type === 'spouse') {
                $this->pushUnique($spousesMap, $relationship->person_id, $relationship->related_person_id);
                $this->pushUnique($spousesMap, $relationship->related_person_id, $relationship->person_id);
            }

            if ($relationship->type === 'parent') {
                $this->pushUnique($parentsMap, $relationship->related_person_id, $relationship->person_id);
                $this->pushUnique($childrenMap, $relationship->person_id, $relationship->related_person_id);
                $this->storeParentSubtype($parentSubtypeMap, $relationship->person_id, $relationship->related_person_id, $relationship->subtype);

                if ($this->countsAsLineageParent($relationship->subtype)) {
                    $this->pushUnique($lineageParentsMap, $relationship->related_person_id, $relationship->person_id);
                    $this->pushUnique($lineageChildrenMap, $relationship->person_id, $relationship->related_person_id);
                }
            }

            if ($relationship->type === 'child') {
                $this->pushUnique($parentsMap, $relationship->person_id, $relationship->related_person_id);
                $this->pushUnique($childrenMap, $relationship->related_person_id, $relationship->person_id);
                $this->storeParentSubtype($parentSubtypeMap, $relationship->related_person_id, $relationship->person_id, $relationship->subtype);

                if ($this->countsAsLineageParent($relationship->subtype)) {
                    $this->pushUnique($lineageParentsMap, $relationship->person_id, $relationship->related_person_id);
                    $this->pushUnique($lineageChildrenMap, $relationship->related_person_id, $relationship->person_id);
                }
            }
        }

        return [$parentsMap, $childrenMap, $spousesMap, $relationships->count(), $parentSubtypeMap, $lineageParentsMap, $lineageChildrenMap];
    }

    /**
     * @param  array<string, list<string>>  $map
     */
    private function pushUnique(array &$map, string $key, string $value): void
    {
        $map[$key] ??= [];

        if (! in_array($value, $map[$key], true)) {
            $map[$key][] = $value;
        }
    }

    /**
     * @param  array<string, array<string, string|null>>  $map
     */
    private function storeParentSubtype(array &$map, string $parentId, string $childId, ?string $subtype): void
    {
        $map[$parentId] ??= [];
        $map[$parentId][$childId] = $this->normalizeParentRelationshipSubtype($subtype);
    }

    /**
     * @return list<int>
     */
    private function parseCollapsedIds(string $collapsed): array
    {
        $ids = [];

        foreach (explode(',', $collapsed) as $value) {
            $value = trim($value);

            if ($value === '') {
                continue;
            }

            $ids[] = $value;
        }

        $ids = array_values(array_unique($ids));

        sort($ids);

        return $ids;
    }

    /**
     * @param  array<string, Person>  $peopleById
     * @param  list<string>  $ids
     * @return list<Person>
     */
    private function peopleFromIds(array $peopleById, array $ids): array
    {
        $people = [];

        foreach ($ids as $id) {
            if (array_key_exists($id, $peopleById)) {
                $people[] = $peopleById[$id];
            }
        }

        usort($people, fn (Person $a, Person $b) => strcmp($a->display_name, $b->display_name));

        return $people;
    }

    private function relationshipToOwnerLabel(
        ?Person $focusPerson,
        ?Person $ownerPerson,
        array $parentsMap,
        array $childrenMap,
        array $spousesMap,
        array $parentSubtypeMap,
        array $lineageParentsMap,
        array $lineageChildrenMap
    ): ?string {
        if (! $focusPerson || ! $ownerPerson) {
            return null;
        }

        if ($focusPerson->id === $ownerPerson->id) {
            return 'You';
        }

        if (in_array($focusPerson->id, $spousesMap[$ownerPerson->id] ?? [], true)) {
            return 'Your '.$this->genderedRole($focusPerson, 'husband', 'wife', 'partner');
        }

        if (in_array($focusPerson->id, $parentsMap[$ownerPerson->id] ?? [], true)) {
            return 'Your '.$this->parentLabelForSubtype(
                $focusPerson,
                $this->parentSubtype($focusPerson->id, $ownerPerson->id, $parentSubtypeMap)
            );
        }

        if (in_array($focusPerson->id, $childrenMap[$ownerPerson->id] ?? [], true)) {
            return 'Your '.$this->childLabelForSubtype(
                $focusPerson,
                $this->parentSubtype($ownerPerson->id, $focusPerson->id, $parentSubtypeMap)
            );
        }

        $siblingLabel = $this->siblingLabel($focusPerson, $ownerPerson, $lineageParentsMap, $spousesMap);

        if ($siblingLabel !== null) {
            return 'Your '.$siblingLabel;
        }

        $ancestorDepth = $this->distanceViaMap($ownerPerson->id, $focusPerson->id, $lineageParentsMap);

        if ($ancestorDepth !== null) {
            return 'Your '.$this->ancestorLabel($focusPerson, $ancestorDepth);
        }

        $descendantDepth = $this->distanceViaMap($ownerPerson->id, $focusPerson->id, $lineageChildrenMap);

        if ($descendantDepth !== null) {
            return 'Your '.$this->descendantLabel($focusPerson, $descendantDepth);
        }

        $bloodRelativeLabel = $this->extendedBloodRelativeLabel($focusPerson, $ownerPerson, $lineageParentsMap);

        if ($bloodRelativeLabel !== null) {
            return 'Your '.$bloodRelativeLabel;
        }

        $inLawLabel = $this->inLawLabel($focusPerson, $ownerPerson, $lineageParentsMap, $lineageChildrenMap, $spousesMap);

        if ($inLawLabel !== null) {
            return 'Your '.$inLawLabel;
        }

        return 'Unknown relationship';
    }

    private function distanceViaMap(string $startId, string $targetId, array $map): ?int
    {
        $visited = [$startId => true];
        $queue = [[$startId, 0]];

        while ($queue !== []) {
            [$currentId, $depth] = array_shift($queue);

            foreach ($map[$currentId] ?? [] as $nextId) {
                if (isset($visited[$nextId])) {
                    continue;
                }

                if ($nextId === $targetId) {
                    return $depth + 1;
                }

                $visited[$nextId] = true;
                $queue[] = [$nextId, $depth + 1];
            }
        }

        return null;
    }

    private function shareParent(string $leftId, string $rightId, array $parentsMap): bool
    {
        return array_intersect($parentsMap[$leftId] ?? [], $parentsMap[$rightId] ?? []) !== [];
    }

    private function ancestorLabel(Person $person, int $depth): string
    {
        if ($depth === 1) {
            return $this->genderedRole($person, 'father', 'mother', 'parent');
        }

        if ($depth === 2) {
            return $this->genderedRole($person, 'grandfather', 'grandmother', 'grandparent');
        }

        if ($depth === 3) {
            return $this->genderedRole($person, 'great-grandfather', 'great-grandmother', 'great-grandparent');
        }

        return sprintf(
            '%s great-%s',
            $this->ordinal($depth - 2),
            $this->genderedRole($person, 'grandfather', 'grandmother', 'grandparent')
        );
    }

    private function descendantLabel(Person $person, int $depth): string
    {
        if ($depth === 1) {
            return $this->genderedRole($person, 'son', 'daughter', 'child');
        }

        if ($depth === 2) {
            return $this->genderedRole($person, 'grandson', 'granddaughter', 'grandchild');
        }

        if ($depth === 3) {
            return $this->genderedRole($person, 'great-grandson', 'great-granddaughter', 'great-grandchild');
        }

        return sprintf(
            '%s great-%s',
            $this->ordinal($depth - 2),
            $this->genderedRole($person, 'grandson', 'granddaughter', 'grandchild')
        );
    }

    private function genderedRole(Person $person, string $male, string $female, string $neutral): string
    {
        return match ($person->sex) {
            'male' => $male,
            'female' => $female,
            default => $neutral,
        };
    }

    /**
     * @param  array<string, array<string, string|null>>  $parentSubtypeMap
     */
    private function parentSubtype(string $parentId, string $childId, array $parentSubtypeMap): ?string
    {
        return $parentSubtypeMap[$parentId][$childId] ?? null;
    }

    private function parentLabelForSubtype(Person $person, ?string $subtype): string
    {
        return match ($subtype) {
            'step' => $this->genderedRole($person, 'stepfather', 'stepmother', 'step-parent'),
            'adoptive' => $this->genderedRole($person, 'adoptive father', 'adoptive mother', 'adoptive parent'),
            'foster' => 'foster parent',
            'guardian' => 'guardian',
            default => $this->genderedRole($person, 'father', 'mother', 'parent'),
        };
    }

    private function childLabelForSubtype(Person $person, ?string $subtype): string
    {
        return match ($subtype) {
            'step' => $this->genderedRole($person, 'stepson', 'stepdaughter', 'stepchild'),
            'adoptive' => $this->genderedRole($person, 'adopted son', 'adopted daughter', 'adopted child'),
            'foster' => 'foster child',
            'guardian' => 'ward',
            default => $this->genderedRole($person, 'son', 'daughter', 'child'),
        };
    }

    /**
     * @param  array<int, list<int>>  $lineageParentsMap
     * @param  array<int, list<int>>  $spousesMap
     */
    private function siblingLabel(
        Person $focusPerson,
        Person $ownerPerson,
        array $lineageParentsMap,
        array $spousesMap
    ): ?string {
        $ownerParents = $lineageParentsMap[$ownerPerson->id] ?? [];
        $focusParents = $lineageParentsMap[$focusPerson->id] ?? [];
        $sharedParents = array_values(array_intersect($ownerParents, $focusParents));

        if ($sharedParents !== []) {
            $ownerParentSet = array_values(array_unique($ownerParents));
            $focusParentSet = array_values(array_unique($focusParents));
            sort($ownerParentSet);
            sort($focusParentSet);

            if ($ownerParentSet !== $focusParentSet) {
                return $this->genderedRole($focusPerson, 'half-brother', 'half-sister', 'half-sibling');
            }

            return $this->genderedRole($focusPerson, 'brother', 'sister', 'sibling');
        }

        if ($this->isStepSibling($ownerPerson->id, $focusPerson->id, $lineageParentsMap, $spousesMap)) {
            return $this->genderedRole($focusPerson, 'stepbrother', 'stepsister', 'stepsibling');
        }

        return null;
    }

    /**
     * @param  array<int, list<int>>  $lineageParentsMap
     * @param  array<string, list<string>>  $spousesMap
     */
    private function isStepSibling(
        string $ownerId,
        string $focusId,
        array $lineageParentsMap,
        array $spousesMap
    ): bool {
        $ownerParents = $lineageParentsMap[$ownerId] ?? [];
        $focusParents = $lineageParentsMap[$focusId] ?? [];

        if ($ownerParents === [] || $focusParents === []) {
            return false;
        }

        if (array_intersect($ownerParents, $focusParents) !== []) {
            return false;
        }

        foreach ($ownerParents as $ownerParentId) {
            foreach ($focusParents as $focusParentId) {
                if (in_array($focusParentId, $spousesMap[$ownerParentId] ?? [], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeParentRelationshipSubtype(?string $subtype): ?string
    {
        $normalized = Str::lower(trim((string) $subtype));

        return match ($normalized) {
            '', 'birth', 'biological' => null,
            'adopted', 'adoptive' => 'adoptive',
            'foster' => 'foster',
            'guardian', 'guardianship' => 'guardian',
            'step', 'stepchild', 'step-parent', 'step parent' => 'step',
            'sealing', 'sealed' => 'sealing',
            default => $normalized !== '' ? $normalized : null,
        };
    }

    private function countsAsLineageParent(?string $subtype): bool
    {
        return ! in_array($this->normalizeParentRelationshipSubtype($subtype), ['step', 'foster', 'guardian'], true);
    }

    private function extendedBloodRelativeLabel(Person $focusPerson, Person $ownerPerson, array $parentsMap): ?string
    {
        $commonAncestorDepths = $this->closestCommonAncestorDepths($ownerPerson->id, $focusPerson->id, $parentsMap);

        if ($commonAncestorDepths === null) {
            return null;
        }

        [$ownerDepth, $focusDepth] = $commonAncestorDepths;

        if ($focusDepth === 1 && $ownerDepth >= 2) {
            return $this->avuncularLabel($focusPerson, $ownerDepth, 'uncle', 'aunt', 'uncle or aunt');
        }

        if ($ownerDepth === 1 && $focusDepth >= 2) {
            return $this->avuncularLabel($focusPerson, $focusDepth, 'nephew', 'niece', 'nibling');
        }

        if ($ownerDepth >= 2 && $focusDepth >= 2) {
            return $this->cousinLabel($ownerDepth, $focusDepth);
        }

        return null;
    }

    private function inLawLabel(
        Person $focusPerson,
        Person $ownerPerson,
        array $parentsMap,
        array $childrenMap,
        array $spousesMap
    ): ?string {
        foreach ($spousesMap[$ownerPerson->id] ?? [] as $spouseId) {
            if (in_array($focusPerson->id, $parentsMap[$spouseId] ?? [], true)) {
                return $this->genderedRole($focusPerson, 'father-in-law', 'mother-in-law', 'parent-in-law');
            }

            if ($this->shareParent($focusPerson->id, $spouseId, $parentsMap)) {
                return $this->genderedRole($focusPerson, 'brother-in-law', 'sister-in-law', 'sibling-in-law');
            }
        }

        foreach ($childrenMap[$ownerPerson->id] ?? [] as $childId) {
            if (in_array($focusPerson->id, $spousesMap[$childId] ?? [], true)) {
                return $this->genderedRole($focusPerson, 'son-in-law', 'daughter-in-law', 'child-in-law');
            }
        }

        foreach ($parentsMap[$ownerPerson->id] ?? [] as $parentId) {
            foreach ($childrenMap[$parentId] ?? [] as $siblingId) {
                if ($siblingId === $ownerPerson->id) {
                    continue;
                }

                if (in_array($focusPerson->id, $spousesMap[$siblingId] ?? [], true)) {
                    return $this->genderedRole($focusPerson, 'brother-in-law', 'sister-in-law', 'sibling-in-law');
                }
            }
        }

        return null;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function closestCommonAncestorDepths(string $leftId, string $rightId, array $parentsMap): ?array
    {
        $leftDepths = $this->ancestorDepthMap($leftId, $parentsMap);
        $rightDepths = $this->ancestorDepthMap($rightId, $parentsMap);
        $best = null;

        foreach ($leftDepths as $ancestorId => $leftDepth) {
            $rightDepth = $rightDepths[$ancestorId] ?? null;

            if ($rightDepth === null || $leftDepth === 0 || $rightDepth === 0) {
                continue;
            }

            $candidate = [$leftDepth, $rightDepth];

            if (
                $best === null
                || array_sum($candidate) < array_sum($best)
                || (array_sum($candidate) === array_sum($best) && max($candidate) < max($best))
            ) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @return array<string, int>
     */
    private function ancestorDepthMap(string $personId, array $parentsMap): array
    {
        $depths = [$personId => 0];
        $queue = [[$personId, 0]];

        while ($queue !== []) {
            [$currentId, $depth] = array_shift($queue);

            foreach ($parentsMap[$currentId] ?? [] as $parentId) {
                if (array_key_exists($parentId, $depths) && $depths[$parentId] <= $depth + 1) {
                    continue;
                }

                $depths[$parentId] = $depth + 1;
                $queue[] = [$parentId, $depth + 1];
            }
        }

        return $depths;
    }

    private function avuncularLabel(Person $person, int $ownerDepth, string $male, string $female, string $neutral): string
    {
        if ($ownerDepth === 2) {
            return $this->genderedRole($person, $male, $female, $neutral);
        }

        return str_repeat('great-', $ownerDepth - 2).$this->genderedRole($person, $male, $female, $neutral);
    }

    private function cousinLabel(int $ownerDepth, int $focusDepth): string
    {
        $cousinNumber = min($ownerDepth, $focusDepth) - 1;
        $removal = abs($ownerDepth - $focusDepth);
        $label = $cousinNumber === 1 ? 'first cousin' : $this->ordinal($cousinNumber).' cousin';

        if ($removal === 0) {
            return $label;
        }

        return $label.' '.$this->removalLabel($removal).' removed';
    }

    private function removalLabel(int $removal): string
    {
        return match ($removal) {
            1 => 'once',
            2 => 'twice',
            default => $removal.' times',
        };
    }

    private function ordinal(int $value): string
    {
        $mod100 = $value % 100;

        if ($mod100 >= 11 && $mod100 <= 13) {
            return $value.'th';
        }

        return match ($value % 10) {
            1 => $value.'st',
            2 => $value.'nd',
            3 => $value.'rd',
            default => $value.'th',
        };
    }

    /**
     * @param  array<int, Person>  $peopleById
     * @param  array<int, list<int>>  $parentsMap
     * @param  array<int, list<int>>  $childrenMap
     * @param  array<int, list<int>>  $spousesMap
     * @param  list<int>  $collapsedIds
     * @return array{0: list<array<string, int|string|bool|null>>, 1: list<array<string, string>>, 2: array<string, int>}
     */
    private function buildChart(
        FamilyTree $tree,
        ?Person $focusPerson,
        array $peopleById,
        array $parentsMap,
        array $childrenMap,
        array $spousesMap,
        string $mode,
        int $generations,
        array $collapsedIds
    ): array {
        if (! $focusPerson) {
            return [[], [], ['width' => 1200, 'height' => 760]];
        }

        $rootRow = $mode === 'pedigree' ? $generations : 1;
        $primaryRows = [$rootRow => [$focusPerson->id]];
        $familyEdges = [];

        if ($mode === 'pedigree') {
            $current = [$focusPerson->id];

            for ($step = 1; $step <= $generations; $step++) {
                $next = [];

                foreach ($current as $childId) {
                    if ($childId !== $focusPerson->id && in_array($childId, $collapsedIds, true)) {
                        continue;
                    }

                    foreach ($parentsMap[$childId] ?? [] as $parentId) {
                        if (! in_array($parentId, $next, true)) {
                            $next[] = $parentId;
                        }

                        $familyEdges[] = ['from' => $parentId, 'to' => $childId];
                    }
                }

                if ($next === []) {
                    break;
                }

                $primaryRows[$rootRow - $step] = $next;
                $current = $next;
            }

            if (($childrenMap[$focusPerson->id] ?? []) !== []) {
                $primaryRows[$rootRow + 1] = $childrenMap[$focusPerson->id];

                foreach ($childrenMap[$focusPerson->id] as $childId) {
                    $familyEdges[] = ['from' => $focusPerson->id, 'to' => $childId];
                }
            }
        } else {
            $current = [$focusPerson->id];

            for ($step = 1; $step <= $generations; $step++) {
                $next = [];

                foreach ($current as $parentId) {
                    if ($parentId !== $focusPerson->id && in_array($parentId, $collapsedIds, true)) {
                        continue;
                    }

                    foreach ($childrenMap[$parentId] ?? [] as $childId) {
                        if (! in_array($childId, $next, true)) {
                            $next[] = $childId;
                        }

                        $familyEdges[] = ['from' => $parentId, 'to' => $childId];
                    }
                }

                if ($next === []) {
                    break;
                }

                $primaryRows[$rootRow + $step] = $next;
                $current = $next;
            }

            if (($parentsMap[$focusPerson->id] ?? []) !== []) {
                $primaryRows[$rootRow - 1] = $parentsMap[$focusPerson->id];

                foreach ($parentsMap[$focusPerson->id] as $parentId) {
                    $familyEdges[] = ['from' => $parentId, 'to' => $focusPerson->id];
                }
            }
        }

        ksort($primaryRows);

        $rows = [];
        $rowOrder = [];

        foreach ($primaryRows as $rowIndex => $personIds) {
            $rowItems = [];
            $seen = [];

            foreach ($personIds as $personId) {
                if (! array_key_exists($personId, $peopleById) || isset($seen[$personId])) {
                    continue;
                }

                $role = $personId === $focusPerson->id
                    ? 'focus'
                    : ($rowIndex < $rootRow ? 'ancestor' : ($rowIndex > $rootRow ? 'descendant' : 'focus'));

                $rowItems[] = [
                    'person_id' => $personId,
                    'role' => $role,
                    'linked_to' => null,
                    'is_primary' => true,
                ];

                $seen[$personId] = true;

                foreach ($spousesMap[$personId] ?? [] as $spouseId) {
                    if (! array_key_exists($spouseId, $peopleById) || isset($seen[$spouseId])) {
                        continue;
                    }

                    $rowItems[] = [
                        'person_id' => $spouseId,
                        'role' => 'spouse',
                        'linked_to' => $personId,
                        'is_primary' => false,
                    ];

                    $seen[$spouseId] = true;
                }
            }

            if ($rowItems !== []) {
                $rows[$rowIndex] = $rowItems;
                $rowOrder[] = $rowIndex;
            }
        }

        // Card dimensions must match CSS .workspace-tree-card { width: 200px }
        $cardWidth  = 200;
        $cardHeight = 100; // inner card (~72px) + connector button (28px)
        $spouseGap  = 10;  // pixels between a person card and their spouse card
        $unitGap    = 56;  // pixels between couple-units in the same row
        $rowSpacing = 200;
        $topPadding = 50;
        $leftPadding = 60;

        // First pass: group each row into couple-units so we can compute the
        // minimum canvas width required to fit the widest row.
        $rowUnits       = [];
        $minCanvasWidth = 1360;

        foreach ($rowOrder as $rowIndex) {
            $items             = $rows[$rowIndex];
            $spouseByLinkedTo  = [];

            foreach ($items as $item) {
                if (! $item['is_primary']) {
                    $spouseByLinkedTo[$item['linked_to']] = $item;
                }
            }

            $units      = [];
            $placedIds  = [];

            foreach ($items as $item) {
                if (! $item['is_primary']) {
                    continue;
                }

                $spouse = $spouseByLinkedTo[$item['person_id']] ?? null;
                $units[]    = ['primary' => $item, 'spouse' => $spouse];
                $placedIds[] = $item['person_id'];

                if ($spouse) {
                    $placedIds[] = $spouse['person_id'];
                }
            }

            // Orphaned spouse entries (edge-case: linked_to not in this row)
            foreach ($items as $item) {
                if (! $item['is_primary'] && ! in_array($item['person_id'], $placedIds, true)) {
                    $units[]    = ['primary' => $item, 'spouse' => null];
                }
            }

            $rowUnits[$rowIndex] = $units;
            $numUnits            = count($units);
            $totalUnitWidth      = array_sum(array_map(
                fn ($u) => $u['spouse'] ? ($cardWidth * 2 + $spouseGap) : $cardWidth,
                $units
            ));
            $rowWidth = ($leftPadding * 2) + $totalUnitWidth + (max(0, $numUnits - 1) * $unitGap);
            $minCanvasWidth = max($minCanvasWidth, $rowWidth + 80);
        }

        $canvasWidth            = $minCanvasWidth;
        $nodes                  = [];
        $nodeMap                = [];
        $primaryNodeKeyByPerson = [];
        $spouseNodeKeyByPerson  = []; // spouse-role nodes, keyed by person id
        $allNodeXByPerson       = []; // covers both primary and spouse nodes
        $spouseEdges            = [];

        // Second pass: place nodes
        foreach ($rowOrder as $visualRow => $rowIndex) {
            $units = $rowUnits[$rowIndex];

            // Sort units so each primary person sits visually above/below their parents.
            // Rows are processed top→bottom, so parent X values are already known when
            // we reach a child row.
            usort($units, function ($ua, $ub) use ($parentsMap, $allNodeXByPerson, $cardWidth): int {
                $avgParentX = function (array $unit) use ($parentsMap, $allNodeXByPerson, $cardWidth): ?float {
                    $xs = [];
                    foreach ($parentsMap[$unit['primary']['person_id']] ?? [] as $pid) {
                        if (isset($allNodeXByPerson[$pid])) {
                            $xs[] = $allNodeXByPerson[$pid] + intdiv($cardWidth, 2);
                        }
                    }
                    return $xs ? array_sum($xs) / count($xs) : null;
                };
                $xa = $avgParentX($ua);
                $xb = $avgParentX($ub);
                if ($xa === null && $xb === null) { return 0; }
                if ($xa === null) { return 1; }  // no visible parents → push right
                if ($xb === null) { return -1; }
                return $xa <=> $xb;
            });

            $y           = $topPadding + ($visualRow * $rowSpacing);
            $numUnits    = count($units);
            $unitWidths  = array_map(
                fn ($u) => $u['spouse'] ? ($cardWidth * 2 + $spouseGap) : $cardWidth,
                $units
            );
            $totalUnitWidth = array_sum($unitWidths);
            $availWidth     = $canvasWidth - ($leftPadding * 2);

            if ($numUnits <= 1) {
                $gapBetween  = 0;
                $startOffset = max(0, intdiv($availWidth - ($unitWidths[0] ?? 0), 2));
            } else {
                $gapBetween    = max($unitGap, intdiv($availWidth - $totalUnitWidth, $numUnits - 1));
                $rowTotalWidth = $totalUnitWidth + $gapBetween * ($numUnits - 1);
                $startOffset   = max(0, intdiv($availWidth - $rowTotalWidth, 2));
            }

            $curUnitX = $leftPadding + $startOffset;

            foreach ($units as $uIndex => $unit) {
                $primaryItem = $unit['primary'];
                $person      = $peopleById[$primaryItem['person_id']];
                $x           = $curUnitX;
                $key         = "{$rowIndex}:{$person->id}:{$primaryItem['role']}";

                $parentIds = $parentsMap[$person->id] ?? [];
                $hasFather = false;
                $hasMother = false;

                foreach ($parentIds as $parentId) {
                    $par = $peopleById[$parentId] ?? null;

                    if (! $par) {
                        continue;
                    }

                    if ($par->sex === 'male') {
                        $hasFather = true;
                    }

                    if ($par->sex === 'female') {
                        $hasMother = true;
                    }
                }

                $branchCount = $primaryItem['is_primary']
                    ? count($mode === 'pedigree' ? ($parentsMap[$person->id] ?? []) : ($childrenMap[$person->id] ?? []))
                    : 0;

                $nodes[] = $this->makeNode(
                    $tree, $person, $x, $y,
                    $person->id === $focusPerson->id,
                    $primaryItem['role'],
                    $mode, $generations, $collapsedIds,
                    $branchCount, true,
                    $hasFather, $hasMother
                );

                $nodeMap[$key] = ['x' => $x, 'y' => $y, 'person_id' => $person->id, 'role' => $primaryItem['role']];
                $primaryNodeKeyByPerson[$person->id] = $key;
                $allNodeXByPerson[$person->id]       = $x;

                // Spouse card, placed immediately to the right of the primary
                if ($unit['spouse']) {
                    $spouseItem   = $unit['spouse'];
                    $spousePerson = $peopleById[$spouseItem['person_id']];
                    $spouseX      = $x + $cardWidth + $spouseGap;
                    $spouseKey    = "{$rowIndex}:{$spousePerson->id}:spouse";

                    $spouseParentIds = $parentsMap[$spousePerson->id] ?? [];
                    $spouseHasFather = false;
                    $spouseHasMother = false;

                    foreach ($spouseParentIds as $pid) {
                        $par = $peopleById[$pid] ?? null;

                        if (! $par) {
                            continue;
                        }

                        if ($par->sex === 'male') {
                            $spouseHasFather = true;
                        }

                        if ($par->sex === 'female') {
                            $spouseHasMother = true;
                        }
                    }

                    $nodes[] = $this->makeNode(
                        $tree, $spousePerson, $spouseX, $y,
                        false, 'spouse',
                        $mode, $generations, $collapsedIds,
                        0, false,
                        $spouseHasFather, $spouseHasMother
                    );

                    $nodeMap[$spouseKey]                      = ['x' => $spouseX, 'y' => $y, 'person_id' => $spousePerson->id, 'role' => 'spouse'];
                    $allNodeXByPerson[$spousePerson->id]      = $spouseX;
                    $spouseNodeKeyByPerson[$spousePerson->id] = $spouseKey;

                    $spouseEdges[] = ['source' => $person->id, 'target' => $spouseKey];
                }

                $curUnitX += $unitWidths[$uIndex] + $gapBetween;
            }
        }

        $lines = [];

        // Group family edges by child so we can draw one line from a couple's
        // midpoint instead of two separate lines when both parents are spouses.
        $childParentsMap = [];

        foreach ($familyEdges as $edge) {
            $childParentsMap[$edge['to']][] = $edge['from'];
        }

        foreach ($childParentsMap as $childId => $parentIds) {
            $childNodeKey = $primaryNodeKeyByPerson[$childId] ?? $spouseNodeKeyByPerson[$childId] ?? null;

            if ($childNodeKey === null) {
                continue;
            }

            $childNode = $nodeMap[$childNodeKey];
            $childTopX = $childNode['x'] + intdiv($cardWidth, 2);
            $childTopY = $childNode['y'];
            $parentIds = array_values(array_unique($parentIds));

            // Two parents who are a known couple → single line from midpoint
            if (count($parentIds) === 2) {
                [$p1Id, $p2Id] = $parentIds;
                $areCoupled    = in_array($p2Id, $spousesMap[$p1Id] ?? [], true)
                    || in_array($p1Id, $spousesMap[$p2Id] ?? [], true);

                if ($areCoupled
                    && isset($allNodeXByPerson[$p1Id], $allNodeXByPerson[$p2Id])
                ) {
                    // Use whichever parent has a node to get the row Y
                    $anchorId      = isset($primaryNodeKeyByPerson[$p1Id]) ? $p1Id : $p2Id;
                    $anchorNodeKey = $primaryNodeKeyByPerson[$anchorId] ?? $spouseNodeKeyByPerson[$anchorId] ?? null;

                    if ($anchorNodeKey !== null) {
                        $anchorNode = $nodeMap[$anchorNodeKey];
                        $p1CenterX  = $allNodeXByPerson[$p1Id] + intdiv($cardWidth, 2);
                        $p2CenterX  = $allNodeXByPerson[$p2Id] + intdiv($cardWidth, 2);
                        $midX       = intdiv($p1CenterX + $p2CenterX, 2);
                        $barY       = $anchorNode['y'] + $cardHeight;

                        // Horizontal bar at parent-row bottom visually bridging both parents
                        if ($p1CenterX !== $p2CenterX) {
                            $lines[] = [
                                'type'   => 'family',
                                'path'   => 'M ' . min($p1CenterX, $p2CenterX) . ' ' . $barY
                                          . ' H ' . max($p1CenterX, $p2CenterX),
                                'stroke' => '#5a8aaa',
                            ];
                        }

                        // Drop from midpoint of bar down to child
                        $lines[] = $this->makeOrthogonalLine($midX, $barY, $childTopX, $childTopY);

                        continue;
                    }
                }
            }

            // Fallback: one line per parent
            foreach ($parentIds as $parentId) {
                $parentNodeKey = $primaryNodeKeyByPerson[$parentId] ?? $spouseNodeKeyByPerson[$parentId] ?? null;

                if ($parentNodeKey === null) {
                    continue;
                }

                $parentNode = $nodeMap[$parentNodeKey];
                $lines[] = $this->makeOrthogonalLine(
                    $parentNode['x'] + intdiv($cardWidth, 2),
                    $parentNode['y'] + $cardHeight,
                    $childTopX,
                    $childTopY
                );
            }
        }

        // Spouse connectors: draw a pink horizontal line for every spouse pair
        // that is in the same visual row, whether or not they are an adjacent unit.
        $drawnSpousePairs = [];

        foreach ($primaryNodeKeyByPerson as $personId => $primaryKey) {
            $fromNode = $nodeMap[$primaryKey];

            foreach ($spousesMap[$personId] ?? [] as $spouseId) {
                $spouseKey = $primaryNodeKeyByPerson[$spouseId] ?? $spouseNodeKeyByPerson[$spouseId] ?? null;

                if ($spouseKey === null) {
                    continue;
                }

                $toNode  = $nodeMap[$spouseKey];

                // Only connect spouses in the same visual row
                if ($toNode['y'] !== $fromNode['y']) {
                    continue;
                }

                // Avoid drawing the same pair twice
                $pairKey = min($personId, $spouseId) . ':' . max($personId, $spouseId);

                if (isset($drawnSpousePairs[$pairKey])) {
                    continue;
                }

                $drawnSpousePairs[$pairKey] = true;

                $lineY  = $fromNode['y'] + 42;
                $leftX  = min($fromNode['x'] + $cardWidth, $toNode['x'] + $cardWidth);
                $rightX = max($fromNode['x'], $toNode['x']);

                if ($rightX <= $leftX) {
                    continue;
                }

                $lines[] = [
                    'type'   => 'spouse',
                    'path'   => 'M ' . $leftX . ' ' . $lineY . ' H ' . $rightX,
                    'stroke' => '#b88a8a',
                ];
            }
        }

        $height = max(760, ($topPadding * 2) + (count($rowOrder) * $rowSpacing) + 60);

        return [$nodes, $lines, ['width' => $canvasWidth, 'height' => $height]];
    }

    /**
     * @param  list<int>  $collapsedIds
     * @return array<string, int|string|bool|null>
     */
    private function makeNode(
        FamilyTree $tree,
        Person $person,
        int $x,
        int $y,
        bool $isFocus,
        string $role,
        string $mode,
        int $generations,
        array $collapsedIds,
        int $branchCount,
        bool $isPrimary,
        bool $hasFather,
        bool $hasMother
    ): array {
        $isCollapsed = in_array($person->id, $collapsedIds, true);

        return [
            'id' => $person->id,
            'name' => $person->display_name,
            'surname' => $person->surname,
            'headline' => $person->headline ?: 'Family profile',
            'life_span' => $person->life_span,
            'sex' => $person->sex,
            'birth_place' => $person->birth_place ?: 'Place unknown',
            'is_owner' => $tree->owner_person_id === $person->id,
            'focus_url' => $this->treeUrl($tree, [
                'focus' => $person->id,
                'mode' => $mode,
                'generations' => $generations,
                'collapsed' => $collapsedIds,
            ]),
            'branch_url' => $isPrimary && $branchCount > 0
                ? $this->treeUrl($tree, [
                    'focus' => $person->id,
                    'mode' => $mode,
                    'generations' => $generations,
                    'collapsed' => $this->toggleCollapsedIds($collapsedIds, $person->id),
                ])
                : null,
            'x' => $x,
            'y' => $y,
            'is_focus' => $isFocus,
            'role' => $role,
            'branch_count' => $branchCount,
            'is_collapsed' => $isCollapsed,
            'can_expand' => $isPrimary && $branchCount > 0,
            'has_father' => $hasFather,
            'has_mother' => $hasMother,
        ];
    }

    /**
     * @param  list<int>  $collapsedIds
     * @return list<string>
     */
    private function toggleCollapsedIds(array $collapsedIds, string $personId): array
    {
        if (in_array($personId, $collapsedIds, true)) {
            return array_values(array_filter($collapsedIds, fn (string $id) => $id !== $personId));
        }

        $collapsedIds[] = $personId;
        $collapsedIds = array_values(array_unique($collapsedIds));
        sort($collapsedIds);

        return $collapsedIds;
    }

    private function treeUrl(FamilyTree $tree, array $query): string
    {
        if (isset($query['collapsed']) && is_array($query['collapsed'])) {
            /** @var list<int> $collapsed */
            $collapsed = $query['collapsed'];
            $query['collapsed'] = $collapsed === [] ? null : implode(',', $collapsed);
        }

        return route('trees.show', ['tree' => $tree] + array_filter($query, fn ($value) => $value !== null && $value !== ''));
    }

    private function ensureOwnerPerson(FamilyTree $tree, User $user): void
    {
        if ($tree->owner_person_id !== null) {
            $ownerExists = $tree->people()
                ->whereKey($tree->owner_person_id)
                ->exists();

            if ($ownerExists) {
                return;
            }
        }

        $existingOwner = $tree->people()
            ->where('created_by', $user->id)
            ->orderBy('id')
            ->first();

        if ($existingOwner) {
            $tree->update(['owner_person_id' => $existingOwner->id]);

            return;
        }

        $ownerPerson = $tree->people()->create([
            'created_by' => $user->id,
            ...$this->ownerPersonSeed($user),
            'sex' => 'unknown',
            'is_living' => true,
            'headline' => 'Account holder',
            'notes' => 'This profile was created automatically for the tree owner.',
        ]);

        $tree->update(['owner_person_id' => $ownerPerson->id]);
        $tree->refresh();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitDisplayName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, fn (string $part) => $part !== ''));

        if ($parts === []) {
            return ['Account', 'Owner'];
        }

        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $givenName = array_shift($parts);

        return [$givenName, Str::of(implode(' ', $parts))->trim()->toString()];
    }

    /**
     * @return array{given_name:string,middle_name:?string,surname:string,birth_date:?string}
     */
    private function ownerPersonSeed(User $user): array
    {
        [$fallbackGivenName, $fallbackSurname] = $this->splitDisplayName($user->name);

        return [
            'given_name' => $user->first_name ?: $fallbackGivenName,
            'middle_name' => $user->middle_name ?: null,
            'surname' => $user->last_name ?: $fallbackSurname,
            'birth_date' => $user->birth_date?->format('Y-m-d'),
        ];
    }

    private function isDisposablePlaceholderOwner(FamilyTree $tree, Person $person): bool
    {
        if ($person->headline !== 'Account holder' || $person->notes !== 'This profile was created automatically for the tree owner.') {
            return false;
        }

        if ($tree->relationships()
            ->where(function ($query) use ($person) {
                $query->where('person_id', $person->id)
                    ->orWhere('related_person_id', $person->id);
            })
            ->exists()) {
            return false;
        }

        if ($person->mediaItems()->exists() || $person->sourceCitations()->exists()) {
            return false;
        }

        return true;
    }

    private function ownerCandidateScore(Person $person, User $user, FamilyTree $tree): int
    {
        if ($person->id === $tree->owner_person_id) {
            return -1000;
        }

        $score = $person->is_living ? 25 : 0;

        $nameParts = collect([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
            $user->name,
        ])
            ->filter()
            ->flatMap(fn (string $value) => preg_split('/\s+/', mb_strtolower(trim($value))) ?: [])
            ->filter()
            ->unique()
            ->values();

        $normalizedFirstName = $user->first_name ? $this->normalizeOwnerCandidateText($user->first_name) : null;
        $normalizedLastName = $user->last_name ? $this->normalizeOwnerCandidateText($user->last_name) : null;

        $personParts = collect([
            $person->given_name,
            $person->middle_name,
            $person->surname,
            $person->birth_surname,
        ])
            ->filter()
            ->map(fn (string $value) => $this->normalizeOwnerCandidateText($value))
            ->values();

        foreach ($nameParts as $part) {
            $normalizedPart = $this->normalizeOwnerCandidateText($part);

            if ($normalizedPart === '') {
                continue;
            }

            if ($personParts->contains($normalizedPart)) {
                $score += 14;
                continue;
            }

            if ($personParts->contains(fn (string $value) => str_starts_with($value, $normalizedPart))) {
                $score += 6;
            }
        }

        if ($normalizedFirstName && $this->normalizeOwnerCandidateText($person->given_name) === $normalizedFirstName) {
            $score += 90;
        }

        if ($normalizedLastName && (
            $this->normalizeOwnerCandidateText($person->surname) === $normalizedLastName
            || $this->normalizeOwnerCandidateText((string) $person->birth_surname) === $normalizedLastName
        )) {
            $score += 25;
        }

        if ($user->birth_date && $person->birth_date) {
            $yearDifference = abs($user->birth_date->year - $person->birth_date->year);

            if ($user->birth_date->isSameDay($person->birth_date)) {
                $score += 140;
            } elseif ($yearDifference === 0) {
                $score += 70;
            } elseif ($yearDifference <= 1) {
                $score += 35;
            } elseif ($yearDifference >= 20) {
                $score -= 25;
            }
        } elseif ($user->birth_date && $person->birth_date_text) {
            $birthYear = (int) preg_replace('/\D+/', '', $person->birth_date_text);

            if ($birthYear > 0) {
                $yearDifference = abs($user->birth_date->year - $birthYear);

                if ($yearDifference === 0) {
                    $score += 40;
                } elseif ($yearDifference <= 1) {
                    $score += 20;
                } elseif ($yearDifference >= 20) {
                    $score -= 15;
                }
            }
        }

        return $score;
    }

    private function ownerCandidateMatchLabel(int $score): string
    {
        return match (true) {
            $score >= 180 => 'Best match',
            $score >= 120 => 'Likely you',
            $score >= 70 => 'Possible match',
            default => '',
        };
    }

    private function normalizeOwnerCandidateText(?string $value): string
    {
        $value = Str::of((string) $value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->toString();

        return trim($value);
    }

    /**
     * @return list<int>
     */
    private function spreadPositions(int $count, int $canvasWidth, int $cardWidth): array
    {
        if ($count <= 0) {
            return [];
        }

        if ($count === 1) {
            return [intdiv($canvasWidth - $cardWidth, 2)];
        }

        $availableWidth = $canvasWidth - $cardWidth;
        $step = intdiv($availableWidth, max($count - 1, 1));
        $positions = [];

        for ($i = 0; $i < $count; $i++) {
            $positions[] = $i * $step;
        }

        return $positions;
    }

    /**
     * @return array<string, string>
     */
    private function makeOrthogonalLine(int $x1, int $y1, int $x2, int $y2): array
    {
        $midY = intdiv($y1 + $y2, 2);

        return [
            'type' => 'family',
            'path' => "M {$x1} {$y1} V {$midY} H {$x2} V {$y2}",
            'stroke' => '#5a8aaa',
        ];
    }
}
