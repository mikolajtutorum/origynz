<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('family_tree_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignUuid('related_person_id')->constrained('people')->cascadeOnDelete();
            $table->string('type', 20);
            $table->timestamps();

            $table->unique(['person_id', 'related_person_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_relationships');
    }
};
