<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->string('findagrave_memorial_id')->nullable()->after('gedcom_updated_at_text');
            $table->string('billiongraves_id')->nullable()->after('findagrave_memorial_id');
            $table->string('familysearch_person_id')->nullable()->after('billiongraves_id');
            $table->string('wikitree_id')->nullable()->after('familysearch_person_id');
            $table->string('geni_profile_id')->nullable()->after('wikitree_id');

            $table->index('familysearch_person_id');
            $table->index('wikitree_id');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropIndex(['familysearch_person_id']);
            $table->dropIndex(['wikitree_id']);
            $table->dropColumn([
                'findagrave_memorial_id',
                'billiongraves_id',
                'familysearch_person_id',
                'wikitree_id',
                'geni_profile_id',
            ]);
        });
    }
};
