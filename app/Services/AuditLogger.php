<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(string $action, ?Model $auditable = null, ?array $properties = null): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable !== null ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'properties' => $properties,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
