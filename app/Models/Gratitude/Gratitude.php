<?php

namespace App\Models\Gratitude;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Gratitude extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    // Gratitudes are identified by gratitudeNumber — there is no user_id on this table.
    protected $fillable = [
        'old_id',
        'gratitudeNumber',
        'totalPoints',
        'totalEarnedPoints',
        'totalBonusPoints',
        'totalExpiredPoints',
        'totalCancelledPoints',
        'totalRedeemedPoints',
        'totalRemainingPoints',
        'useablePoints',
        'nonUseablePoints',
        'level',
        'levelHistory',
        'level_obtained_at',
        'status',
        'statusChange',
        'statusChangeReason',
        'systemLevelUpdate',
        'is_active',
        'importStatus',
        'expires_at',
        'last_activity_at',
    ];

    protected $casts = [
        'importStatus' => 'boolean',
        'systemLevelUpdate' => 'boolean',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'level_obtained_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'levelHistory' => 'array',
    ];
}
