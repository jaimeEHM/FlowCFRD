<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $baseAreas = [
            'Gestión de proyectos',
            'Desarrollo',
            'Diseño instruccional',
            'Analítica y datos',
            'Soporte operativo',
        ];

        foreach ($baseAreas as $name) {
            Area::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }

        $defaultCoordinatorId = User::query()
            ->role('coordinador')
            ->orderBy('id')
            ->value('id');

        if ($defaultCoordinatorId !== null) {
            Area::query()
                ->whereNull('coordinator_user_id')
                ->update(['coordinator_user_id' => $defaultCoordinatorId]);
        }

        // Asignación inicial por cargo/especialidad para entornos demo.
        $areasByKeyword = [
            'jefe' => 'Gestión de proyectos',
            'proyecto' => 'Gestión de proyectos',
            'developer' => 'Desarrollo',
            'desarroll' => 'Desarrollo',
            'diseñ' => 'Diseño instruccional',
            'anal' => 'Analítica y datos',
            'soporte' => 'Soporte operativo',
        ];

        $areaLookup = Area::query()->pluck('id', 'name');

        User::query()->get(['id', 'cargo'])->each(function (User $user) use ($areasByKeyword, $areaLookup): void {
            $cargo = Str::lower((string) ($user->cargo ?? ''));
            if ($cargo === '') {
                return;
            }
            foreach ($areasByKeyword as $keyword => $areaName) {
                if (Str::contains($cargo, $keyword) && isset($areaLookup[$areaName])) {
                    $user->areas()->syncWithoutDetaching([(int) $areaLookup[$areaName]]);
                    return;
                }
            }
        });
    }
}

