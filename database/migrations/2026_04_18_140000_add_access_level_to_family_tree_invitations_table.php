<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_tree_invitations', function (Blueprint $table): void {
            $table->string('access_level', 20)->default('observer')->after('email');
            $table->timestamp('accepted_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('family_tree_invitations', function (Blueprint $table): void {
            $table->dropColumn(['access_level', 'accepted_at']);
        });
    }
};
