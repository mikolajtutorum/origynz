<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_tree_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_tree_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->enum('status', ['pending', 'accepted', 'revoked'])->default('pending');
            $table->timestamps();

            $table->index(['family_tree_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_tree_invitations');
    }
};
