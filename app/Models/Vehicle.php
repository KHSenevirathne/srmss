<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use LogsActivity;

    protected $fillable = [
        'registration_number', 'type', 'seating_capacity', 'mileage', 'status',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    /** The most recent maintenance record — drives the "service due" flag. */
    public function latestMaintenance(): HasOne
    {
        return $this->hasOne(MaintenanceLog::class)->latestOfMany('serviced_at');
    }

    /** Service is due when the latest maintenance record's next-due date has passed. */
    public function serviceDue(): bool
    {
        $latest = $this->relationLoaded('latestMaintenance')
            ? $this->latestMaintenance
            : $this->latestMaintenance()->first();

        return $latest?->serviceDue() ?? false;
    }
}
