<?php

namespace App\Models\Gratitude;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Gratitude\PointRedemption;

class EarnedPoint extends Model
{
    use HasFactory, LogsActivity;

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
        'points',
        'points_breakdown',
        'redeemed_points',
        'redemption_history',
        'amount',
        'date',
        'description',
        'category',
        'status',
        'usable_date',
        'expires_at',
        'expires_at_manual',
        'project_data'
    ];

    protected $casts = [
        'usable_date' => 'date',
        'expires_at' => 'datetime',
        'expires_at_manual' => 'boolean',
        'date' => 'date',
        'redemption_history' => 'array',
        'points_breakdown' => 'array',
        'project_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function redemptions()
    {
        return $this->morphMany(RedeemPointsDetails::class, 'source');
    }

    public function cancellation()
    {
        return $this->belongsTo(Cancellation::class, 'cancel_id');
    }

    public function scopeActiveStatus($query)
    {
        return $query->whereIn('status', ['active', 'approved', 1, '1']);
    }
}
