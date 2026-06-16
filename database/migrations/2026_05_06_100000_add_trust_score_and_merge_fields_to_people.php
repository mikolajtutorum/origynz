<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->unsignedTinyInteger('trust_score')->default(0)->after('exclude_from_global_tree');
            $table->uuid('merged_into_id')->nullable()->after('trust_score');
            $table->foreign('merged_into_id')->references('id')->on('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropForeign(['merged_into_id']);
            $table->dropColumn(['trust_score', 'merged_into_id']);
        });
    }
};
