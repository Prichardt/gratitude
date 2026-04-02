<?php

namespace App\Models\Gratitude;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Gratitude extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    protected $fillable = [
        'old_id',
        'gratitudeNumber',
        'totalPoints',
        'useablePoints',
        'level',
        'levelHistory',
        'level_obtained_at',
        'status',
        'statusChange',
        'statusChangeReason',
        'systemLevelUpdate',
        'importStatus',
        'expires_at',
    ];

    protected $casts = [
        'importStatus' => 'boolean',
        'systemLevelUpdate' => 'boolean',
        'expires_at' => 'datetime',
        'level_obtained_at' => 'datetime',
        'levelHistory' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
