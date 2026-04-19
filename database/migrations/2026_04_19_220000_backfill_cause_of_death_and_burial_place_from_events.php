<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE people
            SET cause_of_death = (
                SELECT e.cause FROM person_events e
                WHERE e.person_id = people.id AND e.type = 'DEAT'
                  AND e.cause IS NOT NULL AND e.cause != ''
                LIMIT 1
            )
            WHERE cause_of_death IS NULL
              AND EXISTS (
                SELECT 1 FROM person_events e
                WHERE e.person_id = people.id AND e.type = 'DEAT'
                  AND e.cause IS NOT NULL AND e.cause != ''
              )
        ");

        DB::statement("
            UPDATE people
            SET burial_place = (
                SELECT e.place FROM person_events e
                WHERE e.person_id = people.id AND e.type = 'BURI'
                  AND e.place IS NOT NULL AND e.place != ''
                LIMIT 1
            )
            WHERE burial_place IS NULL
              AND EXISTS (
                SELECT 1 FROM person_events e
                WHERE e.person_id = people.id AND e.type = 'BURI'
                  AND e.place IS NOT NULL AND e.place != ''
              )
        ");
    }

    public function down(): void
    {
        // Not reversible — would require knowing which values came from backfill vs manual entry
    }
};
