<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
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

        /** @var list<string> $allowedDomains */
        $allowedDomains = config('workflow.cfrd_email_domains', []);
        $domainOk = collect($allowedDomains)->contains(fn (string $d) => str_ends_with($email, '@'.$d));

        if (! $domainOk) {
            $suffixes = collect($allowedDomains)->map(fn (string $d) => '@'.$d)->implode(', ');

            return redirect()
                ->route('login')
                ->with('status', 'Solo se permiten cuentas corporativas ('.$suffixes.').');
        }

        $user = User::findForCfrdEmail($email);

        if ($user === null) {
            return redirect()
                ->route('login')
                ->with('status', 'Tu cuenta no está registrada en Workflow. Solicita acceso al administrador del CFRD.');
        }

        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
