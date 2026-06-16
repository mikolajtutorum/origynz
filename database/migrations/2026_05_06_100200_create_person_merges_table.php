<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_merges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('surviving_person_id')->constrained('people')->cascadeOnDelete();
            $table->uuid('absorbed_person_id'); // not FK — person may be deleted after merge
            $table->foreignUuid('merged_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('field_decisions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_merges');
    }
};
