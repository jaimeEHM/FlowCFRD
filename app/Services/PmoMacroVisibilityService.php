<?php

namespace App\Services;

use App\Models\PmoMacroVisibilityRule;
use App\Models\User;
use App\Support\PmoMacroHubCatalog;
use Illuminate\Support\Facades\Route;

final class PmoMacroVisibilityService
{
    /**
     * Roles efectivos para un ítem (catálogo + override en BD). `admin` siempre incluido en la práctica vía {@see userMaySee}.
     *
     * @return list<string>
     */
    public function effectiveRolesForItem(string $itemKey): array
    {
        $defaults = $this->defaultRolesForKey($itemKey);
        $rule = PmoMacroVisibilityRule::query()->where('item_key', $itemKey)->first();
        if ($rule === null || $rule->allowed_roles === null) {
            return $defaults;
        }

        $merged = array_values(array_unique(array_map('strval', $rule->allowed_roles)));

        return $merged !== [] ? $merged : $defaults;
    }

    /**
     * @return list<string>
     */
    public function defaultRolesForKey(string $itemKey): array
    {
        foreach (PmoMacroHubCatalog::hubLinks() as $row) {
            if ($row['key'] === $itemKey) {
                return $row['roles'];
            }
        }
        foreach (PmoMacroHubCatalog::tabSegments() as $row) {
            if ($row['key'] === $itemKey) {
                return $row['roles'];
            }
        }

        return [];
    }

    public function userMaySee(User $user, string $itemKey): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $roles = $this->effectiveRolesForItem($itemKey);
        if ($roles === []) {
            return true;
        }

        foreach ($roles as $slug) {
            if ($user->hasRole($slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{ group: string, title: string, href: string, icon: string }>
     */
    public function visibleHubLinksFor(User $user): array
    {
        $out = [];
        foreach (PmoMacroHubCatalog::hubLinks() as $row) {
            if (! $this->userMaySee($user, $row['key'])) {
                continue;
            }
            if (! Route::has($row['route'])) {
                continue;
            }
            $out[] = [
                'group' => $row['group'],
                'title' => $row['title'],
                'href' => route($row['route']),
                'icon' => $row['icon'],
            ];
        }

        return $out;
    }

    /**
     * @return array{kpi: bool, gantt: bool, lista: bool, kanban: bool, carga: bool}
     */
    public function visibleTabSegmentsFor(User $user): array
    {
        return [
            'kpi' => $this->userMaySee($user, 'segment.kpi'),
            'gantt' => $this->userMaySee($user, 'segment.gantt'),
            'lista' => $this->userMaySee($user, 'segment.lista'),
            'kanban' => $this->userMaySee($user, 'segment.kanban'),
            'carga' => $this->userMaySee($user, 'segment.carga'),
        ];
    }

    /**
     * Matriz para la UI de gestión PMO: defaults + efectivos.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     kind: 'hub'|'segment',
     *     default_roles: list<string>,
     *     effective_roles: list<string>,
     * }>
     */
    public function managementMatrix(): array
    {
        $rows = [];
        foreach (PmoMacroHubCatalog::hubLinks() as $row) {
            $key = $row['key'];
            $rows[] = [
                'key' => $key,
                'label' => $row['group'].' — '.$row['title'],
                'kind' => 'hub',
                'default_roles' => $row['roles'],
                'effective_roles' => $this->effectiveRolesForItem($key),
            ];
        }
        foreach (PmoMacroHubCatalog::tabSegments() as $row) {
            $key = $row['key'];
            $rows[] = [
                'key' => $key,
                'label' => 'Tablero macro — '.$row['title'],
                'kind' => 'segment',
                'default_roles' => $row['roles'],
                'effective_roles' => $this->effectiveRolesForItem($key),
            ];
        }

        return $rows;
    }
}
