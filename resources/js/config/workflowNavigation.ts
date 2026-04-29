import {
    BarChart3,
    Bell,
    Briefcase,
    Calendar,
    ClipboardList,
    Columns3,
    FolderKanban,
    Gauge,
    LayoutGrid,
    ListChecks,
    Network,
    ShieldCheck,
    Target,
    Users,
    Wrench,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

/** Roles Spatie que pueden ver el ítem (OR). Vacío = cualquier autenticado. `admin` ve todo en el filtro. */
export type WorkflowNavItem = NavItem & {
    roles?: string[];
};

export type WorkflowNavGroup = {
    label: string;
    items: WorkflowNavItem[];
};

function navItem(
    title: string,
    href: NavItem['href'],
    icon: WorkflowNavItem['icon'],
    roles?: string[],
): WorkflowNavItem {
    return roles?.length
        ? { title, href, icon, roles }
        : { title, href, icon };
}

export function getWorkflowNavGroups(): WorkflowNavGroup[] {
    return [
        {
            label: 'Plataforma',
            items: [navItem('Inicio', dashboard(), LayoutGrid)],
        },
        {
            label: 'PMO',
            items: [
                navItem('Cartera macro', '/pmo/tablero-macro', Briefcase, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                ]),
                navItem('Indicadores', '/pmo/tablero-macro?segment=kpi', BarChart3, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                ]),
                navItem('Cronograma macro', '/pmo/tablero-macro?segment=gantt', Calendar, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                ]),
                navItem('Calendario macro', '/pmo/tablero-macro?segment=calendario', Calendar, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                ]),
                navItem('Lista macro', '/pmo/tablero-macro?segment=lista', ListChecks, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                ]),
                navItem('Kanban macro', '/pmo/tablero-macro?segment=kanban', Columns3, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                ]),
                navItem('Carga equipo', '/pmo/tablero-macro?segment=carga', Gauge, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                ]),
                navItem('Gestión de proyectos', '/pmo/proyectos', Wrench, ['pmo']),
            ],
        },
        {
            label: 'Coordinación',
            items: [
                navItem('Equipos y carga', '/coordinacion/equipos-carga', Users, [
                    'pmo',
                    'coordinador',
                ]),
                navItem('Backlog de tareas', '/coordinacion/backlog-tareas', ClipboardList, [
                    'pmo',
                    'coordinador',
                ]),
                navItem('Validación de avances', '/coordinacion/validacion-avances', ShieldCheck, [
                    'pmo',
                    'coordinador',
                ]),
            ],
        },
        {
            label: 'Proyecto',
            items: [
                navItem('Tabla', '/proyecto/tabla', ListChecks, ['pmo', 'coordinador', 'jefe_proyecto']),
                navItem('Cronograma', '/proyecto/cronograma', Calendar, ['pmo', 'coordinador', 'jefe_proyecto']),
                navItem('Calendario', '/proyecto/calendario', Calendar, ['pmo', 'coordinador', 'jefe_proyecto']),
                navItem('Kanban', '/proyecto/kanban', FolderKanban, ['pmo', 'coordinador', 'jefe_proyecto']),
                navItem('Minutas', '/proyecto/minutas', ClipboardList, ['pmo', 'coordinador', 'jefe_proyecto']),
            ],
        },
        {
            label: 'Colaborador',
            items: [
                navItem('Mis tareas', '/colaborador/mis-tareas', Target, ['colaborador']),
                navItem('Urgentes', '/colaborador/urgentes', Bell, ['colaborador']),
            ],
        },
        {
            label: 'Talento',
            items: [
                navItem('Matriz de skills', '/talento/matriz-skills', Users, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                ]),
                navItem('Mapa de relaciones', '/talento/mapa-relaciones', Network, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                ]),
            ],
        },
        {
            label: 'Sistema',
            items: [
                navItem('Auditoría', '/sistema/auditoria', ShieldCheck, ['pmo']),
                navItem('Usuarios y roles', '/sistema/usuarios-roles', Users, ['pmo']),
                navItem('Config. transversal', '/sistema/configuracion-transversal', Wrench, ['pmo']),
                navItem('Notificaciones', '/sistema/notificaciones', Bell, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                ]),
                navItem('LRS', '/sistema/lrs', BarChart3, ['pmo']),
            ],
        },
    ];
}

/** Oculta ítems y grupos vacíos según `role_slugs` del usuario (admin ve todo). */
export function getWorkflowNavGroupsForUser(
    roleSlugs: string[],
): WorkflowNavGroup[] {
    const canSee = (item: WorkflowNavItem): boolean => {
        if (roleSlugs.includes('admin')) {
            return true;
        }
        if (!item.roles?.length) {
            return true;
        }
        return item.roles.some((r) => roleSlugs.includes(r));
    };

    return getWorkflowNavGroups()
        .map((group) => ({
            ...group,
            items: group.items.filter(canSee),
        }))
        .filter((g) => g.items.length > 0);
}
