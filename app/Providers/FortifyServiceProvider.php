<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Solo la cuenta de desarrollo puede usar el formulario email/contraseña.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $attemptEmail = strtolower((string) $request->input('email', ''));
            $allowedEmails = config('workflow.dev_password_login_emails', []);
            if (! is_array($allowedEmails) || $allowedEmails === []) {
                $allowedEmails = [strtolower((string) config('workflow.dev_password_login_email'))];
            }

            $attemptUser = User::findForCfrdEmail($attemptEmail);
            $allowedUserIds = collect($allowedEmails)
                ->filter(fn ($email) => is_string($email) && trim($email) !== '')
                ->map(fn (string $email) => User::findForCfrdEmail($email))
                ->filter()
                ->pluck('id')
                ->unique()
                ->values()
                ->all();

            if ($attemptUser === null || $allowedUserIds === [] || ! in_array($attemptUser->id, $allowedUserIds, true)) {
                return null;
            }

            if ($attemptUser->password === null) {
                return null;
            }

            if (! Hash::check((string) $request->input('password'), $attemptUser->password)) {
                return null;
            }

            return $attemptUser;
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
            'googleOAuthConfigured' => filled(config('services.google.client_id')),
            'googleClientId' => (string) config('services.google.client_id', ''),
            'googleAuthUrl' => route('google.redirect'),
            'googleTokenUrl' => route('google.token'),
            'cfrdDomain' => config('workflow.cfrd_email_domain'),
            'devPasswordLoginEmails' => config('workflow.dev_password_login_emails', []),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
