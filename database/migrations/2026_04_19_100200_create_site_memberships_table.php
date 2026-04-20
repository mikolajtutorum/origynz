<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('invited_by')->constrained('users')->cascadeOnDelete();
            $table->enum('trees_access', ['all', 'specific'])->default('all');
            $table->enum('status', ['pending', 'accepted', 'revoked'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_memberships');
    }
};
