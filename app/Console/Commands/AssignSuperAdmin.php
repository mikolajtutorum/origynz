<?php

namespace App\Console\Commands;

use App\Enums\SiteRole;
use App\Models\User;
use Illuminate\Console\Command;
use App\Models\Role;

class AssignSuperAdmin extends Command
{
    protected $signature = 'admin:assign-super-admin {email}';

    protected $description = 'Assign the super admin role to a user by email';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");
            return self::FAILURE;
        }

        $role = Role::firstOrCreate([
            'name'           => SiteRole::SuperAdmin->value,
            'guard_name'     => 'web',
            'family_tree_id' => 0,
        ]);

        setPermissionsTeamId(0);
        $user->assignRole($role);

        $this->info("Super admin role assigned to {$user->name} ({$user->email}).");
        return self::SUCCESS;
    }
}
