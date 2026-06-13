<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'phone', 'email', 'address',
        'license_number', 'license_expiry', 'weekly_hours', 'status',
    ];

    protected $casts = [
        'license_expiry' => 'date',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    // Helper used by the dashboard alerts panel (see docs/MODULES.md).
    public function licenseExpiringSoon(int $days = 30): bool
    {
        return $this->license_expiry?->isBetween(now(), now()->addDays($days)) ?? false;
    }
}
