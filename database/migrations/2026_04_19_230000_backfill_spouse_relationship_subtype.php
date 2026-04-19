<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('person_relationships')
            ->where('type', 'spouse')
            ->whereNull('subtype')
            ->where(function ($q) {
                $q->whereNotNull('start_date')
                  ->orWhereNotNull('start_date_text');
            })
            ->update(['subtype' => 'married']);
    }

    public function down(): void
    {
        // Cannot reliably reverse — would incorrectly clear manually-set values
    }
};
