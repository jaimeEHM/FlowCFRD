/**
 * Props Inertia a recargar ante eventos Reverb (evita `router.reload()` completo).
 * Claves deben coincidir con los arrays del `Inertia::render` de cada página.
 */
export const WORKFLOW_INERTIA_RELOAD_ONLY: Record<string, string[]> = {
    Dashboard: ['blocks', 'greeting'],
    'colaborador/MisTareas': ['tasks'],
    'colaborador/Urgentes': ['tasks'],
    /** Coincide con `ProyectoKanbanController@index`: `groups`, no `columns`. */
    'proyecto/Kanban': ['project', 'projects', 'groups', 'statuses', 'peopleOptions'],
    'proyecto/Minutas': ['minutes', 'projects'],
    'coordinacion/BacklogTareas': ['tasks', 'projects', 'statuses'],
    'coordinacion/ValidacionAvances': ['urgent_tasks', 'skill_validations'],
    'coordinacion/EquiposCarga': ['users'],
    'pmo/Proyectos': ['projects', 'statuses'],
    'pmo/TableroMacro': [
        'activeSegment',
        'projects',
        'selectedProjectId',
        'selectedProject',
        'statuses',
        'jefeOptions',
        'ganttProjects',
        'visibleTabSegments',
        'canEditCarteraFull',
        'listaTaskGroups',
        'listaTasks',
        'listaPeopleOptions',
        'listaPortfolioMode',
        'kanbanBoards',
        'kanbanPeopleOptions',
        'kanbanPortfolioMode',
        'portfolioWorkload',
        'taskStatuses',
        'usuarios_total',
        'proyectos_total',
        'tareas_total',
        'tareas_abiertas',
        'tareas_urgentes_pendientes',
        'projects_by_status',
        'tasks_by_status',
        'tasks_abiertas_por_responsable',
    ],
    'talento/MatrizSkills': ['skills', 'users_with_skills'],
    'talento/MapaRelaciones': ['edges', 'nodes_summary'],
    'sistema/Notificaciones': ['notifications'],
    'sistema/Auditoria': ['logs'],
    'sistema/LrsIntegracion': [],
};

export function workflowReloadOnlyKeys(
    component: string | undefined,
): string[] | null {
    if (!component) {
        return null;
    }
    const keys = WORKFLOW_INERTIA_RELOAD_ONLY[component];
    if (keys === undefined) {
        return null;
    }
    if (keys.length === 0) {
        return null;
    }
    return keys;
}
