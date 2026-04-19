<?php

namespace Database\Seeders;

use App\Enums\SiteRole;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        app(TreeAccessService::class)->assignDefaultRole($user);
        setPermissionsTeamId(0);
        $user->syncRoles([SiteRole::SuperAdmin->value]);
    }
}
