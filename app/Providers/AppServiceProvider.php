<?php

namespace App\Providers;

use App\Enums\SiteRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);
        Gate::before(function (User $user, string $ability): ?bool {
            $tables = config('permission.table_names');
            $columns = config('permission.column_names');
            $teamKey = $columns['team_foreign_key'] ?? 'family_tree_id';
            $morphKey = $columns['model_morph_key'] ?? 'model_id';

            $isSuperAdmin = DB::table($tables['model_has_roles'].' as model_roles')
                ->join($tables['roles'].' as roles', 'roles.id', '=', 'model_roles.role_id')
                ->where('model_roles.'.$teamKey, \App\Support\Authorization\TreeAccessService::SITE_TEAM_ID)
                ->where('model_roles.model_type', User::class)
                ->where('model_roles.'.$morphKey, $user->getKey())
                ->where('roles.name', SiteRole::SuperAdmin->value)
                ->exists();

            return $isSuperAdmin ? true : null;
        });

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
