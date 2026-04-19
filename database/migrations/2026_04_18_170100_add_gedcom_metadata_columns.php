<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_trees', function (Blueprint $table) {
            $table->string('gedcom_source_system', 120)->nullable()->after('privacy');
            $table->string('gedcom_source_version', 80)->nullable()->after('gedcom_source_system');
            $table->string('gedcom_language', 80)->nullable()->after('gedcom_source_version');
            $table->string('gedcom_destination', 120)->nullable()->after('gedcom_language');
            $table->string('gedcom_exported_at_text', 120)->nullable()->after('gedcom_destination');
            $table->string('gedcom_file_label')->nullable()->after('gedcom_exported_at_text');
            $table->string('gedcom_project_guid', 160)->nullable()->after('gedcom_file_label');
            $table->string('gedcom_site_id', 120)->nullable()->after('gedcom_project_guid');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->string('alternative_name', 160)->nullable()->after('middle_name');
            $table->string('gedcom_rin', 120)->nullable()->after('notes');
            $table->string('gedcom_uid', 160)->nullable()->after('gedcom_rin');
            $table->string('gedcom_updated_at_text', 120)->nullable()->after('gedcom_uid');
        });

        Schema::table('person_relationships', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('type');
            $table->string('start_date_text', 120)->nullable()->after('start_date');
            $table->date('end_date')->nullable()->after('start_date_text');
            $table->string('end_date_text', 120)->nullable()->after('end_date');
            $table->string('place', 255)->nullable()->after('end_date_text');
            $table->string('subtype', 120)->nullable()->after('place');
            $table->text('description')->nullable()->after('subtype');
        });

        Schema::table('media_items', function (Blueprint $table) {
            $table->string('gedcom_rin', 120)->nullable()->after('description');
            $table->string('gedcom_updated_at_text', 120)->nullable()->after('gedcom_rin');
            $table->string('gedcom_external_id', 160)->nullable()->after('gedcom_updated_at_text');
            $table->string('gedcom_parent_external_id', 160)->nullable()->after('gedcom_external_id');
            $table->string('crop_position', 120)->nullable()->after('gedcom_parent_external_id');
            $table->boolean('is_cutout')->default(false)->after('crop_position');
            $table->boolean('is_personal_photo')->default(false)->after('is_cutout');
            $table->boolean('is_parent_photo')->default(false)->after('is_personal_photo');
            $table->boolean('is_primary_cutout')->default(false)->after('is_parent_photo');
        });

        Schema::table('sources', function (Blueprint $table) {
            $table->string('gedcom_rin', 120)->nullable()->after('quality');
            $table->string('gedcom_updated_at_text', 120)->nullable()->after('gedcom_rin');
            $table->string('source_type', 120)->nullable()->after('gedcom_updated_at_text');
            $table->string('source_medium', 120)->nullable()->after('source_type');
        });

        Schema::table('person_source_citations', function (Blueprint $table) {
            $table->string('event_name', 160)->nullable()->after('quality');
            $table->string('role', 120)->nullable()->after('event_name');
            $table->string('entry_date_text', 120)->nullable()->after('role');
            $table->text('entry_text')->nullable()->after('entry_date_text');
        });
    }

    public function down(): void
    {
        Schema::table('person_source_citations', function (Blueprint $table) {
            $table->dropColumn(['event_name', 'role', 'entry_date_text', 'entry_text']);
        });

        Schema::table('sources', function (Blueprint $table) {
            $table->dropColumn(['gedcom_rin', 'gedcom_updated_at_text', 'source_type', 'source_medium']);
        });

        Schema::table('media_items', function (Blueprint $table) {
            $table->dropColumn([
                'gedcom_rin',
                'gedcom_updated_at_text',
                'gedcom_external_id',
                'gedcom_parent_external_id',
                'crop_position',
                'is_cutout',
                'is_personal_photo',
                'is_parent_photo',
                'is_primary_cutout',
            ]);
        });

        Schema::table('person_relationships', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'start_date_text', 'end_date', 'end_date_text', 'place', 'subtype', 'description']);
        });

        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['alternative_name', 'gedcom_rin', 'gedcom_uid', 'gedcom_updated_at_text']);
        });

        Schema::table('family_trees', function (Blueprint $table) {
            $table->dropColumn([
                'gedcom_source_system',
                'gedcom_source_version',
                'gedcom_language',
                'gedcom_destination',
                'gedcom_exported_at_text',
                'gedcom_file_label',
                'gedcom_project_guid',
                'gedcom_site_id',
            ]);
        });
    }
};
