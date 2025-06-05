
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
        Schema::create('productive_people', function (Blueprint $table) {
            // Primary key
            $table->unsignedBigInteger('id')->primary();
            $table->string('type')->default('people');
            // Core attributes
            $table->string('avatar_url')->nullable();
            $table->json('contact')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('original_avatar_url')->nullable();
            $table->integer('role_id')->nullable();
            $table->string('status_emoji')->nullable();
            $table->timestamp('status_expires_at')->nullable();
            $table->string('status_text')->nullable();
            $table->boolean('time_off_status_sync')->default(false);
            $table->string('title')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->boolean('autotracking')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->boolean('is_user')->default(false);
            $table->integer('user_id')->nullable();
            $table->json('tag_list')->nullable();
            $table->boolean('virtual')->default(false);
            $table->json('custom_fields')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->boolean('placeholder')->default(false);
            $table->integer('color_id')->nullable();
            $table->boolean('sample_data')->default(false);
            $table->boolean('time_unlocked')->default(false);
            $table->timestamp('time_unlocked_on')->nullable();
            $table->timestamp('time_unlocked_start_date')->nullable();
            $table->timestamp('time_unlocked_end_date')->nullable();
            $table->integer('time_unlocked_period_id')->nullable();
            $table->integer('time_unlocked_interval')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('two_factor_auth')->default(false)->nullable();
            $table->json('availabilities')->nullable();
            $table->string('external_id')->nullable();
            $table->boolean('external_sync')->default(false);
            $table->integer('hrm_type_id')->nullable();
            $table->boolean('champion')->default(false);
            $table->boolean('timesheet_submission_disabled')->default(false);
            // Relationships - using foreign IDs without constraints to avoid circular dependencies
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('subsidiary_id')->nullable();

            $table->json('custom_role')->nullable();
            
            $table->unsignedBigInteger('apa_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();

            $table->json('custom_field_people')->nullable();
            $table->json('custom_field_attachments')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for archiving
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productive_people');
    }
};
