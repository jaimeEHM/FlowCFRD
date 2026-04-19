<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $composerVersion = '0.0.0';
        $composerPath = base_path('composer.json');
        if (is_readable($composerPath)) {
            $decoded = json_decode((string) file_get_contents($composerPath), true);
            if (is_array($decoded) && isset($decoded['version']) && is_string($decoded['version'])) {
                $composerVersion = $decoded['version'];
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'appVersion' => $composerVersion,
            'cfrdDomain' => config('workflow.cfrd_email_domain'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
