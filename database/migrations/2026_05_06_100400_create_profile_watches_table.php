<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_watches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('person_id')->constrained('people')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_watches');
    }
};
