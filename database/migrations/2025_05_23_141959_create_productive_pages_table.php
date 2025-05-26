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
        Schema::create('productive_pages', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->string('type')->default('pages'); // type of page, e.g., 'project', 'task', etc.
            // Core attributes
            $table->text('cover_image_meta')->nullable();
            $table->text('cover_image_url')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->timestamp('edited_at_api')->nullable();
            $table->integer('icon_id')->nullable();
            $table->integer('position')->nullable();
            $table->json('preferences')->nullable();
            $table->string('title');
            $table->timestamp('updated_at_api')->nullable();
            $table->integer('version_number')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->json('body');
            $table->integer('parent_page_id')->nullable();
            $table->integer('root_page_id')->nullable();
            $table->uuid('public_uuid')->nullable();
            $table->boolean('public')->default(false);
            // Foreign keys without constraints - we'll add constraints in a separate migration
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            $table->json('template_object')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_pages');
    }
};
