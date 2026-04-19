<?php

namespace App\Support\Authorization;

use App\Enums\SiteRole;
use App\Enums\TreeAccessLevel;
use App\Enums\TreePermission;
use App\Models\FamilyTree;
use App\Models\FamilyTreeInvitation;
use App\Models\FamilyTreeMembershipRequest;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TreeAccessService
{
    private const SITE_TEAM_ID = 0;

    public function can(User $user, FamilyTree $tree, TreePermission $permission): bool
    {
        if ($this->hasSiteRole($user, [SiteRole::SuperAdmin, SiteRole::Admin])) {
            return true;
        }

        return $this->forTree($user, $tree, fn () => $user->can($permission->value));
    }

    public function authorize(User $user, FamilyTree $tree, TreePermission $permission): void
    {
        abort_unless($this->can($user, $tree, $permission), 403);
    }

    public function assignDefaultRole(User $user): void
    {
        $this->ensureBaseRecordsExist();

        $this->forSiteContext(function () use ($user): void {
            $user->unsetRelation('roles');

            if (! $user->hasAnyRole(SiteRole::values())) {
                $user->assignRole(SiteRole::Member->value);
            }
        });
    }

    public function grantTreeAccess(User $user, FamilyTree $tree, TreeAccessLevel $level): TreeAccessLevel
    {
        $this->ensureBaseRecordsExist();

        return $this->forTree($user, $tree, function () use ($user, $level): TreeAccessLevel {
            $current = $this->resolveActiveTreeAccessLevel($user);
            $effective = TreeAccessLevel::highest($current ?? $level, $level) ?? $level;

            $user->syncPermissions($effective->permissions());
            $user->unsetRelation('permissions');

            return $effective;
        });
    }

    public function getTreeAccessLevel(User $user, FamilyTree $tree): ?TreeAccessLevel
    {
        if ($this->hasSiteRole($user, [SiteRole::SuperAdmin, SiteRole::Admin])) {
            return TreeAccessLevel::Owner;
        }

        return $this->forTree($user, $tree, fn (): ?TreeAccessLevel => $this->resolveActiveTreeAccessLevel($user));
    }

    /**
     * @return Collection<int, User>
     */
    public function members(FamilyTree $tree): Collection
    {
        return $tree->authorizedUsers()->get()->map(function (User $user) use ($tree): User {
            $user->setAttribute(
                'tree_access_level',
                $this->getTreeAccessLevel($user, $tree)?->value ?? TreeAccessLevel::Observer->value,
            );

            return $user;
        });
    }

    public function acceptInvitation(FamilyTreeInvitation $invitation, User $user): TreeAccessLevel
    {
        $level = TreeAccessLevel::from($invitation->access_level);
        $effective = $this->grantTreeAccess($user, $invitation->familyTree, $level);

        $invitation->forceFill([
            'status' => 'accepted',
            'accepted_at' => now(),
        ])->save();

        return $effective;
    }

    public function syncPendingAccessForUser(User $user): void
    {
        $pendingInvitations = FamilyTreeInvitation::query()
            ->with('familyTree')
            ->where('email', strtolower($user->email))
            ->where('status', 'pending')
            ->get();

        foreach ($pendingInvitations as $invitation) {
            $this->acceptInvitation($invitation, $user);
        }

        $approvedRequests = FamilyTreeMembershipRequest::query()
            ->with('familyTree')
            ->where('requester_email', strtolower($user->email))
            ->where('status', 'approved')
            ->get();

        foreach ($approvedRequests as $request) {
            $this->grantTreeAccess($user, $request->familyTree, TreeAccessLevel::Observer);
        }
    }

    public function ensureBaseRecordsExist(): void
    {
        foreach (SiteRole::values() as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        foreach (TreePermission::values() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function forTree(User $user, FamilyTree $tree, Closure $callback): mixed
    {
        $this->ensureBaseRecordsExist();

        $originalTeamId = getPermissionsTeamId();

        setPermissionsTeamId($tree->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        try {
            return $callback();
        } finally {
            setPermissionsTeamId($originalTeamId);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function forSiteContext(Closure $callback): mixed
    {
        $originalTeamId = getPermissionsTeamId();

        setPermissionsTeamId(self::SITE_TEAM_ID);

        try {
            return $callback();
        } finally {
            setPermissionsTeamId($originalTeamId);
        }
    }

    /**
     * @param  list<SiteRole>  $roles
     */
    private function hasSiteRole(User $user, array $roles): bool
    {
        return $this->forSiteContext(function () use ($user, $roles): bool {
            $user->unsetRelation('roles');

            return $user->hasAnyRole(array_map(
                static fn (SiteRole $role): string => $role->value,
                $roles,
            ));
        });
    }

    private function resolveActiveTreeAccessLevel(User $user): ?TreeAccessLevel
    {
        if ($user->can(TreePermission::Owner->value)) {
            return TreeAccessLevel::Owner;
        }

        if ($user->can(TreePermission::Manage->value)) {
            return TreeAccessLevel::Manager;
        }

        if ($user->can(TreePermission::Observe->value)) {
            return TreeAccessLevel::Observer;
        }

        return null;
    }
}
