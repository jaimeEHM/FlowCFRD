<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Support\CfrdEquipoAvatars;
use Database\Seeders\Support\CfrdEquipoConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CfrdDevUserSeeder extends Seeder
{
    /**
     * Contraseña de desarrollo compartida (usuarios sembrados en BD).
     * El formulario email/contraseña solo admite `workflow.dev_password_login_email` (p. ej. director en seed).
     */
    public const DEV_PASSWORD = 'cf753rd/';

    public function run(): void
    {
        $hashed = Hash::make(self::DEV_PASSWORD);
        $data = CfrdEquipoConfig::read();

        foreach ($data['equipo'] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $email = $row['email'] ?? null;
            $nombre = $row['nombre'] ?? null;
            $roles = $row['roles'] ?? null;
            if (! is_string($email) || ! is_string($nombre) || ! is_array($roles)) {
                continue;
            }

            $cargo = isset($row['cargo']) && is_string($row['cargo']) && $row['cargo'] !== ''
                ? $row['cargo']
                : null;
            $name = $cargo !== null ? $nombre.' — '.$cargo : $nombre;

            $avatar = CfrdEquipoAvatars::dataUrlForEmail($email);

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'cargo' => $cargo,
                    'password' => $hashed,
                    'email_verified_at' => now(),
                    'avatar' => $avatar,
                ],
            );

            $user->syncRoles($roles);
        }

        $this->pruneUsersNotInEquipo($data['equipo']);
    }

    /**
     * Elimina cuentas que no figuren en `cfrd_equipo.json` (p. ej. usuarios de prueba antiguos @cfrd.cl).
     *
     * @param  list<mixed>  $equipoRows
     */
    private function pruneUsersNotInEquipo(array $equipoRows): void
    {
        $allowed = collect($equipoRows)
            ->filter(fn ($row) => is_array($row))
            ->pluck('email')
            ->filter(fn ($e) => is_string($e) && $e !== '')
            ->map(fn (string $e) => strtolower($e))
            ->unique()
            ->values()
            ->all();

        if ($allowed === []) {
            return;
        }

        User::query()
            ->whereNotIn('email', $allowed)
            ->orderBy('id')
            ->each(function (User $user): void {
                $user->syncRoles([]);
                $user->delete();
            });
    }
}
