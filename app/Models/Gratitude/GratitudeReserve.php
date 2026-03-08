<?php

namespace App\Models\Gratitude;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GratitudeReserve extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    protected $fillable = [
        'gratitudeNumber',
        'journey_id',
        'amount',
        'type',
        'date',
        'description',
        'reserved_breakdown_data',
        'status',
    ];

    protected $casts = [
        'date' => 'datetime',
        'reserved_breakdown_data' => 'array',
    ];
}
