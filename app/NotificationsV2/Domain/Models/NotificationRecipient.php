<?php

namespace App\NotificationsV2\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispatch_id',
        'type',
        'name',
        'email',
        'context',
        'status',
        'provider_message_id',
        'failure_reason',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(NotificationDispatch::class, 'dispatch_id');
    }
}
