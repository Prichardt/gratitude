<?php

namespace App\Models\Gratitude;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GratitudeEarnedBenefit extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'gratitude_earned_benefits';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('gratitude_earned_benefit')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'gratitudeNumber',
        'benefit_id',
        'user_id',
        'journey_id',
        'benefit_name',
        'benefit_key',
        'description',
        'benefit_value',
        'value_type',
        'project_data',
        'date',
        'status',
        'notes',
    ];

    protected $casts = [
        'date'         => 'date',
        'project_data' => 'array',
        'journey_id'   => 'integer',
        'benefit_id'   => 'integer',
        'user_id'      => 'integer',
    ];

    public function gratitude()
    {
        return $this->belongsTo(Gratitude::class, 'gratitudeNumber', 'gratitudeNumber');
    }

    public function benefit()
    {
        return $this->belongsTo(GratitudeBenefit::class, 'benefit_id');
    }
}
