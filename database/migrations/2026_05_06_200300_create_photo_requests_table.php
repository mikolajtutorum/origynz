<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignUuid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('findagrave_memorial_id')->nullable();
            $table->string('status', 20)->default('pending'); // pending | fulfilled | closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_requests');
    }
};
