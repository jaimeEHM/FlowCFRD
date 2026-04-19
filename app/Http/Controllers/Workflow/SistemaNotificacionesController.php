<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SistemaNotificacionesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ]);

        return Inertia::render('sistema/Notificaciones', [
            'notifications' => $notifications,
        ]);
    }
}
