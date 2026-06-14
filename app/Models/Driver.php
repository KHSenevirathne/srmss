<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'nic', 'employee_number', 'phone', 'email', 'address',
        'license_number', 'license_expiry', 'weekly_hours', 'status',
    ];

    protected $casts = [
        'license_expiry' => 'date',
    ];

    protected static function booted(): void
    {
        // Auto-assign a short employee number on first save if one wasn't given.
        static::creating(function (Driver $driver) {
            if (blank($driver->employee_number)) {
                $driver->employee_number = static::nextEmployeeNumber();
            }
        });
    }

    /** Next short employee number, e.g. E-001 : one past the current highest. */
    public static function nextEmployeeNumber(): string
    {
        $max = static::query()
            ->whereNotNull('employee_number')
            ->pluck('employee_number')
            ->map(fn ($e) => (int) preg_replace('/\D/', '', (string) $e))
            ->max() ?? 0;

        return 'E-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

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
