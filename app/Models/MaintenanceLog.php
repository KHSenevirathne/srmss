<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    use LogsActivity;

    protected $fillable = ['vehicle_id', 'type', 'description', 'cost', 'serviced_at', 'next_due_at'];

    protected $casts = [
        'serviced_at' => 'date',
        'next_due_at' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * "Service due" when a next-service date is set and has reached/passed today.
     * See docs/DATA_MODEL.md : surfaced on the maintenance log and dashboard alerts.
     */
    public function serviceDue(): bool
    {
        return $this->next_due_at !== null && $this->next_due_at->lte(now()->startOfDay());
    }
}
