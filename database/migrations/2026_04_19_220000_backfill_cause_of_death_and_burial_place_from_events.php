<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill cause_of_death from DEAT events where the person column is still null
        DB::statement("
            UPDATE people p
            JOIN person_events e ON e.person_id = p.id AND e.type = 'DEAT' AND e.cause IS NOT NULL AND e.cause != ''
            SET p.cause_of_death = e.cause
            WHERE p.cause_of_death IS NULL
        ");

        // Backfill burial_place from BURI events where the person column is still null
        DB::statement("
            UPDATE people p
            JOIN person_events e ON e.person_id = p.id AND e.type = 'BURI' AND e.place IS NOT NULL AND e.place != ''
            SET p.burial_place = e.place
            WHERE p.burial_place IS NULL
        ");
    }

    public function down(): void
    {
        // Not reversible — would require knowing which values came from backfill vs manual entry
    }
};
