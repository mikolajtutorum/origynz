<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merge_candidates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_a_id')->constrained('people')->cascadeOnDelete();
            $table->foreignUuid('person_b_id')->constrained('people')->cascadeOnDelete();
            $table->unsignedTinyInteger('similarity_score')->default(0);
            $table->string('status', 20)->default('pending'); // pending | dismissed | merged
            $table->timestamps();
            $table->unique(['person_a_id', 'person_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merge_candidates');
    }
};
