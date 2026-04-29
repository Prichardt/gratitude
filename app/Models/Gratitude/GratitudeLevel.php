<?php

namespace App\Models\Gratitude;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
        'partner_points_per_dollar',
        'earned_expire_days',
        'bonus_expire_days',
        'level_interval_years',
        'jetsetter_min_journeys',
        'jetsetter_min_journey_days',
        'stay_active_rules',
        'level_rules',
        'terms_conditions',
        'level_terms_conditions',
        'level_image',
        'level_icon',
    ];

    protected $appends = ['level_image_url', 'level_icon_url'];

    protected $casts = [
        'level_rules' => 'array',
        'status' => 'boolean',
        'redemption_points_per_dollar' => 'decimal:2',
        'partner_points_per_dollar' => 'decimal:2',
        'earned_expire_days' => 'integer',
        'bonus_expire_days' => 'integer',
        'level_interval_years' => 'integer',
        'jetsetter_min_journeys' => 'integer',
        'jetsetter_min_journey_days' => 'integer',
    ];

    public function getLevelImageUrlAttribute(): ?string
    {
        return $this->level_image ? url(Storage::url($this->level_image)) : null;
    }

    public function getLevelIconUrlAttribute(): ?string
    {
        return $this->level_icon ? url(Storage::url($this->level_icon)) : null;
    }

    public function benefits()
    {
        return $this->belongsToMany(GratitudeBenefit::class, 'benefit_gratitude_level')
            ->using(BenefitGratitudeLevel::class)
            ->withPivot('description', 'value', 'value_type', 'calculation', 'is_active', 'web_status')
            ->withTimestamps();
    }
}
