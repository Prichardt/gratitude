<?php

namespace App\Models\Gratitude;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GratitudeBenefit extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    protected $fillable = [
        'name',
        'benefit_key',
        'description',
        'type',
        'is_active',
    ];

    public function levels()
    {
        return $this->belongsToMany(GratitudeLevel::class, 'benefit_gratitude_level')
                    ->using(BenefitGratitudeLevel::class)
                    ->withPivot('description', 'value', 'value_type', 'calculation', 'is_active', 'web_status')
                    ->withTimestamps();
    }
}
