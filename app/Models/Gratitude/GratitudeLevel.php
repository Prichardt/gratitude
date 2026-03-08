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
        'stay_active_rules',
    ];

    public function benefits()
    {
        return $this->belongsToMany(GratitudeBenefit::class)
                    ->withPivot('description', 'value', 'value_type', 'calculation', 'is_active')
                    ->withTimestamps();
    }
}
