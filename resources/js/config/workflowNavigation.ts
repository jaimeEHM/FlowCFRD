/**
 * Única fuente de verdad para ítems del menú lateral, accesos rápidos en Inicio
 * y menú móvil (header). Alineado con middleware `role:` en `routes/web.php`.
 */
import {
    BarChart3,
    Bell,
    ChartGantt,
    CheckCircle2,
    ClipboardList,
    FileText,
    FolderKanban,
    Kanban,
    LayoutDashboard,
    LayoutGrid,
    ListTodo,
    Network,
    Plug,
    ScrollText,
    Share2,
    Users,
    Zap,
} from 'lucide-vue-next';
import {
    auditoria,
    lrs,
    notificaciones,
} from '@/routes/sistema';
import {
    backlogTareas,
    equiposCarga,
    validacionAvances,
} from '@/routes/coordinacion';
import { misTareas, urgentes } from '@/routes/colaborador';
import { dashboard } from '@/routes';
import { gantt, indicadores, proyectos, tableroMacro } from '@/routes/pmo';
import { kanban, minutas } from '@/routes/proyecto';
import { mapaRelaciones, matrizSkills } from '@/routes/talento';
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
            label: 'PMO — macro y seguimiento',
            items: [
                navItem('Tablero macro', tableroMacro(), LayoutDashboard, [
                    'pmo',
                    'admin',
                ]),
                navItem('Proyectos', proyectos(), FolderKanban, ['pmo', 'admin']),
                navItem('Indicadores (KPI)', indicadores(), BarChart3, [
                    'pmo',
                    'admin',
                ]),
                navItem('Gantt', gantt(), ChartGantt, ['pmo', 'admin']),
            ],
        },
        {
            label: 'Coordinación — personas y backlog',
            items: [
                navItem('Equipos y carga', equiposCarga(), Users, [
                    'coordinador',
                    'pmo',
                    'admin',
                ]),
                navItem('Backlog', backlogTareas(), ListTodo, [
                    'coordinador',
                    'pmo',
                    'admin',
                ]),
                navItem(
                    'Validación de avances',
                    validacionAvances(),
                    CheckCircle2,
                    ['coordinador', 'pmo', 'admin'],
                ),
            ],
        },
        {
            label: 'Jefe de proyecto — ejecución',
            items: [
                navItem('Kanban', kanban(), Kanban, [
                    'jefe_proyecto',
                    'pmo',
                    'coordinador',
                    'admin',
                ]),
                navItem('Minutas', minutas(), FileText, [
                    'jefe_proyecto',
                    'pmo',
                    'coordinador',
                    'admin',
                ]),
            ],
        },
        {
            label: 'Colaborador — trabajo diario',
            items: [
                navItem('Mis tareas', misTareas(), ClipboardList, [
                    'colaborador',
                    'admin',
                ]),
                navItem('Urgentes', urgentes(), Zap, ['colaborador', 'admin']),
            ],
        },
        {
            label: 'Talento y skills',
            items: [
                navItem('Matriz de skills', matrizSkills(), Share2, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                    'admin',
                ]),
                navItem('Mapa de relaciones', mapaRelaciones(), Network, [
                    'pmo',
                    'coordinador',
                    'jefe_proyecto',
                    'colaborador',
                    'admin',
                ]),
            ],
        },
        {
            label: 'Sistema y cumplimiento',
            items: [
                navItem('Auditoría', auditoria(), ScrollText, ['pmo', 'admin']),
                navItem(
                    'Notificaciones',
                    notificaciones(),
                    Bell,
                    [
                        'pmo',
                        'coordinador',
                        'jefe_proyecto',
                        'colaborador',
                        'admin',
                    ],
                ),
                navItem('LRS / integración', lrs(), Plug, ['pmo', 'admin']),
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
