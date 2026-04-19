<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\CfrdDevUserSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('workflow:verify-dev-account')]
#[Description('Comprueba que exista admin@cfrd.cl, contraseña del seed y coherencia con WORKFLOW_DEV_PASSWORD_EMAIL (login web).')]
class WorkflowVerifyDevAccountCommand extends Command
{
    public function handle(): int
    {
        $devEmail = 'admin@cfrd.cl';
        $passwordPlain = CfrdDevUserSeeder::DEV_PASSWORD;

        $this->info('Workflow — verificación de cuenta de desarrollo');
        $this->newLine();

        $configured = strtolower((string) config('workflow.dev_password_login_email'));
        $this->line('WORKFLOW_DEV_PASSWORD_EMAIL (config): '.$configured);

        $allowed = User::findForCfrdEmail($configured);
        $this->line('Usuario resuelto para ese correo: '.($allowed ? "{$allowed->email} (id {$allowed->id})" : '(ninguno)'));

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

        if ($allowed !== null && $admin->id !== $allowed->id) {
            $this->newLine();
            $this->warn('Atención: el login web por contraseña solo acepta el correo WORKFLOW_DEV_PASSWORD_EMAIL.');
            $this->warn('Ese correo apunta al usuario id '.$allowed->id.', pero admin@cfrd.cl es id '.$admin->id.'.');
            $this->warn('Para entrar con admin@cfrd.cl en la web, pon WORKFLOW_DEV_PASSWORD_EMAIL=admin@cfrd.cl en .env');

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
