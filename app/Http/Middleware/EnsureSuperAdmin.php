<?php

namespace App\Http\Middleware;

use App\Enums\SiteRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $tables  = config('permission.table_names');
        $columns = config('permission.column_names');
        $teamKey = $columns['team_foreign_key'] ?? 'family_tree_id';
        $morphKey = $columns['model_morph_key'] ?? 'model_id';

        $isSuperAdmin = DB::table($tables['model_has_roles'].' as model_roles')
            ->join($tables['roles'].' as roles', 'roles.id', '=', 'model_roles.role_id')
            ->where('model_roles.'.$teamKey, \App\Support\Authorization\TreeAccessService::SITE_TEAM_ID)
            ->where('model_roles.model_type', get_class($user))
            ->where('model_roles.'.$morphKey, $user->getKey())
            ->where('roles.name', SiteRole::SuperAdmin->value)
            ->exists();

        if (! $isSuperAdmin) {
            abort(403, 'Super admin access required.');
        }

        return $next($request);
    }
}
