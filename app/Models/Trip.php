<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    /** Every status a trip may hold. 'cancelled' is staff-only (manage-schedules). */
    public const STATUSES = ['scheduled', 'on_time', 'delayed', 'completed', 'cancelled'];

    /** The subset a driver may request for their own trips (never 'cancelled'). */
    public const DRIVER_REQUESTABLE = ['on_time', 'delayed', 'completed'];

    protected $fillable = [
        'schedule_id', 'trip_date', 'status', 'actual_departure', 'actual_arrival',
        'pending_status', 'status_requested_by', 'status_requested_at',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'status_requested_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /** The driver login that requested the currently pending status change, if any. */
    public function statusRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_requested_by');
    }

    /** True when a driver's requested status is awaiting staff approval. */
    public function hasPendingStatus(): bool
    {
        return $this->pending_status !== null;
    }
}
