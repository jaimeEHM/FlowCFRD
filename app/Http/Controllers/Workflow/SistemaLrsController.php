<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SistemaLrsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('sistema/LrsIntegracion', [
            'lrs_enabled' => (bool) config('workflow.lrs.enabled'),
            'lrs_endpoint' => config('workflow.lrs.endpoint'),
            'lrs_has_key' => filled(config('workflow.lrs.key')),
        ]);
    }
}
