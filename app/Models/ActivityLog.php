<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in the audit trail (HR-02). Created by the LogsActivity trait and the
 * auth event listeners — never edited or deleted through the UI, so the log stays
 * a trustworthy record. This model deliberately does NOT use LogsActivity itself
 * (that would recurse).
 */
class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'event', 'subject_type', 'subject_id', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
