/**
 * Menú lateral reducido: solo Inicio. La cartera de proyectos se muestra aparte
 * (`PmoMacroSidebarProjects`) con datos compartidos por Inertia.
 */
import { LayoutGrid } from 'lucide-vue-next';
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
