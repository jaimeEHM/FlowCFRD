<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Roles de negocio Workflow (CFRD / UdeC).
     *
     * @var list<string>
     */
    public const ROLE_NAMES = [
        'admin',
        'pmo',
        'coordinador',
        'jefe_proyecto',
        'colaborador',
    ];

    public function run(): void
    {
        foreach (self::ROLE_NAMES as $name) {
            Role::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
            );
        }
    }
}
