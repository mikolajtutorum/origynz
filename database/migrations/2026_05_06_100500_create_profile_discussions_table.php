<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_discussions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('profile_discussions')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_discussions');
    }
};
