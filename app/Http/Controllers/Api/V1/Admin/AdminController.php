<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\SiteRole;
use App\Http\Controllers\Controller;
use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Site administration API. Mounted behind auth:sanctum + super.admin.
 */
class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'users' => User::count(),
            'trees' => FamilyTree::count(),
            'people' => Person::count(),
            'recent_users' => User::latest()->take(5)->get(['id', 'name', 'email', 'created_at']),
            'recent_trees' => FamilyTree::with('user:id,name')->latest()->take(5)->get(['id', 'name', 'user_id', 'created_at']),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $users = User::withCount(['familyTrees', 'people'])
            ->with('roles:id,name')
            ->when($request->string('search')->value(), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->latest()
            ->paginate(25);

        return response()->json([
            'data' => $users->getCollection()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'family_trees_count' => $u->family_trees_count,
                'people_count' => $u->people_count,
                'roles' => $u->roles->pluck('name'),
                'created_at' => $u->created_at?->toIso8601String(),
            ]),
            'meta' => ['current_page' => $users->currentPage(), 'last_page' => $users->lastPage(), 'total' => $users->total()],
        ]);
    }

    public function updateUserRole(Request $request, User $user): JsonResponse
    {
        $request->validate(['role' => ['required', 'in:'.implode(',', SiteRole::values())]]);

        setPermissionsTeamId(0);
        $user->syncRoles([]);
        $role = Role::firstOrCreate(['name' => $request->role, 'guard_name' => 'web', 'family_tree_id' => 0]);
        $user->assignRole($role);

        return response()->json(['message' => "Role updated to {$request->role}."]);
    }

    public function deleteUser(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot delete your own account.');

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    public function trees(): JsonResponse
    {
        $trees = FamilyTree::with('user:id,name')->withCount('people')->latest()->paginate(25);

        return response()->json([
            'data' => $trees->getCollection()->map(fn (FamilyTree $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'privacy' => $t->privacy,
                'owner' => $t->user?->name,
                'people_count' => $t->people_count,
                'global_tree_enabled' => $t->global_tree_enabled,
                'created_at' => $t->created_at?->toIso8601String(),
            ]),
            'meta' => ['current_page' => $trees->currentPage(), 'last_page' => $trees->lastPage(), 'total' => $trees->total()],
        ]);
    }

    public function deleteTree(FamilyTree $tree): JsonResponse
    {
        $tree->delete();

        return response()->json(['message' => 'Tree deleted.']);
    }

    public function toggleGlobalTree(FamilyTree $tree): JsonResponse
    {
        $tree->update(['global_tree_enabled' => ! $tree->global_tree_enabled]);

        return response()->json(['global_tree_enabled' => $tree->global_tree_enabled]);
    }

    public function activity(): JsonResponse
    {
        $logs = Activity::with('causer:id,name')->latest()->paginate(40);

        return response()->json([
            'data' => $logs->getCollection()->map(fn (Activity $a) => [
                'id' => $a->id,
                'event' => $a->event,
                'description' => $a->description,
                'causer' => $a->causer?->name,
                'created_at' => $a->created_at?->toIso8601String(),
            ]),
            'meta' => ['current_page' => $logs->currentPage(), 'last_page' => $logs->lastPage(), 'total' => $logs->total()],
        ]);
    }
}
