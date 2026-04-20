<?php

namespace App\NotificationsV2\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_type_id',
        'version',
        'subject',
        'body_html',
        'body_text',
        'variables_schema',
        'is_active',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'variables_schema' => 'array',
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
        ];
    }

    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class);
    }
}
