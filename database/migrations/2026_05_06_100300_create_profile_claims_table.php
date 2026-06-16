<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_claims', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->text('message')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_claims');
    }
};
