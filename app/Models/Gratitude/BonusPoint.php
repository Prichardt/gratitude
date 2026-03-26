<?php

namespace App\Models\Gratitude;


use App\Models\Gratitude\RedeemPointsDetails;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BonusPoint extends Model
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
        'date',
        'category',
        'type',
        'points',
        'redeemed_points',
        'redemption_history',
        'amount',
        'description',
        'status',
        'usable_date',
        'expires_at',
        'expires_at_manual',
    ];

    protected $casts = [
        'usable_date' => 'date',
        'expires_at' => 'datetime',
        'expires_at_manual' => 'boolean',
        'date' => 'date',
        'redemption_history' => 'array',
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
        return $query->whereIn('status', [true, 1, '1', 'active']);
    }
}
