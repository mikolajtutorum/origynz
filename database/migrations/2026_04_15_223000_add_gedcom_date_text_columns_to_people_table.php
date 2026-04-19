<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('birth_date_text')->nullable()->after('birth_date');
            $table->string('death_date_text')->nullable()->after('death_date');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date_text',
                'death_date_text',
            ]);
        });
    }
};
