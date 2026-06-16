<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dna_kits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('provider', 30);           // 23andme | ancestrydna | ftdna | myheritage | livingdna | other
            $table->string('kit_name')->nullable();
            $table->string('file_path');
            $table->unsignedBigInteger('snp_count')->default(0);
            $table->string('haplogroup_y')->nullable();
            $table->string('haplogroup_mt')->nullable();
            $table->json('ancestry_composition')->nullable();
            $table->date('sample_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dna_kits');
    }
};
