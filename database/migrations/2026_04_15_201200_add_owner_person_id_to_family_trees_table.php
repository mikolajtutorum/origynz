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
            $table->foreignId('owner_person_id')
                ->nullable()
                ->after('user_id')
                ->constrained('people')
                ->nullOnDelete();
        });

        DB::table('family_trees')
            ->orderBy('id')
            ->get()
            ->each(function ($tree): void {
                $firstPersonId = DB::table('people')
                    ->where('family_tree_id', $tree->id)
                    ->orderBy('id')
                    ->value('id');

                if ($firstPersonId !== null) {
                    DB::table('family_trees')
                        ->where('id', $tree->id)
                        ->update(['owner_person_id' => $firstPersonId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('family_trees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_person_id');
        });
    }
};
