<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Spatie\Permission\Models\Role;

class GoogleAuthController extends Controller
{
    /**
     * @var list<string>
     */
    private const APP_ROLE_NAMES = ['admin', 'pmo', 'coordinador', 'jefe_proyecto', 'colaborador'];
    private const DEFAULT_ROLE_FOR_NEW_GOOGLE_USER = 'colaborador';

    /**
     * @return list<string>
     */
    private function allowedDomains(): array
    {
        $single = strtolower(trim((string) config('services.google.allowed_domain', '')));
        if ($single !== '') {
            return [$single];
        }

        /** @var list<string> $domains */
        $domains = config('workflow.cfrd_email_domains', []);

        return array_values(array_filter(array_map(
            fn (string $d) => strtolower(trim($d)),
            $domains
        )));
    }

    /**
     * Login con Google Identity Services (id_token enviado desde la vista de login).
     */
    public function tokenLogin(Request $request): RedirectResponse
    {
        if (empty(config('services.google.client_id'))) {
            return redirect()
                ->route('login')
                ->with('status', 'El acceso con Google no está configurado. Usa el acceso de desarrollo o contacta a sistemas.');
        }

        $validated = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            $tokenInfo = Http::asForm()
                ->timeout(10)
                ->acceptJson()
                ->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $validated['id_token']]);
        } catch (\Throwable) {
            return redirect()
                ->route('login')
                ->with('status', 'No se pudo validar Google en este momento. Inténtalo de nuevo.');
        }

        if (! $tokenInfo->ok()) {
            return redirect()
                ->route('login')
                ->with('status', 'El token de Google es inválido o expiró. Inténtalo de nuevo.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $tokenInfo->json();
        $email = strtolower((string) ($payload['email'] ?? ''));
        $aud = (string) ($payload['aud'] ?? '');
        $sub = (string) ($payload['sub'] ?? '');
        $avatar = (string) ($payload['picture'] ?? '');
        $emailVerified = in_array((string) ($payload['email_verified'] ?? ''), ['true', '1'], true);

        if ($email === '' || $sub === '' || ! $emailVerified) {
            return redirect()
                ->route('login')
                ->with('status', 'Google no entregó una cuenta válida/verificada. Inténtalo con otra cuenta.');
        }

        $configuredClientId = (string) config('services.google.client_id');
        if ($configuredClientId !== '' && $aud !== $configuredClientId) {
            return redirect()
                ->route('login')
                ->with('status', 'La credencial de Google no coincide con la aplicación configurada.');
        }

        $allowedDomains = $this->allowedDomains();
        $domainOk = collect($allowedDomains)->contains(fn (string $d) => str_ends_with($email, '@'.strtolower($d)));

        if (! $domainOk) {
            $suffixes = collect($allowedDomains)->map(fn (string $d) => '@'.$d)->implode(', ');

            return redirect()
                ->route('login')
                ->with('status', 'Solo se permiten cuentas corporativas ('.$suffixes.').');
        }

        $user = $this->resolveOrCreateGoogleUser(
            email: $email,
            googleId: $sub,
            avatar: $avatar,
            name: (string) ($payload['name'] ?? ''),
        );

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Redirige al proveedor Google (OAuth).
     */
    public function redirect(): RedirectResponse
    {
        if (empty(config('services.google.client_id'))) {
            return redirect()
                ->route('login')
                ->with('status', 'El acceso con Google no está configurado. Usa el acceso de desarrollo o contacta a sistemas.');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Callback OAuth: correos en `workflow.cfrd_email_domains`; usuario resuelto con User::findForCfrdEmail().
     */
    public function callback(): RedirectResponse
    {
        if (empty(config('services.google.client_id'))) {
            return redirect()->route('login');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()
                ->route('login')
                ->with('status', 'La sesión de Google expiró o es inválida. Inténtalo de nuevo.');
        } catch (\Throwable) {
            return redirect()
                ->route('login')
                ->with('status', 'No se pudo completar el acceso con Google. Inténtalo de nuevo.');
        }

        $email = strtolower((string) $googleUser->getEmail());
        $googleId = (string) $googleUser->getId();
        if ($email === '' || $googleId === '') {
            return redirect()
                ->route('login')
                ->with('status', 'Google no entregó una cuenta válida. Inténtalo de nuevo.');
        }

        $allowedDomains = $this->allowedDomains();
        $domainOk = collect($allowedDomains)->contains(fn (string $d) => str_ends_with($email, '@'.$d));

        if (! $domainOk) {
            $suffixes = collect($allowedDomains)->map(fn (string $d) => '@'.$d)->implode(', ');

            return redirect()
                ->route('login')
                ->with('status', 'Solo se permiten cuentas corporativas ('.$suffixes.').');
        }

        $user = $this->resolveOrCreateGoogleUser(
            email: $email,
            googleId: $googleId,
            avatar: (string) ($googleUser->getAvatar() ?? ''),
            name: (string) ($googleUser->getName() ?? ''),
        );

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function resolveOrCreateGoogleUser(string $email, string $googleId, string $avatar, string $name): User
    {
        $user = User::findForCfrdEmail($email);
        if ($user === null) {
            $user = User::query()->create([
                'name' => $name !== '' ? $name : $this->nameFromEmail($email),
                'email' => $email,
                'password' => null,
                'email_verified_at' => now(),
                'google_id' => $googleId,
                'avatar' => $avatar !== '' ? $avatar : null,
            ]);
        } else {
            $user->forceFill([
                'google_id' => $googleId,
                'avatar' => $avatar !== '' ? $avatar : $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        $this->ensureApplicationRole($user);

        return $user;
    }

    private function ensureApplicationRole(User $user): void
    {
        $currentAppRoles = $user->getRoleNames()
            ->filter(fn (string $role) => in_array($role, self::APP_ROLE_NAMES, true))
            ->values();

        if ($currentAppRoles->isNotEmpty()) {
            return;
        }

        Role::findOrCreate(self::DEFAULT_ROLE_FOR_NEW_GOOGLE_USER, 'web');
        $user->assignRole(self::DEFAULT_ROLE_FOR_NEW_GOOGLE_USER);
    }

    private function nameFromEmail(string $email): string
    {
        $local = strstr($email, '@', true);

        return $local !== false && $local !== '' ? $local : $email;
    }
}
