<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('prefix', 40)->nullable()->after('birth_surname');
            $table->string('suffix', 40)->nullable()->after('prefix');
            $table->string('cause_of_death', 120)->nullable()->after('death_place');
            $table->text('burial_place')->nullable()->after('cause_of_death');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['prefix', 'suffix', 'cause_of_death', 'burial_place']);
        });
    }
};
