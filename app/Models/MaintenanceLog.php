<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    protected $fillable = ['vehicle_id', 'type', 'description', 'cost', 'serviced_at', 'next_due_at'];

    protected $casts = [
        'serviced_at' => 'date',
        'next_due_at' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
