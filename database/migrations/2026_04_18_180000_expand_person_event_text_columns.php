<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE person_events MODIFY place TEXT NULL');
        DB::statement('ALTER TABLE person_events MODIFY value TEXT NULL');
        DB::statement('ALTER TABLE person_events MODIFY cause TEXT NULL');
        DB::statement('ALTER TABLE person_events MODIFY address_line1 TEXT NULL');
        DB::statement('ALTER TABLE person_events MODIFY city TEXT NULL');
        DB::statement('ALTER TABLE person_events MODIFY country TEXT NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE person_events MODIFY place VARCHAR(255) NULL');
        DB::statement('ALTER TABLE person_events MODIFY value VARCHAR(255) NULL');
        DB::statement('ALTER TABLE person_events MODIFY cause VARCHAR(255) NULL');
        DB::statement('ALTER TABLE person_events MODIFY address_line1 VARCHAR(255) NULL');
        DB::statement('ALTER TABLE person_events MODIFY city VARCHAR(120) NULL');
        DB::statement('ALTER TABLE person_events MODIFY country VARCHAR(120) NULL');
    }
};
