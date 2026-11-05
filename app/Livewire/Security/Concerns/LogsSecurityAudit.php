<?php

namespace App\Livewire\Security\Concerns;

use App\Models\SecurityAudit;
use Illuminate\Support\Facades\Auth;

trait LogsSecurityAudit
{
    protected function logSecurityAudit(
        string $action,
        string $entityType,
        ?string $entityId,
        ?array $before,
        ?array $after
    ): void {
        $actor = Auth::user();

        SecurityAudit::create([
            'actor_user_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => mb_substr(request()->ip() ?? '', 0, 45) ?: null,
            'user_agent' => request()->userAgent() === null ? null : mb_strcut(request()->userAgent(), 0, 65535),
        ]);
    }
}
