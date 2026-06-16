<?php

namespace App\Http\Controllers;

use App\Enums\MergeCandidateStatus;
use App\Enums\SiteRole;
use App\Models\MergeCandidate;
use App\Models\Person;
use App\Services\DuplicateDetectionService;
use App\Services\PersonMergeService;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonMergeController extends Controller
{
    public function __construct(
        private readonly DuplicateDetectionService $detector,
        private readonly PersonMergeService $mergeService,
        private readonly TreeAccessService $access,
    ) {}

    /**
     * List all pending merge candidates.
     */
    public function index(Request $request): View
    {
        $this->requireCuratorOrAdmin($request);

        $candidates = MergeCandidate::query()
            ->where('status', MergeCandidateStatus::Pending)
            ->with([
                'personA.familyTree:id,name',
                'personB.familyTree:id,name',
            ])
            ->orderByDesc('similarity_score')
            ->paginate(25);

        return view('global-tree.merge.index', ['candidates' => $candidates]);
    }

    /**
     * Show the side-by-side conflict-resolution UI for a candidate.
     */
    public function review(Request $request, MergeCandidate $candidate): View
    {
        $this->requireCuratorOrAdmin($request);

        $candidate->load([
            'personA.familyTree:id,name',
            'personA.sourceCitations',
            'personA.events',
            'personA.mediaItems',
            'personB.familyTree:id,name',
            'personB.sourceCitations',
            'personB.events',
            'personB.mediaItems',
        ]);

        return view('global-tree.merge.review', [
            'candidate'     => $candidate,
            'fields'        => $this->mergeService->mergeableFields(),
        ]);
    }

    /**
     * Execute the merge after the user submits field decisions.
     */
    public function execute(Request $request, MergeCandidate $candidate): RedirectResponse
    {
        $this->requireCuratorOrAdmin($request);

        $validated = $request->validate([
            'surviving' => 'required|in:a,b',
            'decisions' => 'array',
            'decisions.*' => 'in:a,b',
        ]);

        $isAsurviving = $validated['surviving'] === 'a';
        $surviving    = $isAsurviving ? $candidate->personA : $candidate->personB;
        $absorbed     = $isAsurviving ? $candidate->personB : $candidate->personA;

        $rawDecisions = $validated['decisions'] ?? [];

        // If user chose B as surviving, flip a/b in decisions to maintain semantics
        if (! $isAsurviving) {
            $rawDecisions = array_map(
                fn (string $v) => $v === 'a' ? 'b' : 'a',
                $rawDecisions,
            );
        }

        $this->mergeService->execute($surviving, $absorbed, $request->user(), $rawDecisions);

        return redirect()->route('global-tree.merge.index')
            ->with('success', __('Profiles merged successfully.'));
    }

    /**
     * Dismiss a candidate (not a duplicate).
     */
    public function dismiss(Request $request, MergeCandidate $candidate): RedirectResponse
    {
        $this->requireCuratorOrAdmin($request);

        $candidate->update(['status' => MergeCandidateStatus::Dismissed]);

        return back()->with('success', __('Candidate dismissed.'));
    }

    /**
     * Trigger a fresh duplicate scan.
     */
    public function scan(Request $request): RedirectResponse
    {
        $this->requireCuratorOrAdmin($request);

        $count = $this->detector->scan();

        return redirect()->route('global-tree.merge.index')
            ->with('success', __(':count new duplicate candidates found.', ['count' => $count]));
    }

    // -------------------------------------------------------------------------

    private function requireCuratorOrAdmin(Request $request): void
    {
        $user = $request->user();

        $isCurator = $this->access->hasSiteRolePublic($user, [
            SiteRole::SuperAdmin, SiteRole::Admin, SiteRole::Curator,
        ]);

        abort_unless($isCurator, 403);
    }
}
