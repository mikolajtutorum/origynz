<?php

use App\Models\Site;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_trees', function (Blueprint $table) {
            $table->foreignUuid('site_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        // Create a site for each existing user who owns trees, then assign their trees to it.
        $userIds = DB::table('family_trees')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            if (! $user) {
                continue;
            }

            $siteName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if ($siteName === '') {
                $siteName = $user->name;
            }
            $siteName .= "'s site";

            $siteId = (string) Str::uuid();
            DB::table('sites')->insert([
                'id'         => $siteId,
                'user_id'    => $userId,
                'name'       => $siteName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('family_trees')
                ->where('user_id', $userId)
                ->update(['site_id' => $siteId]);
        }
    }

    public function down(): void
    {
        Schema::table('family_trees', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });
    }
};
