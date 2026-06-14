<?php

use App\Services\ScheduleConflictService;
use Illuminate\Support\Carbon;

/*
 * Phase 4 : pure unit tests for the conflict-detection overlap rules. These
 * exercise the date/time maths directly, with no database (per docs/MODULES.md:
 * "Write unit tests for this first").
 */

beforeEach(function () {
    $this->service = new ScheduleConflictService();
});

function d(string $date): Carbon
{
    return Carbon::parse($date);
}

// --- Date range overlap -----------------------------------------------------

test('date ranges that overlap are detected', function () {
    expect($this->service->rangesOverlap(
        d('2026-01-01'), d('2026-01-31'),
        d('2026-01-15'), d('2026-02-15'),
    ))->toBeTrue();
});

test('date ranges that do not overlap are not flagged', function () {
    expect($this->service->rangesOverlap(
        d('2026-01-01'), d('2026-01-31'),
        d('2026-02-01'), d('2026-02-28'),
    ))->toBeFalse();
});

test('an open-ended range (null end) overlaps any later range', function () {
    expect($this->service->rangesOverlap(
        d('2026-01-01'), null,            // open-ended
        d('2027-06-01'), d('2027-06-30'),
    ))->toBeTrue();
});

test('ranges that merely touch at the boundary still overlap', function () {
    expect($this->service->rangesOverlap(
        d('2026-01-01'), d('2026-01-31'),
        d('2026-01-31'), d('2026-02-10'),
    ))->toBeTrue();
});

// --- Time window overlap ----------------------------------------------------

test('time windows that overlap are detected', function () {
    expect($this->service->timeRangesOverlap('06:00', '08:00', '07:00', '09:00'))->toBeTrue();
});

test('back-to-back time windows do not overlap', function () {
    expect($this->service->timeRangesOverlap('06:00', '07:00', '07:00', '08:00'))->toBeFalse();
});

test('disjoint time windows do not overlap', function () {
    expect($this->service->timeRangesOverlap('06:00', '07:00', '09:00', '10:00'))->toBeFalse();
});

test('time comparison tolerates H:i:s strings from the database', function () {
    expect($this->service->timeRangesOverlap('06:00', '08:00', '07:00:00', '09:00:00'))->toBeTrue();
});
