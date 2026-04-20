<?php

namespace App\NotificationsV2\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'channel',
        'is_active',
        'priority',
        'copy_contexts',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'copy_contexts' => 'array',
        ];
    }

    public function templates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(NotificationDispatch::class);
    }
}
