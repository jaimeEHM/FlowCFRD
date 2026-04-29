<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\CfrdDevUserSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('workflow:verify-dev-account')]
#[Description('Comprueba cuenta(s) de login por contraseña en desarrollo y coherencia de configuración.')]
class WorkflowVerifyDevAccountCommand extends Command
{
    public function handle(): int
    {
        $devEmail = 'admin@cfrd.cl';
        $passwordPlain = CfrdDevUserSeeder::DEV_PASSWORD;

        $this->info('Workflow — verificación de cuenta de desarrollo');
        $this->newLine();

        $configured = config('workflow.dev_password_login_emails', []);
        if (! is_array($configured) || $configured === []) {
            $configured = [strtolower((string) config('workflow.dev_password_login_email'))];
        }
        $this->line('WORKFLOW_DEV_PASSWORD_EMAIL(S) (config): '.implode(', ', $configured));

        $allowedUsers = collect($configured)
            ->map(fn ($email) => User::findForCfrdEmail((string) $email))
            ->filter();
        $allowedIds = $allowedUsers->pluck('id')->unique()->values()->all();
        $this->line('Usuarios resueltos para login web: '.($allowedUsers->isEmpty()
            ? '(ninguno)'
            : $allowedUsers->map(fn (User $u) => "{$u->email} (id {$u->id})")->join(', ')));

        $admin = User::query()->where('email', $devEmail)->first();
        if ($admin === null) {
            $this->error("No hay usuario {$devEmail} en la base de datos.");
            $this->warn('Ejecuta: php artisan migrate:fresh --seed   o   php artisan db:seed --class=CfrdDevUserSeeder');

            return self::FAILURE;
        }

        $this->line("Usuario {$devEmail}: id {$admin->id}");

        if ($admin->password === null) {
            $this->error('El usuario no tiene contraseña en BD (solo OAuth). Vuelve a ejecutar CfrdDevUserSeeder.');

            return self::FAILURE;
        }

        $ok = Hash::check($passwordPlain, $admin->password);
        if ($ok) {
            $this->info('Contraseña del seed (CfrdDevUserSeeder::DEV_PASSWORD): correcta.');
        } else {
            $this->error('La contraseña no coincide con el hash en BD. Ejecuta de nuevo CfrdDevUserSeeder o migrate:fresh --seed.');
        }

        if ($allowedIds !== [] && ! in_array($admin->id, $allowedIds, true)) {
            $this->newLine();
            $this->warn('Atención: admin@cfrd.cl no está en la lista permitida de login por contraseña.');
            $this->warn('Agrégalo en WORKFLOW_DEV_PASSWORD_EMAILS (separado por comas) en .env.');

            return self::FAILURE;
        }

        if (! $ok) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Listo: web (Fortify) y API (Sanctum) deberían aceptar '.$devEmail.' con la contraseña del seed.');

        return self::SUCCESS;
    }
}
