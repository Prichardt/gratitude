<?php

namespace App\Models\Gratitude;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RedeemPointsDetails extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    protected $fillable = [
        'old_id',
        'user_id',
        'redeem_id',
        'source_id',
        'source_type',
        'points',
        'points_breakdown',
    ];

    protected $casts = [
        'points_breakdown' => 'array',
        'points' => 'integer',
    ];

    /**
     * Get the parent redeem record.
     */
    public function redeemPoint()
    {
        return $this->belongsTo(RedeemPoints::class, 'redeem_id');
    }

    /**
     * Get the parent source model (EarnedPoint or BonusPoint).
     */
    public function source()
    {
        return $this->morphTo();
    }
}
