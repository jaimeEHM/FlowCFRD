<?php

namespace App\Http\Middleware;

use App\Models\Project;
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
        $packagePath = base_path('package.json');
        if (is_readable($packagePath)) {
            $decoded = json_decode((string) file_get_contents($packagePath), true);
            if (is_array($decoded) && isset($decoded['version']) && is_string($decoded['version'])) {
                $composerVersion = $decoded['version'];
            }
        } elseif (is_readable(base_path('composer.json'))) {
            $decoded = json_decode((string) file_get_contents(base_path('composer.json')), true);
            if (is_array($decoded) && isset($decoded['version']) && is_string($decoded['version'])) {
                $composerVersion = $decoded['version'];
            }
        }

        $user = $request->user();
        if ($user !== null) {
            $user->loadMissing('areas:id,name');
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'appVersion' => $composerVersion,
            'cfrdDomain' => config('workflow.cfrd_email_domain'),
            'workflowRealtimeEnabled' => config('broadcasting.default') !== 'null',
            'unread_notifications_count' => $user !== null
                ? $user->unreadNotifications()->count()
                : 0,
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'sidebarProjects' => $user !== null
                ? Project::queryForUser($user)
                    ->orderBy('name')
                    ->get(['id', 'name', 'code', 'status'])
                    ->map(fn (Project $p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'status' => $p->status,
                    ])
                    ->values()
                    ->all()
                : [],
            'sidebarCanCreateProject' => $user !== null && $user->hasRole(['admin', 'pmo']),
        ];
    }
}
