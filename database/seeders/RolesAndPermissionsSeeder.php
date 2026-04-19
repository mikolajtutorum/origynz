<?php

namespace Database\Seeders;

use App\Support\Authorization\TreeAccessService;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(TreeAccessService $treeAccess): void
    {
        $treeAccess->ensureBaseRecordsExist();
    }
}
