<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TreeAccessLevel;
use App\Enums\TreePermission;
use App\Http\Controllers\Controller;
use App\Models\FamilyTree;
use App\Models\FamilyTreeInvitation;
use App\Models\FamilyTreeMembershipRequest;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TreeCollaborationController extends Controller
{
    public function __construct(private readonly TreeAccessService $treeAccess) {}

    // ── Invitations ─────────────────────────────────────────────────────────

    public function invitations(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $invitations = $tree->invitations()
            ->with('inviter:id,name')
            ->latest()
            ->get()
            ->map(fn (FamilyTreeInvitation $i) => [
                'id' => $i->id,
                'email' => $i->email,
                'access_level' => $i->access_level,
                'status' => $i->status,
                'invited_by' => $i->inviter?->name,
                'accepted_at' => $i->accepted_at?->toIso8601String(),
                'created_at' => $i->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $invitations]);
    }

    public function invite(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'access_level' => ['required', Rule::in([TreeAccessLevel::Manager->value, TreeAccessLevel::Observer->value])],
        ]);

        $email = strtolower($validated['email']);

        if ($tree->invitations()->where('email', $email)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['email' => [__('This person already has a pending invitation.')]]);
        }

        $invitation = $tree->invitations()->create([
            'invited_by' => $request->user()->id,
            'email' => $email,
            'access_level' => $validated['access_level'],
            'status' => 'pending',
        ]);

        // If they already have an account, grant access immediately.
        $existing = User::whereRaw('lower(email) = ?', [$email])->first();
        if ($existing) {
            $this->treeAccess->acceptInvitation($invitation, $existing);
        }

        return response()->json([
            'id' => $invitation->id,
            'status' => $invitation->fresh()->status,
        ], 201);
    }

    public function revokeInvitation(Request $request, FamilyTreeInvitation $invitation): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $invitation->familyTree, TreePermission::Manage);

        $invitation->update(['status' => 'revoked']);

        return response()->json(['status' => $invitation->status]);
    }

    // ── Membership requests ──────────────────────────────────────────────────

    public function membershipRequests(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $requests = $tree->membershipRequests()
            ->latest()
            ->get()
            ->map(fn (FamilyTreeMembershipRequest $r) => [
                'id' => $r->id,
                'requester_name' => $r->requester_name,
                'requester_email' => $r->requester_email,
                'note' => $r->note,
                'status' => $r->status,
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $requests]);
    }

    public function requestMembership(Request $request, FamilyTree $tree): JsonResponse
    {
        // Anyone who can see the tree (public/invited) may ask to join; they must
        // not already have access.
        abort_if($this->treeAccess->can($request->user(), $tree, TreePermission::Observe), 409, __('You already have access to this tree.'));
        abort_unless(in_array($tree->privacy, ['public', 'invited'], true), 403);

        $validated = $request->validate(['note' => 'nullable|string|max:1000']);

        $user = $request->user();
        $email = strtolower($user->email);

        if ($tree->membershipRequests()->where('requester_email', $email)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['note' => [__('You already have a pending request for this tree.')]]);
        }

        $membershipRequest = $tree->membershipRequests()->create([
            'requester_name' => $user->name,
            'requester_email' => $email,
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['id' => $membershipRequest->id, 'status' => 'pending'], 201);
    }

    public function reviewMembershipRequest(Request $request, FamilyTreeMembershipRequest $membershipRequest): JsonResponse
    {
        $tree = $membershipRequest->familyTree;
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $validated = $request->validate(['decision' => 'required|in:approved,declined']);

        $membershipRequest->update([
            'status' => $validated['decision'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($validated['decision'] === 'approved') {
            $user = User::whereRaw('lower(email) = ?', [$membershipRequest->requester_email])->first();
            if ($user) {
                $this->treeAccess->grantTreeAccess($user, $tree, TreeAccessLevel::Observer);
            }
        }

        return response()->json(['status' => $membershipRequest->status]);
    }

    // ── Members ──────────────────────────────────────────────────────────────

    public function updateMember(Request $request, FamilyTree $tree, User $user): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        abort_if($user->id === $tree->user_id, 422, __('The tree owner cannot be changed.'));

        $validated = $request->validate([
            'access_level' => ['required', Rule::in([TreeAccessLevel::Manager->value, TreeAccessLevel::Observer->value])],
        ]);

        $this->treeAccess->setTreeAccessLevel($user, $tree, TreeAccessLevel::from($validated['access_level']));

        return response()->json(['access_level' => $validated['access_level']]);
    }

    public function removeMember(Request $request, FamilyTree $tree, User $user): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        abort_if($user->id === $tree->user_id, 422, __('The tree owner cannot be removed.'));

        $this->treeAccess->revokeTreeAccess($user, $tree);

        return response()->json(['removed' => true]);
    }
}
