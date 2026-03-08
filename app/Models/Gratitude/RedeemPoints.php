<?php

namespace App\Models\Gratitude;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RedeemPoints extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    protected $fillable = [
        'old_id',
        'user_id',
        'journey_id',
        'cancel_id',
        'gratitudeNumber',
        'amount',
        'points',
        'roomStatus',
        'status',
        'category',
        'reason',
        'points_breakdown',
    ];

    protected $casts = [
        'points_breakdown' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the details for the redeem point.
     */
    public function details()
    {
        return $this->hasMany(RedeemPointsDetails::class, 'redeem_id');
    }
}
