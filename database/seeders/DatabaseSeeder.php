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
            'name'     => 'Mikolaj Florysiak',
            'email'    => 'mikolajflorysiak1@gmail.com',
            'password' => bcrypt('password'),
        ]);

        app(TreeAccessService::class)->assignDefaultRole($user);
        setPermissionsTeamId(0);
        $user->syncRoles([SiteRole::SuperAdmin->value]);

        $this->call(BibleTreeSeeder::class);
    }
}
