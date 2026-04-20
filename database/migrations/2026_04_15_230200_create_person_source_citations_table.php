<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_source_citations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('source_id')->constrained()->cascadeOnDelete();
            $table->string('page')->nullable();
            $table->text('quotation')->nullable();
            $table->text('note')->nullable();
            $table->unsignedTinyInteger('quality')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_source_citations');
    }
};
