<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('family_tree_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};
