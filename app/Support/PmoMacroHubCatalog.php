<?php

namespace App\Support;

/**
 * Catálogo de accesos rápidos del tablero macro (alineado con `workflowNavigation.ts`).
 * Las claves se usan en `pmo_macro_visibility_rules.item_key`.
 */
final class PmoMacroHubCatalog
{
    /**
     * Enlaces equivalentes al menú lateral (sin «Tablero macro», la vista actual).
     *
     * @return list<array{
     *     key: string,
     *     group: string,
     *     title: string,
     *     route: string,
     *     icon: string,
     *     roles: list<string>,
     * }>
     */
    public static function hubLinks(): array
    {
        return [
            [
                'key' => 'hub.dashboard',
                'group' => 'Plataforma',
                'title' => 'Inicio',
                'route' => 'dashboard',
                'icon' => 'LayoutGrid',
                'roles' => [],
            ],
            [
                'key' => 'hub.proyectos',
                'group' => 'PMO — macro y seguimiento',
                'title' => 'Proyectos (listado)',
                'route' => 'pmo.proyectos',
                'icon' => 'FolderOpen',
                'roles' => ['pmo', 'admin'],
            ],
            [
                'key' => 'hub.equipos_carga',
                'group' => 'Coordinación — personas y backlog',
                'title' => 'Equipos y carga',
                'route' => 'coordinacion.equipos-carga',
                'icon' => 'Users',
                'roles' => ['coordinador', 'pmo', 'admin'],
            ],
            [
                'key' => 'hub.backlog_tareas',
                'group' => 'Coordinación — personas y backlog',
                'title' => 'Backlog',
                'route' => 'coordinacion.backlog-tareas',
                'icon' => 'ListTodo',
                'roles' => ['coordinador', 'pmo', 'admin'],
            ],
            [
                'key' => 'hub.validacion_avances',
                'group' => 'Coordinación — personas y backlog',
                'title' => 'Validación de avances',
                'route' => 'coordinacion.validacion-avances',
                'icon' => 'CheckCircle2',
                'roles' => ['coordinador', 'pmo', 'admin'],
            ],
            [
                'key' => 'hub.kanban',
                'group' => 'Jefe de proyecto — ejecución',
                'title' => 'Kanban',
                'route' => 'proyecto.kanban',
                'icon' => 'Kanban',
                'roles' => ['jefe_proyecto', 'pmo', 'coordinador', 'admin'],
            ],
            [
                'key' => 'hub.minutas',
                'group' => 'Jefe de proyecto — ejecución',
                'title' => 'Minutas',
                'route' => 'proyecto.minutas',
                'icon' => 'FileText',
                'roles' => ['jefe_proyecto', 'pmo', 'coordinador', 'admin'],
            ],
            [
                'key' => 'hub.mis_tareas',
                'group' => 'Colaborador — trabajo diario',
                'title' => 'Mis tareas',
                'route' => 'colaborador.mis-tareas',
                'icon' => 'ClipboardList',
                'roles' => ['colaborador', 'admin'],
            ],
            [
                'key' => 'hub.urgentes',
                'group' => 'Colaborador — trabajo diario',
                'title' => 'Urgentes',
                'route' => 'colaborador.urgentes',
                'icon' => 'Zap',
                'roles' => ['colaborador', 'admin'],
            ],
            [
                'key' => 'hub.matriz_skills',
                'group' => 'Talento y skills',
                'title' => 'Matriz de skills',
                'route' => 'talento.matriz-skills',
                'icon' => 'Share2',
                'roles' => ['pmo', 'coordinador', 'jefe_proyecto', 'colaborador', 'admin'],
            ],
            [
                'key' => 'hub.mapa_relaciones',
                'group' => 'Talento y skills',
                'title' => 'Mapa de relaciones',
                'route' => 'talento.mapa-relaciones',
                'icon' => 'Network',
                'roles' => ['pmo', 'coordinador', 'jefe_proyecto', 'colaborador', 'admin'],
            ],
            [
                'key' => 'hub.auditoria',
                'group' => 'Sistema y cumplimiento',
                'title' => 'Auditoría',
                'route' => 'sistema.auditoria',
                'icon' => 'ScrollText',
                'roles' => ['pmo', 'admin'],
            ],
            [
                'key' => 'hub.notificaciones',
                'group' => 'Sistema y cumplimiento',
                'title' => 'Notificaciones',
                'route' => 'sistema.notificaciones',
                'icon' => 'Bell',
                'roles' => ['pmo', 'coordinador', 'jefe_proyecto', 'colaborador', 'admin'],
            ],
            [
                'key' => 'hub.lrs',
                'group' => 'Sistema y cumplimiento',
                'title' => 'Integraciones (LRS)',
                'route' => 'sistema.lrs',
                'icon' => 'Plug',
                'roles' => ['pmo', 'admin'],
            ],
        ];
    }

    /**
     * Pestañas internas del tablero macro (además de «Cartera», siempre visible para quien entra).
     *
     * @return list<array{ key: string, segment: string, title: string, roles: list<string> }>
     */
    public static function tabSegments(): array
    {
        return [
            [
                'key' => 'segment.kpi',
                'segment' => 'kpi',
                'title' => 'Indicadores (KPI)',
                'roles' => ['pmo', 'coordinador', 'admin'],
            ],
            [
                'key' => 'segment.gantt',
                'segment' => 'gantt',
                'title' => 'Gantt',
                'roles' => ['pmo', 'coordinador', 'admin'],
            ],
            [
                'key' => 'segment.calendario',
                'segment' => 'calendario',
                'title' => 'Calendario',
                'roles' => ['pmo', 'coordinador', 'admin'],
            ],
            [
                'key' => 'segment.lista',
                'segment' => 'lista',
                'title' => 'Lista de tareas',
                'roles' => ['jefe_proyecto', 'pmo', 'coordinador', 'admin'],
            ],
            [
                'key' => 'segment.kanban',
                'segment' => 'kanban',
                'title' => 'Kanban',
                'roles' => ['jefe_proyecto', 'pmo', 'coordinador', 'admin'],
            ],
            [
                'key' => 'segment.carga',
                'segment' => 'carga',
                'title' => 'Carga por persona',
                'roles' => ['jefe_proyecto', 'pmo', 'coordinador', 'admin'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function allItemKeys(): array
    {
        $keys = array_map(fn (array $r) => $r['key'], self::hubLinks());
        foreach (self::tabSegments() as $s) {
            $keys[] = $s['key'];
        }

        return $keys;
    }
}
