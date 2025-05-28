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
        Schema::create('productive_document_types', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('document_types');
            // Core attributes
            $table->string('name');
            $table->string('tax1_name');
            $table->decimal('tax1_value', 5, 2);
            $table->string('tax2_name')->nullable();
            $table->decimal('tax2_value', 5, 2)->nullable();
            $table->string('locale')->default('en_AU');
            $table->integer('document_template_id')->nullable();
            $table->integer('exportable_type_id')->nullable();
            $table->text('note')->nullable();
            $table->text('footer')->nullable();
            $table->json('template_options')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('header_template')->nullable();
            $table->text('body_template')->nullable();
            $table->text('footer_template')->nullable();
            $table->text('scss_template')->nullable();
            $table->json('exporter_options')->nullable();
            $table->text('email_template')->nullable();
            $table->string('email_subject')->nullable();
            $table->json('email_data')->nullable();
            $table->boolean('dual_currency')->default(false);
            // Relationships
            $table->string('subsidiary_id')->nullable();
            $table->string('document_style_id')->nullable();
            $table->string('attachment_id')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_document_types');
    }
};
