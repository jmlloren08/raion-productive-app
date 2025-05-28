<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductivePeople extends Model
{
    use SoftDeletes;

    protected $table = 'productive_people';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'type',
        'avatar_url',
        'contact',
        'deactivated_at',
        'email',
        'first_name',
        'last_name',
        'nickname',
        'original_avatar_url',
        'role_id',
        'status_emoji',
        'status_expires_at',
        'status_text',
        'time_off_status_sync',
        'title',
        'archived_at',
        'autotracking',
        'joined_at',
        'last_seen_at',
        'invited_at',
        'is_user',
        'user_id',
        'tag_list',
        'virtual',
        'custom_fields',
        'created_at_api',
        'placeholder',
        'color_id',
        'sample_data',
        'time_unlocked',
        'time_unlocked_on',
        'time_unlocked_start_date',
        'time_unlocked_end_date',
        'time_unlocked_period_id',
        'time_unlocked_interval',
        'last_activity_at',
        'two_factor_auth',
        'availabilities',
        'external_id',
        'external_sync',
        'hrm_type_id',
        'champion',
        'timesheet_submission_disabled',
        'manager_id',
        'company_id',
        'subsidiary_id',
        'apa_id',
        'team_id',
        'custom_field_people',
        'custom_field_attachments'
    ];

    protected $casts = [
        'contact' => 'array',
        'tag_list' => 'array',
        'custom_fields' => 'array',
        'custom_field_people' => 'array',
        'custom_field_attachments' => 'array',
        'availabilities' => 'array',
        'deactivated_at' => 'timestamp',
        'status_expires_at' => 'timestamp',
        'archived_at' => 'timestamp',
        'joined_at' => 'timestamp',
        'last_seen_at' => 'timestamp',
        'invited_at' => 'timestamp',
        'created_at_api' => 'timestamp',
        'time_unlocked_on' => 'timestamp',
        'time_unlocked_start_date' => 'timestamp',
        'time_unlocked_end_date' => 'timestamp',
        'last_activity_at' => 'timestamp',
        'is_user' => 'boolean',
        'virtual' => 'boolean',
        'placeholder' => 'boolean',
        'sample_data' => 'boolean',
        'time_unlocked' => 'boolean',
        'external_sync' => 'boolean',
        'champion' => 'boolean',
        'timesheet_submission_disabled' => 'boolean',
        'time_off_status_sync' => 'boolean',
        'autotracking' => 'boolean'
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(ProductiveCompany::class, 'company_id');
    }

    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(ProductiveSubsidiary::class, 'subsidiary_id');
    }

    public function apa(): BelongsTo
    {
        return $this->belongsTo(ProductiveApa::class, 'apa_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ProductiveTeam::class, 'team_id');
    }
}
