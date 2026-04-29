<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ProjectGanttChart from '@/components/workflow/ProjectGanttChart.vue';
import ProyectoWorkspaceTabs from '@/components/workflow/ProyectoWorkspaceTabs.vue';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { cronograma } from '@/routes/proyecto';
type GanttRow = {
    id: number;
    name: string;
    starts_at: string | null;
    ends_at: string | null;
    status: string;
    project_id: number;
    project_name: string;
    project_code: string | null;
    task_title: string;
};

type Proj = { id: number; name: string; code: string | null };
type ViewMode = 'Day' | 'Week' | 'Month' | 'Year';

const props = defineProps<{
    project: Proj | null;
    projects: Proj[];
    ganttProjects: GanttRow[];
}>();

const projectId = ref(props.project?.id ?? '');
const ganttRef = ref<InstanceType<typeof ProjectGanttChart> | null>(null);
const viewMode = ref<ViewMode>('Week');
const selectedStatuses = ref<string[]>([]);
const viewModeLabel: Record<ViewMode, string> = {
    Day: 'Día',
    Week: 'Semana',
    Month: 'Mes',
    Year: 'Año',
};

const statusLabels: Record<string, string> = {
    pendiente: 'Pendiente',
    en_progreso: 'En progreso',
    completada: 'Completada',
    bloqueada: 'Bloqueada',
};

const statusOrder = ['pendiente', 'en_progreso', 'bloqueada', 'completada'];

const availableStatuses = computed(() => {
    const set = new Set(props.ganttProjects.map((t) => t.status).filter(Boolean));
    return statusOrder.filter((s) => set.has(s));
});

watch(
    availableStatuses,
    (list) => {
        if (selectedStatuses.value.length === 0) {
            selectedStatuses.value = [...list];
            return;
        }
        selectedStatuses.value = selectedStatuses.value.filter((s) => list.includes(s));
    },
    { immediate: true },
);

const filteredGanttProjects = computed(() =>
    props.ganttProjects.filter((row) =>
        selectedStatuses.value.length === 0
            ? true
            : selectedStatuses.value.includes(row.status),
    ),
);

const ganttMetrics = computed(() => {
    const total = filteredGanttProjects.value.length;
    const completed = filteredGanttProjects.value.filter((t) => t.status === 'completada').length;
    const inProgress = filteredGanttProjects.value.filter((t) => t.status === 'en_progreso').length;
    const blocked = filteredGanttProjects.value.filter((t) => t.status === 'bloqueada').length;
    const completionRate = total > 0 ? Math.round((completed / total) * 100) : 0;
    return { total, completed, inProgress, blocked, completionRate };
});

watch(
    () => props.project?.id,
    (id) => {
        projectId.value = id ?? '';
    },
);

function changeProject() {
    if (!projectId.value) {
        return;
    }
    router.get(
        cronograma.url({ query: { project_id: Number(projectId.value) } }),
    );
}

function toggleStatus(status: string): void {
    if (selectedStatuses.value.includes(status)) {
        selectedStatuses.value = selectedStatuses.value.filter((s) => s !== status);
        return;
    }
    selectedStatuses.value = [...selectedStatuses.value, status];
}

function setAllStatuses(): void {
    selectedStatuses.value = [...availableStatuses.value];
}

function clearStatuses(): void {
    selectedStatuses.value = [];
}

function goToday(): void {
    ganttRef.value?.scrollToToday();
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Cronograma del proyecto', href: cronograma() },
        ],
    },
});
</script>

<template>
    <Head title="Cronograma — proyecto" />

    <WorkflowSection
        context-label="Jefe de proyecto — ejecución"
        title="Cronograma (Gantt) del proyecto"
        description="Cronograma de tareas del proyecto (fechas a partir de la fecha límite o de la alta de cada tarea)."
    >
        <div
            v-if="!project"
            class="rounded-lg border border-dashed border-[#003366]/30 p-8 text-center text-[#666]"
        >
            No hay proyectos accesibles.
        </div>
        <template v-else>
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <label class="text-sm font-medium text-[#003366]">Proyecto</label>
                <select
                    v-model="projectId"
                    class="rounded-md border border-input px-3 py-1.5 text-sm"
                    @change="changeProject"
                >
                    <option
                        v-for="p in projects"
                        :key="p.id"
                        :value="p.id"
                    >
                        {{ p.name }}
                    </option>
                </select>
            </div>

            <ProyectoWorkspaceTabs
                active="cronograma"
                :project="project"
                :projects="projects"
            />

            <div class="mb-4 rounded-xl border border-[#003366]/12 bg-white p-3 shadow-sm">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-[#003366]">
                            Escala de tiempo
                        </span>
                        <div class="inline-flex rounded-lg border border-[#003366]/20 bg-[#f8fafc] p-1">
                            <button
                                v-for="mode in (['Day', 'Week', 'Month', 'Year'] as ViewMode[])"
                                :key="mode"
                                type="button"
                                class="rounded-md px-2.5 py-1 text-xs font-medium transition"
                                :class="
                                    viewMode === mode
                                        ? 'bg-[#003366] text-white shadow-sm'
                                        : 'text-slate-700 hover:bg-[#e2e8f0]'
                                "
                                @click="viewMode = mode"
                            >
                                {{ viewModeLabel[mode] }}
                            </button>
                        </div>
                        <Button variant="outline" size="sm" class="h-8" @click="goToday">
                            Hoy
                        </Button>
                    </div>
                    <div class="text-xs text-slate-600">
                        Estilo Monday: timeline + filtros + lectura por estado
                    </div>
                </div>

                <div class="mb-3 flex flex-wrap gap-2">
                    <button
                        v-for="status in availableStatuses"
                        :key="status"
                        type="button"
                        class="rounded-full border px-2.5 py-1 text-xs font-medium transition"
                        :class="
                            selectedStatuses.includes(status)
                                ? 'border-[#003366] bg-[#003366] text-white'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                        "
                        @click="toggleStatus(status)"
                    >
                        {{ statusLabels[status] ?? status }}
                    </button>

                    <button
                        type="button"
                        class="rounded-full border border-slate-300 px-2.5 py-1 text-xs text-slate-700 hover:bg-slate-50"
                        @click="setAllStatuses"
                    >
                        Todos
                    </button>
                    <button
                        type="button"
                        class="rounded-full border border-slate-300 px-2.5 py-1 text-xs text-slate-700 hover:bg-slate-50"
                        @click="clearStatuses"
                    >
                        Ninguno
                    </button>
                </div>

                <div class="grid gap-2 text-xs text-slate-700 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">Tareas visibles</div>
                        <div class="text-base font-semibold text-slate-900">{{ ganttMetrics.total }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">Completadas</div>
                        <div class="text-base font-semibold text-slate-900">{{ ganttMetrics.completed }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">En progreso</div>
                        <div class="text-base font-semibold text-slate-900">{{ ganttMetrics.inProgress }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">Bloqueadas</div>
                        <div class="text-base font-semibold text-slate-900">{{ ganttMetrics.blocked }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">Avance</div>
                        <div class="text-base font-semibold text-slate-900">{{ ganttMetrics.completionRate }}%</div>
                    </div>
                </div>
            </div>

            <ProjectGanttChart
                ref="ganttRef"
                class="mb-6"
                :projects="filteredGanttProjects"
                :view-mode="viewMode"
                empty-hint="No hay tareas en este proyecto. La vista detallada por tarea está en Lista de tareas."
            />
        </template>
    </WorkflowSection>
</template>
