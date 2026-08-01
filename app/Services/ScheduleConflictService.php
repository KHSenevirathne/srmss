<?php

namespace App\Services;

use App\Models\Schedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Two schedules clash when they share the same vehicle or driver and both their
 * date ranges and daily time windows overlap. Cancelled schedules never clash.
 */
class ScheduleConflictService
{
    /**
     * Existing schedules that clash with the given attributes.
     *
     * @param  array{vehicle_id:int|string, driver_id:int|string, departure_time:string, arrival_time:string, valid_from:string, valid_to:?string}  $attributes
     * @return Collection<int, Schedule>
     */
    public function conflicts(array $attributes, ?int $ignoreId = null): Collection
    {
        return Schedule::query()
            ->where('status', '!=', 'cancelled')
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where(fn ($q) => $q
                ->where('vehicle_id', $attributes['vehicle_id'])
                ->orWhere('driver_id', $attributes['driver_id']))
            ->get()
            ->filter(fn (Schedule $existing) => $this->clashes($attributes, $existing))
            ->values();
    }

    public function hasConflict(array $attributes, ?int $ignoreId = null): bool
    {
        return $this->conflicts($attributes, $ignoreId)->isNotEmpty();
    }

    /** Does the candidate attribute set clash with one existing schedule? */
    private function clashes(array $a, Schedule $b): bool
    {
        return $this->rangesOverlap(
            Carbon::parse($a['valid_from']),
            empty($a['valid_to']) ? null : Carbon::parse($a['valid_to']),
            $b->valid_from,
            $b->valid_to,
        ) && $this->timeRangesOverlap(
            $a['departure_time'],
            $a['arrival_time'],
            (string) $b->departure_time,
            (string) $b->arrival_time,
        );
    }

    /** A null start/end means open-ended, not a fixed boundary. */
    public function rangesOverlap(?CarbonInterface $aFrom, ?CarbonInterface $aTo, ?CarbonInterface $bFrom, ?CarbonInterface $bTo): bool
    {
        $aStartsBeforeBEnds = $bTo === null || $aFrom === null || $aFrom->lte($bTo);
        $bStartsBeforeAEnds = $aTo === null || $bFrom === null || $bFrom->lte($aTo);

        return $aStartsBeforeBEnds && $bStartsBeforeAEnds;
    }

    /** Back-to-back windows (one ends exactly when the next starts) don't count as overlapping. */
    public function timeRangesOverlap(string $aDep, string $aArr, string $bDep, string $bArr): bool
    {
        return $this->minutes($aDep) < $this->minutes($bArr)
            && $this->minutes($bDep) < $this->minutes($aArr);
    }

    /** Minutes since midnight for a "H:i" or "H:i:s" time string. */
    private function minutes(string $time): int
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '0');

        return ((int) $h) * 60 + (int) $m;
    }
}
