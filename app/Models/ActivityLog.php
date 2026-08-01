<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One audit-trail entry. Doesn't use the LogsActivity trait itself — that would recurse. */
class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'event', 'subject_type', 'subject_id', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
