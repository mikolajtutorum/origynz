<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_trees', function (Blueprint $table): void {
            $table->boolean('global_tree_enabled')->default(false)->after('privacy');
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->boolean('exclude_from_global_tree')->default(false)->after('is_living');
        });
    }

    public function down(): void
    {
        Schema::table('family_trees', function (Blueprint $table): void {
            $table->dropColumn('global_tree_enabled');
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->dropColumn('exclude_from_global_tree');
        });
    }
};
