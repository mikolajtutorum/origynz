<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('family_tree_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('given_name');
            $table->string('middle_name')->nullable();
            $table->string('surname');
            $table->string('birth_surname')->nullable();
            $table->string('sex', 20)->default('unknown');
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
            $table->text('birth_place')->nullable();
            $table->text('death_place')->nullable();
            $table->boolean('is_living')->default(false);
            $table->string('headline')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
