<?php

namespace App\NotificationsV2\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_type_id',
        'template_id',
        'event_key',
        'idempotency_key',
        'payload',
        'status',
        'scheduled_for',
        'queued_at',
        'processed_at',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'scheduled_for' => 'datetime',
            'queued_at' => 'datetime',
            'processed_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class, 'dispatch_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(NotificationAttempt::class, 'dispatch_id');
    }
}
