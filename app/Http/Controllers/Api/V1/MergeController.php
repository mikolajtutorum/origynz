<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MergeCandidateStatus;
use App\Enums\SiteRole;
use App\Enums\TreePermission;
use App\Http\Controllers\Controller;
use App\Models\FamilyTree;
use App\Models\MergeCandidate;
use App\Models\Person;
use App\Services\DuplicateDetectionService;
use App\Services\PersonMergeService;
use App\Services\SuggestedConnectionService;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MergeController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
        private readonly DuplicateDetectionService $detector,
        private readonly PersonMergeService $merger,
        private readonly SuggestedConnectionService $suggestions,
    ) {}

    /**
     * Pending duplicate candidates where both people belong to the given tree.
     */
    public function treeCandidates(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $candidates = MergeCandidate::query()
            ->where('status', MergeCandidateStatus::Pending)
            ->whereHas('personA', fn ($q) => $q->where('family_tree_id', $tree->id)->whereNull('merged_into_id'))
            ->whereHas('personB', fn ($q) => $q->where('family_tree_id', $tree->id)->whereNull('merged_into_id'))
            ->with(['personA.familyTree:id,name', 'personB.familyTree:id,name'])
            ->orderByDesc('similarity_score')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $candidates->map(fn (MergeCandidate $c) => $this->candidateSummary($c))->values(),
        ]);
    }

    /**
     * Scan a tree for within-tree duplicates and persist candidates.
     */
    public function scanTree(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $created = $this->detector->scanTree($tree);

        return response()->json(['created' => $created]);
    }

    /**
     * Cross-tree "suggested connections" involving the current user's trees.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $candidates = $this->suggestions->forUser($request->user())
            ->filter(fn (MergeCandidate $c) => $c->personA->family_tree_id !== $c->personB->family_tree_id);

        return response()->json([
            'data' => $candidates->map(fn (MergeCandidate $c) => $this->candidateSummary($c))->values(),
        ]);
    }

    /**
     * Field-by-field comparison for a candidate pair, plus what would migrate.
     */
    public function preview(Request $request, MergeCandidate $candidate): JsonResponse
    {
        $candidate->load(['personA.familyTree:id,name', 'personB.familyTree:id,name']);
        $this->authorizeCandidate($request, $candidate, requireBoth: true);

        $a = $candidate->personA;
        $b = $candidate->personB;

        $fields = collect($this->merger->mergeableFields())->map(function (string $field) use ($a, $b) {
            $valueA = $this->stringifyField($a, $field);
            $valueB = $this->stringifyField($b, $field);

            return [
                'field' => $field,
                'label' => $this->fieldLabel($field),
                'value_a' => $valueA,
                'value_b' => $valueB,
                'conflict' => $valueA !== null && $valueB !== null && $valueA !== $valueB,
                // Sensible default: keep whichever side has a value (A wins ties).
                'suggested' => $valueA !== null ? 'a' : ($valueB !== null ? 'b' : 'a'),
            ];
        })->values();

        return response()->json([
            'id' => $candidate->id,
            'similarity_score' => $candidate->similarity_score,
            'person_a' => $this->personHeader($a),
            'person_b' => $this->personHeader($b),
            'fields' => $fields,
        ]);
    }

    /**
     * Execute the merge for a candidate.
     */
    public function merge(Request $request, MergeCandidate $candidate): JsonResponse
    {
        $candidate->load(['personA.familyTree', 'personB.familyTree']);
        $this->authorizeCandidate($request, $candidate, requireBoth: true);

        $validated = $request->validate([
            'surviving' => 'required|in:a,b',
            'decisions' => 'array',
            'decisions.*' => 'in:a,b',
        ]);

        [$surviving, $absorbed] = $validated['surviving'] === 'a'
            ? [$candidate->personA, $candidate->personB]
            : [$candidate->personB, $candidate->personA];

        // The service reads decisions as 'a' = surviving, 'b' = absorbed. Our UI
        // decisions reference the candidate's person A/B, so translate them.
        $serviceDecisions = [];
        foreach (($validated['decisions'] ?? []) as $field => $chosen) {
            $serviceDecisions[$field] = $chosen === $validated['surviving'] ? 'a' : 'b';
        }

        $result = $this->merger->execute($surviving, $absorbed, $request->user(), $serviceDecisions);

        return response()->json([
            'surviving_person_id' => $result->id,
            'absorbed_person_id' => $absorbed->id,
        ]);
    }

    /**
     * Dismiss a candidate without merging.
     */
    public function dismiss(Request $request, MergeCandidate $candidate): JsonResponse
    {
        $candidate->load(['personA.familyTree', 'personB.familyTree']);
        $this->authorizeCandidate($request, $candidate, requireBoth: false);

        $candidate->update(['status' => MergeCandidateStatus::Dismissed]);

        return response()->json(['status' => $candidate->status]);
    }

    // -------------------------------------------------------------------------

    /**
     * Merging rewrites both people, so it needs manage rights on both trees
     * (or a site-level admin/curator). Dismissing only needs one side.
     */
    private function authorizeCandidate(Request $request, MergeCandidate $candidate, bool $requireBoth): void
    {
        $user = $request->user();

        if ($this->treeAccess->hasSiteRolePublic($user, [SiteRole::SuperAdmin, SiteRole::Admin, SiteRole::Curator])) {
            return;
        }

        $canA = $this->treeAccess->can($user, $candidate->personA->familyTree, TreePermission::Manage);
        $canB = $this->treeAccess->can($user, $candidate->personB->familyTree, TreePermission::Manage);

        $allowed = $requireBoth ? ($canA && $canB) : ($canA || $canB);

        abort_unless($allowed, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function candidateSummary(MergeCandidate $c): array
    {
        return [
            'id' => $c->id,
            'similarity_score' => $c->similarity_score,
            'person_a' => $this->personHeader($c->personA),
            'person_b' => $this->personHeader($c->personB),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function personHeader(Person $p): array
    {
        return [
            'id' => $p->id,
            'display_name' => $p->display_name,
            'life_span' => $p->life_span,
            'birth_place' => $p->birth_place,
            'sex' => $p->sex,
            'tree_id' => $p->family_tree_id,
            'tree_name' => $p->familyTree->name,
            'counts' => [
                'relationships' => $p->outgoingRelationships()->count() + $p->incomingRelationships()->count(),
                'media' => $p->mediaItems()->count(),
                'events' => $p->events()->count(),
                'sources' => $p->sourceCitations()->count(),
            ],
        ];
    }

    private function stringifyField(Person $p, string $field): ?string
    {
        $value = $p->{$field};

        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    private function fieldLabel(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }
}
