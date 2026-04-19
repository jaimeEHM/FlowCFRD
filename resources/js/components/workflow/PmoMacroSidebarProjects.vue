<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Folder, LayoutGrid, Plus, Search } from 'lucide-vue-next';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
    useSidebar,
} from '@/components/ui/sidebar';
import { tableroMacro } from '@/routes/pmo';

type Proj = { id: number; name: string; code: string | null; status: string };

const page = usePage();
const { state, isMobile } = useSidebar();

const isMacroPage = computed(() => page.component === 'pmo/TableroMacro');

const projects = computed(
    () => (page.props.sidebarProjects as Proj[] | undefined) ?? [],
);
const canCreateProject = computed(
    () => page.props.sidebarCanCreateProject === true,
);
const selectedProjectId = computed(() => {
    if (!isMacroPage.value) {
        return null;
    }
    return (page.props.selectedProjectId as number | null | undefined) ?? null;
});
const activeSegment = computed(() =>
    isMacroPage.value
        ? (page.props.activeSegment as
              | 'cartera'
              | 'kpi'
              | 'gantt'
              | 'lista'
              | 'kanban'
              | 'carga'
              | undefined)
        : undefined,
);

const portfolioActive = computed(
    () =>
        isMacroPage.value &&
        activeSegment.value === 'cartera' &&
        selectedProjectId.value === null,
);

/** Búsqueda + alta en fila: visible con sidebar expandido o en móvil. */
const showSearchBar = computed(
    () => state.value === 'expanded' || isMobile.value === true,
);

function qTablero(
    extra: Record<string, string | number | undefined>,
): Record<string, string | number> {
    const q: Record<string, string | number> = {};
    const seg = activeSegment.value;
    if (seg && seg !== 'cartera') {
        q.segment = seg;
    }
    for (const [k, v] of Object.entries(extra)) {
        if (v !== undefined && v !== '') {
            q[k] = v;
        }
    }
    return q;
}

function hrefPortfolio(): string {
    const q = qTablero({});
    return Object.keys(q).length > 0
        ? tableroMacro.url({ query: q })
        : tableroMacro.url();
}

function hrefProject(id: number): string {
    return tableroMacro.url({ query: qTablero({ project_id: id }) });
}

function hrefCrear(): string {
    return tableroMacro.url({ query: qTablero({ crear: '1' }) });
}

const listFilter = ref('');

const filteredProjects = computed(() => {
    const s = listFilter.value.trim().toLowerCase();
    const list = projects.value;
    if (s === '') {
        return list;
    }
    return list.filter((p) => {
        const name = p.name.toLowerCase();
        const code = (p.code ?? '').toLowerCase();
        return name.includes(s) || code.includes(s);
    });
});

const emptyPortfolio = computed(
    () => projects.value.length === 0 && listFilter.value.trim() === '',
);
</script>

<template>
    <SidebarSeparator />
    <SidebarGroup class="group/macro-projects">
        <SidebarGroupLabel
            class="text-[11px] text-[#003366]/85"
        >
            Cartera de proyectos
        </SidebarGroupLabel>

        <!-- Misma línea gráfica UdeC que el tablero macro: claros, borde #003366 suave -->
        <div
            v-show="showSearchBar"
            class="mx-1 mb-2 rounded-lg border border-[#003366]/12 bg-[#f8fafc] p-2 shadow-[0_1px_2px_rgba(0,51,102,0.06)]"
        >
            <div class="flex items-center gap-2">
                <div class="relative min-w-0 flex-1">
                    <Search
                        class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        aria-hidden="true"
                        :stroke-width="2"
                    />
                    <input
                        v-model="listFilter"
                        type="search"
                        autocomplete="off"
                        placeholder="Buscar"
                        class="h-9 w-full rounded-md border border-slate-200 bg-white pl-9 pr-2 text-sm text-slate-800 shadow-sm outline-none placeholder:text-slate-400 focus:border-[#003366] focus:ring-1 focus:ring-[#003366]"
                    />
                </div>
                <Link
                    v-if="canCreateProject"
                    :href="hrefCrear()"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-[#003366] text-white shadow-sm transition hover:bg-[#00264d] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#003366]"
                    aria-label="Nuevo proyecto"
                >
                    <Plus class="h-5 w-5" :stroke-width="2.5" aria-hidden="true" />
                </Link>
            </div>
        </div>

        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton
                    as-child
                    :is-active="portfolioActive"
                    tooltip="Cartera completa (tabla)"
                >
                    <Link
                        :href="hrefPortfolio()"
                        class="flex w-full min-w-0 items-center gap-2"
                    >
                        <LayoutGrid class="shrink-0" aria-hidden="true" />
                        <span class="truncate">Cartera completa</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>

        <SidebarGroupContent
            class="max-h-[min(40vh,320px)] overflow-y-auto overscroll-contain px-0"
        >
            <SidebarMenu>
                <SidebarMenuItem v-for="p in filteredProjects" :key="p.id">
                    <SidebarMenuButton
                        as-child
                        :is-active="isMacroPage && selectedProjectId === p.id"
                        :tooltip="p.name"
                    >
                        <Link
                            :href="hrefProject(p.id)"
                            class="flex w-full min-w-0 items-center gap-2"
                        >
                            <Folder class="shrink-0 opacity-80" aria-hidden="true" />
                            <span class="min-w-0 flex-1 truncate text-left">{{
                                p.name
                            }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem v-if="emptyPortfolio">
                    <p
                        class="px-2 py-2 text-center text-[11px] leading-snug text-muted-foreground"
                    >
                        No hay proyectos visibles con tu perfil.
                    </p>
                </SidebarMenuItem>
                <SidebarMenuItem v-else-if="filteredProjects.length === 0">
                    <p
                        class="px-2 py-2 text-center text-[11px] text-muted-foreground"
                    >
                        Ningún resultado.
                    </p>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
