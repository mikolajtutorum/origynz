<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SiteRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withCount(['familyTrees', 'people'])
            ->with('roles')
            ->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(25)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->loadCount(['familyTrees', 'people', 'socialAccounts']);
        $user->load(['familyTrees' => fn ($q) => $q->withCount('people')->latest(), 'roles']);
        $recentActivity = \Spatie\Activitylog\Models\Activity::where('causer_type', User::class)
            ->where('causer_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        return view('admin.users.show', compact('user', 'recentActivity'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'in:'.implode(',', SiteRole::values())],
        ]);

        setPermissionsTeamId(0);
        $user->syncRoles([]);

        if ($request->role !== '') {
            $role = Role::firstOrCreate([
                'name'           => $request->role,
                'guard_name'     => 'web',
                'family_tree_id' => 0,
            ]);
            $user->assignRole($role);
        }

        return back()->with('status', "Role updated to '{$request->role}' for {$user->name}.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', "User {$user->name} deleted.");
    }
}
