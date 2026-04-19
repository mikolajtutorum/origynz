<?php

namespace App\Http\Controllers;

use App\Enums\TreeAccessLevel;
use App\Enums\TreePermission;
use App\Models\FamilyTree;
use App\Models\FamilyTreeInvitation;
use App\Models\FamilyTreeMembershipRequest;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TreeManagerController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function show(Request $request, FamilyTree $tree): View
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $pendingInvites = $tree->invitations()
            ->with('inviter')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $pendingRequests = $tree->membershipRequests()
            ->where('status', 'pending')
            ->latest()
            ->get();

        $reviewedRequests = $tree->membershipRequests()
            ->with('reviewer')
            ->whereIn('status', ['approved', 'declined'])
            ->latest('reviewed_at')
            ->get();

        $activityByUserId = DB::table('sessions')
            ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id');

        $memberRows = $this->treeAccess->members($tree)
            ->sortBy('name')
            ->map(function (User $member) use ($activityByUserId, $request): array {
                $accessLevel = TreeAccessLevel::from((string) $member->getAttribute('tree_access_level'));

                return [
                    'name' => $member->name,
                    'access_level' => $member->id === $request->user()->id
                        ? $accessLevel->label().' (' . __('you') . ')'
                        : $accessLevel->label(),
                    'last_visited' => $this->formatLastVisited($activityByUserId[$member->id] ?? null),
                ];
            })
            ->values()
            ->all();

        return view('trees.managers', [
            'tree' => $tree,
            'memberRows' => $memberRows,
            'pendingInvites' => $pendingInvites,
            'pendingRequests' => $pendingRequests,
            'reviewedRequests' => $reviewedRequests,
        ]);
    }

    public function storeInvite(Request $request, FamilyTree $tree): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $data = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('family_tree_invitations', 'email')->where(
                    fn ($query) => $query
                        ->where('family_tree_id', $tree->id)
                        ->whereIn('status', ['pending', 'accepted'])
                ),
            ],
            'access_level' => ['required', Rule::in([TreeAccessLevel::Manager->value, TreeAccessLevel::Observer->value])],
        ]);

        $email = strtolower($data['email']);
        $invitee = User::query()->where('email', $email)->first();
        $status = 'pending';
        $acceptedAt = null;

        if ($invitee) {
            $this->treeAccess->grantTreeAccess($invitee, $tree, TreeAccessLevel::from($data['access_level']));
            $status = 'accepted';
            $acceptedAt = now();
        }

        $tree->invitations()->create([
            'invited_by' => $request->user()->id,
            'email' => $email,
            'access_level' => $data['access_level'],
            'status' => $status,
            'accepted_at' => $acceptedAt,
        ]);

        return redirect()
            ->route('trees.managers.show', $tree)
            ->with('status', $invitee ? 'Access granted to existing member.' : 'Invitation created.');
    }

    public function reviewRequest(Request $request, FamilyTree $tree, FamilyTreeMembershipRequest $membershipRequest): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);
        abort_unless($membershipRequest->family_tree_id === $tree->id, 404);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'declined'])],
        ]);

        $membershipRequest->update([
            'status' => $data['decision'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($data['decision'] === 'approved') {
            $requester = User::query()
                ->where('email', strtolower($membershipRequest->requester_email))
                ->first();

            if ($requester) {
                $this->treeAccess->grantTreeAccess($requester, $tree, TreeAccessLevel::Observer);
            }
        }

        return redirect()
            ->route('trees.managers.show', $tree)
            ->with('status', 'Membership request reviewed.');
    }

    private function formatLastVisited(null|int|string $timestamp): string
    {
        if (! $timestamp) {
            return 'No recorded visit';
        }

        return CarbonImmutable::createFromTimestamp((int) $timestamp)->diffForHumans();
    }
}
