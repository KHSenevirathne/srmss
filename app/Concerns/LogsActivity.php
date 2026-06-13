<?php

namespace App\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Drop-in audit logging (HR-02). Any model that uses this trait automatically
 * writes an ActivityLog row whenever it is created, updated, or deleted —
 * capturing the acting user (if any), the action, and a readable label.
 *
 * Eloquent model events are the hook, so logging happens no matter which screen
 * triggered the change, with no per-component wiring.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => $model->recordActivity('created'));
        static::updated(fn (Model $model) => $model->recordActivity('updated'));
        static::deleted(fn (Model $model) => $model->recordActivity('deleted'));
    }

    public function recordActivity(string $event): void
    {
        ActivityLog::create([
            'user_id'      => auth()->id(),
            'event'        => $event,
            'subject_type' => class_basename($this),
            'subject_id'   => $this->getKey(),
            'description'  => ucfirst($event) . ' ' . strtolower(class_basename($this)) . ': ' . $this->activityLabel(),
        ]);
    }

    /** A human-friendly identifier for this record, used in the log description. */
    public function activityLabel(): string
    {
        foreach (['name', 'registration_number', 'code', 'email', 'description'] as $attribute) {
            if (! empty($this->{$attribute})) {
                return (string) $this->{$attribute};
            }
        }

        return '#' . $this->getKey();
    }
}
