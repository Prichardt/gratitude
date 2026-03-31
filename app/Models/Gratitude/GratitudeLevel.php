<?php

namespace App\Models\Gratitude;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GratitudeLevel extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    protected $fillable = [
        'name',
        'min_points',
        'max_points',
        'status',
        'redemption_points_per_dollar',
        'earned_expire_days',
        'bonus_expire_days',
        'stay_active_rules',
        'level_rules',
        'level_image',
        'level_icon',
    ];

    protected $casts = [
        'level_rules' => 'array',
        'status' => 'boolean',
        'earned_expire_days' => 'integer',
        'bonus_expire_days' => 'integer',
    ];

    public function benefits()
    {
        return $this->belongsToMany(GratitudeBenefit::class, 'benefit_gratitude_level')
            ->using(BenefitGratitudeLevel::class)
            ->withPivot('description', 'value', 'value_type', 'calculation', 'is_active', 'web_status')
            ->withTimestamps();
    }
}
