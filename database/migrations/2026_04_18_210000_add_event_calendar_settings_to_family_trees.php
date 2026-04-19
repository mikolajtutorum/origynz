<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_trees', function (Blueprint $table) {
            $table->boolean('show_birthdays_in_events')->default(true)->after('privacy');
            $table->boolean('show_wedding_anniversaries_in_events')->default(true)->after('show_birthdays_in_events');
            $table->boolean('show_death_anniversaries_in_events')->default(false)->after('show_wedding_anniversaries_in_events');
        });

        DB::table('family_trees')->update([
            'show_birthdays_in_events' => true,
            'show_wedding_anniversaries_in_events' => true,
            'show_death_anniversaries_in_events' => false,
        ]);
    }

    public function down(): void
    {
        Schema::table('family_trees', function (Blueprint $table) {
            $table->dropColumn([
                'show_birthdays_in_events',
                'show_wedding_anniversaries_in_events',
                'show_death_anniversaries_in_events',
            ]);
        });
    }
};
