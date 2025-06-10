<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productive_custom_fields', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('productive_projects');
            $table->foreign('section_id')->references('id')->on('productive_sections');
            $table->foreign('survey_id')->references('id')->on('productive_surveys');
            $table->foreign('person_id')->references('id')->on('productive_people');
            $table->foreign('cfo_id')->references('id')->on('productive_cfos');
        });

        Schema::table('productive_cfos', function (Blueprint $table) {
            $table->foreign('custom_field_id')->references('id')->on('productive_custom_fields');
        });

        Schema::table('productive_surveys', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('productive_projects');
            $table->foreign('creator_id')->references('id')->on('productive_people');
            $table->foreign('updater_id')->references('id')->on('productive_people');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productive_custom_fields', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['section_id']);
            $table->dropForeign(['survey_id']);
            $table->dropForeign(['person_id']);
            $table->dropForeign(['cfo_id']);
        });
        
        Schema::table('productive_cfos', function (Blueprint $table) {
            $table->dropForeign(['custom_field_id']);
        });

        Schema::table('productive_surveys', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['updater_id']);
        });
    }
};
