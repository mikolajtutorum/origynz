<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('family_tree_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('person_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('label', 160)->nullable();
            $table->string('category', 40)->nullable();
            $table->date('event_date')->nullable();
            $table->string('event_date_text', 120)->nullable();
            $table->string('place', 255)->nullable();
            $table->string('value', 255)->nullable();
            $table->string('age', 80)->nullable();
            $table->string('cause', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('address_line1', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 120)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_events');
    }
};
