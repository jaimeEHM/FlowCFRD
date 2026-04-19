<?php

namespace Database\Seeders\Support;

/**
 * Proyectos públicos CFRD (https://cfrd.udec.cl/proyectos-cfrd/) + matriz demo de tareas.
 */
final class CfrdProyectosWebConfig
{
    public static function path(): string
    {
        return database_path('data/cfrd_proyectos_web.json');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function proyectos(): array
    {
        $path = self::path();
        if (! is_readable($path)) {
            return [];
        }

        try {
            $raw = file_get_contents($path);
            if ($raw === false) {
                return [];
            }
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($data) || ! isset($data['proyectos']) || ! is_array($data['proyectos'])) {
                return [];
            }

            return $data['proyectos'];
        } catch (\Throwable) {
            return [];
        }
    }
}
