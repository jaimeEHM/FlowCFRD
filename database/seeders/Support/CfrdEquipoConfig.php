<?php

namespace Database\Seeders\Support;

/**
 * Lee `database/data/cfrd_equipo.json` (equipo + vinculación demo).
 * Si el archivo falta o es inválido, usa datos embebidos equivalentes al seed anterior.
 */
final class CfrdEquipoConfig
{
    public static function path(): string
    {
        return database_path('data/cfrd_equipo.json');
    }

    /**
     * @return array{equipo: list<array<string, mixed>>, vinculacion_demo: array<string, string>}
     */
    public static function read(): array
    {
        $path = self::path();
        if (is_readable($path)) {
            try {
                $raw = file_get_contents($path);
                if ($raw !== false) {
                    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($data) && isset($data['equipo']) && is_array($data['equipo'])) {
                        $data['vinculacion_demo'] = is_array($data['vinculacion_demo'] ?? null)
                            ? $data['vinculacion_demo']
                            : self::defaultVinculacionDemo();

                        return $data;
                    }
                }
            } catch (\Throwable) {
                // fallback
            }
        }

        return [
            'equipo' => self::defaultEquipo(),
            'vinculacion_demo' => self::defaultVinculacionDemo(),
        ];
    }

    /**
     * @return list<array{email: string, nombre: string, cargo?: string, roles: list<string>}>
     */
    public static function defaultEquipo(): array
    {
        return [
            ['email' => 'admin@cfrd.cl', 'nombre' => 'Administrador Workflow', 'cargo' => 'Plataforma y desarrollo', 'roles' => ['admin']],
            ['email' => 'dbordon@udec.cl', 'nombre' => 'Daniel Bordon O.', 'cargo' => 'Director', 'roles' => ['admin']],
            ['email' => 'marcospalma@udec.cl', 'nombre' => 'Marcos Palma M.', 'cargo' => 'Ingeniero de Proyectos', 'roles' => ['pmo', 'jefe_proyecto']],
            ['email' => 'pmartinez@udec.cl', 'nombre' => 'Pedro Martínez C.', 'cargo' => 'Coordinador', 'roles' => ['coordinador']],
            ['email' => 'lletelie@udec.cl', 'nombre' => 'Leonardo Letelier S.', 'cargo' => 'Ingeniero de Proyectos', 'roles' => ['pmo', 'jefe_proyecto']],
            ['email' => 'fsuazo@udec.cl', 'nombre' => 'Francisca Suazo M.', 'cargo' => 'Arquitecta de Información', 'roles' => ['colaborador']],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultVinculacionDemo(): array
    {
        return [
            'admin_email' => 'dbordon@udec.cl',
            'pmo_email' => 'marcospalma@udec.cl',
            'coordinador_email' => 'pmartinez@udec.cl',
            'jefe_proyecto_principal_email' => 'marcospalma@udec.cl',
            'jefe_proyecto_secundario_email' => 'lletelie@udec.cl',
            'colaborador_principal_email' => 'fsuazo@udec.cl',
            'colaborador_secundario_email' => 'aalcaino@udec.cl',
        ];
    }

    /**
     * @param  array<string, string>  $v
     */
    public static function emailOr(array $v, string $key, string $fallback): string
    {
        $e = $v[$key] ?? '';

        return is_string($e) && $e !== '' ? $e : $fallback;
    }
}
