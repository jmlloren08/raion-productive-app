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
        Schema::create('productive_custom_fields', function (Blueprint $table) {

            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('custom_fields'); // Default type for custom fields

            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('updated_at_api')->nullable();
            $table->string('name');
            $table->integer('data_type')->default(0);
            $table->boolean('required')->default(false);
            $table->text('description')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->integer('aggregation_type_id')->nullable();
            $table->integer('formatting_type_id')->nullable();
            $table->boolean('global')->default(false);
            $table->boolean('show_in_add_edit_views')->default(false);
            $table->boolean('sensitive')->default(false);
            $table->integer('position')->default(0);
            $table->boolean('quick_add_enabled')->default(false);
            $table->string('customizable_type');

            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('survey_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('cfo_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_custom_fields');
    }
};
