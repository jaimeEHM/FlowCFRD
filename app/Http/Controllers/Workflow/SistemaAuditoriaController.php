<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Inertia\Inertia;
use Inertia\Response;

class SistemaAuditoriaController extends Controller
{
    public function __invoke(): Response
    {
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(150)
            ->get();

        return Inertia::render('sistema/Auditoria', [
            'logs' => $logs,
        ]);
    }
}
