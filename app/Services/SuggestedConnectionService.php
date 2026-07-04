<?php

namespace App\Services;

use App\Models\FamilyTree;
use App\Models\MergeCandidate;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;

class SuggestedConnectionService
{
    public function __construct(
        private readonly DuplicateDetectionService $detector,
    ) {}

    /**
     * Return pending MergeCandidates where at least one person belongs to a tree
     * owned/managed by $user, suggesting a connection with someone in another tree.
     *
     * @return Collection<int, MergeCandidate>
     */
    public function forUser(User $user): Collection
    {
        $userTreeIds = FamilyTree::where('user_id', $user->id)->pluck('id');

        return MergeCandidate::query()
            ->where('status', 'pending')
            ->where(function ($q) use ($userTreeIds): void {
                $q->whereHas('personA', fn ($sq) => $sq->whereIn('family_tree_id', $userTreeIds))
                    ->orWhereHas('personB', fn ($sq) => $sq->whereIn('family_tree_id', $userTreeIds));
            })
            ->with([
                'personA.familyTree:id,name',
                'personB.familyTree:id,name',
            ])
            ->orderByDesc('similarity_score')
            ->limit(20)
            ->get();
    }

    /**
     * Compute and persist suggestions for people in $tree against the global pool.
     * Intended to be called after a new tree is enabled for the Global Tree.
     */
    public function scanForTree(FamilyTree $tree): int
    {
        return $this->detector->scan();
    }
}
