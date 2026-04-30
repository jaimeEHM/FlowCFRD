<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { formatDateChile } from '@/lib/dateFormat';
import { dashboard } from '@/routes';
import { proyectos, tableroMacro } from '@/routes/pmo';

type Project = {
    id: number;
    name: string;
    code: string | null;
    status: string;
    carta_inicio_at: string | null;
    starts_at: string | null;
    ends_at: string | null;
    updated_at: string;
    tasks_total: number;
    tasks_abiertas: number;
    members_count: number;
    has_acta: boolean;
    jefe_proyecto: { name: string; email: string } | null;
    created_by: { name: string } | null;
};

type PaginatedProjects = {
    data: Project[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    projects: PaginatedProjects;
    statuses: string[];
    filters: {
        q: string;
        status: string | null;
        sort: string;
        dir: 'asc' | 'desc';
    };
}>();

const filterForm = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    sort: props.filters.sort ?? 'updated_at',
    dir: props.filters.dir ?? 'desc',
});

const statusLabel = (status: string): string =>
    ({
        borrador: 'Borrador',
        activo: 'Activo',
        en_pausa: 'En pausa',
        cerrado: 'Cerrado',
    })[status] ?? status;

const statusBadgeClass = (status: string): string =>
    ({
        borrador: 'bg-slate-100 text-slate-700',
        activo: 'bg-emerald-100 text-emerald-700',
        en_pausa: 'bg-amber-100 text-amber-800',
        cerrado: 'bg-zinc-200 text-zinc-700',
    })[status] ?? 'bg-slate-100 text-slate-700';

const sortOptions = [
    { value: 'updated_at', label: 'Última actualización' },
    { value: 'name', label: 'Nombre' },
    { value: 'status', label: 'Estado' },
    { value: 'carta_inicio_at', label: 'Carta de inicio' },
];

const summaryText = computed(() => {
    if (!props.projects.total) {
        return 'Sin resultados';
    }
    return `Mostrando ${props.projects.from ?? 0}-${props.projects.to ?? 0} de ${props.projects.total} proyectos`;
});

function applyFilters(page = 1): void {
    router.get(
        proyectos(),
        {
            q: filterForm.q || undefined,
            status: filterForm.status || undefined,
            sort: filterForm.sort,
            dir: filterForm.dir,
            page,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    filterForm.q = '';
    filterForm.status = '';
    filterForm.sort = 'updated_at';
    filterForm.dir = 'desc';
    applyFilters(1);
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Proyectos e iniciativas', href: proyectos() },
        ],
    },
});
</script>

<template>
    <Head title="Proyectos" />

    <WorkflowSection
        context-label="PMO — visión macro y seguimiento"
        title="Proyectos e iniciativas"
        description="Cartera con filtros, orden, avance y accesos rápidos para operación PMO."
    >
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <Button
                as-child
                class="bg-[#003366] hover:bg-[#003366]/90"
            >
                <Link :href="tableroMacro()"> Abrir tablero macro </Link>
            </Button>
            <p class="text-sm text-[#666]">
                Allí: clic en el nombre del proyecto, FAB + para crear.
            </p>
        </div>

        <div class="mb-4 rounded-lg border border-[#003366]/15 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <div class="space-y-1 xl:col-span-2">
                    <Label for="filter-q">Buscar</Label>
                    <Input
                        id="filter-q"
                        v-model="filterForm.q"
                        placeholder="Nombre, código o jefe de proyecto"
                        @keyup.enter="applyFilters(1)"
                    />
                </div>
                <div class="space-y-1">
                    <Label for="filter-status">Estado</Label>
                    <select
                        id="filter-status"
                        v-model="filterForm.status"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="">Todos</option>
                        <option v-for="status in statuses" :key="status" :value="status">
                            {{ statusLabel(status) }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1">
                    <Label for="filter-sort">Ordenar por</Label>
                    <select
                        id="filter-sort"
                        v-model="filterForm.sort"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
                <div class="space-y-1">
                    <Label for="filter-dir">Dirección</Label>
                    <select
                        id="filter-dir"
                        v-model="filterForm.dir"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="desc">Descendente</option>
                        <option value="asc">Ascendente</option>
                    </select>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <p class="text-xs text-slate-600">{{ summaryText }}</p>
                <div class="flex gap-2">
                    <Button type="button" variant="outline" @click="clearFilters">Limpiar</Button>
                    <Button type="button" class="bg-[#003366] hover:bg-[#003366]/90" @click="applyFilters(1)">
                        Aplicar filtros
                    </Button>
                </div>
            </div>
        </div>

        <div
            class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white shadow-sm"
        >
            <table class="w-full min-w-[1120px] text-left text-sm">
                <thead
                    class="border-b border-[#003366]/15 bg-[#f8fafc] text-xs font-semibold uppercase tracking-wide text-[#003366]"
                >
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Inicio</th>
                        <th class="px-4 py-3">Fin</th>
                        <th class="px-4 py-3">Carta inicio</th>
                        <th class="px-4 py-3">Jefe</th>
                        <th class="px-4 py-3 text-right">Avance</th>
                        <th class="px-4 py-3 text-right">Equipo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#003366]/10">
                    <tr v-for="p in projects.data" :key="p.id">
                        <td class="px-4 py-3 font-medium text-[#333]">
                            {{ p.name }}
                        </td>
                        <td class="px-4 py-3 text-[#666]">
                            {{ p.code ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="statusBadgeClass(p.status)">
                                {{ statusLabel(p.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-[#666]">
                            {{ formatDateChile(p.starts_at) }}
                        </td>
                        <td class="px-4 py-3 text-[#666]">
                            {{ formatDateChile(p.ends_at) }}
                        </td>
                        <td class="px-4 py-3 text-[#666]">
                            {{ formatDateChile(p.carta_inicio_at) }}
                        </td>
                        <td class="px-4 py-3 text-[#666]">
                            {{ p.jefe_proyecto?.name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-[#666]">
                            {{ p.tasks_total - p.tasks_abiertas }}/{{ p.tasks_total }}
                        </td>
                        <td class="px-4 py-3 text-right text-[#666]">
                            {{ p.members_count }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <Button as-child variant="outline" size="sm">
                                    <Link :href="`/pmo/tablero-macro?project_id=${p.id}&segment=lista`">Ver</Link>
                                </Button>
                                <Button
                                    v-if="p.has_acta"
                                    as-child
                                    variant="outline"
                                    size="sm"
                                >
                                    <a :href="`/pmo/proyectos/${p.id}/acta-constitucion`">Acta</a>
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="projects.data.length === 0">
                        <td
                            colspan="10"
                            class="px-4 py-8 text-center text-[#666]"
                        >
                            Sin proyectos para los filtros actuales.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2">
            <Button
                type="button"
                variant="outline"
                :disabled="!projects.prev_page_url"
                @click="applyFilters(projects.current_page - 1)"
            >
                Anterior
            </Button>
            <span class="text-sm text-slate-600">
                Página {{ projects.current_page }} de {{ projects.last_page }}
            </span>
            <Button
                type="button"
                variant="outline"
                :disabled="!projects.next_page_url"
                @click="applyFilters(projects.current_page + 1)"
            >
                Siguiente
            </Button>
        </div>
    </WorkflowSection>
</template>
