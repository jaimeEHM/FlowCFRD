<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowDownWideNarrow,
    FileText,
    Pencil,
    Plus,
    Search,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import KpiStatusCharts from '@/components/workflow/KpiStatusCharts.vue';
import PmoMacroBoardShell from '@/components/workflow/PmoMacroBoardShell.vue';
import PmoMacroProjectContext from '@/components/workflow/PmoMacroProjectContext.vue';
import ProjectGanttChart from '@/components/workflow/ProjectGanttChart.vue';
import ProyectoKanbanBoard from '@/components/workflow/ProyectoKanbanBoard.vue';
import PortfolioWorkloadPanel from '@/components/workflow/PortfolioWorkloadPanel.vue';
import type { WorkloadPayload } from '@/components/workflow/PortfolioWorkloadPanel.vue';
import ProyectoTaskListPanel from '@/components/workflow/ProyectoTaskListPanel.vue';
import type {
    TaskListGroup,
    TaskListPerson,
    TaskListRow,
} from '@/types/proyectoTaskList';
import InputError from '@/components/InputError.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateChile } from '@/lib/dateFormat';
import { update as patchProyecto, store as postProyecto } from '@/routes/pmo/proyectos';
import { store as postTarea } from '@/routes/proyecto/tareas';
import { dashboard } from '@/routes';
import { tableroMacro } from '@/routes/pmo';

type JefeOption = { id: number; name: string };
type MemberOption = { id: number; name: string };

type Project = {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    status: string;
    carta_inicio_at: string | null;
    starts_at: string | null;
    ends_at: string | null;
    jefe_proyecto: { id: number; name: string } | null;
    member_ids: number[];
    acta_constitucion: {
        download_url: string;
        original_name: string | null;
    } | null;
    tasks_abiertas: number;
    tasks_total: number;
};

type Segment = 'cartera' | 'kpi' | 'gantt' | 'calendario' | 'lista' | 'kanban' | 'carga';
type GanttViewMode = 'Day' | 'Week' | 'Month' | 'Year';
type CalendarView = 'day' | 'week' | 'month';
type TaskDay = {
    id: number;
    title: string;
    status: string;
    project_id: number;
    project_name: string | null;
    assignee: { name: string } | null;
};

type KanbanBoardPayload = {
    project: { id: number; name: string; code: string | null };
    groups: unknown[];
};

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

type GanttTaskGroupOption = {
    id: number;
    name: string;
};

const props = defineProps<{
    activeSegment: Segment;
    projects: Project[];
    /** Proyecto enfocado (query `project_id`); la barra lateral y las pestañas lo conservan. */
    selectedProjectId: number | null;
    /** Payload completo si `selectedProjectId` es válido; si no, null. */
    selectedProject: Project | null;
    statuses: string[];
    jefeOptions: JefeOption[];
    memberOptions: MemberOption[];
    ganttProjects: GanttRow[];
    visibleTabSegments: {
        kpi: boolean;
        gantt: boolean;
        calendario: boolean;
        lista: boolean;
        kanban: boolean;
        carga: boolean;
    };
    listaTaskGroups: TaskListGroup[];
    listaTasks: TaskListRow[];
    listaPeopleOptions: TaskListPerson[];
    /** true = lista sin `project_id`: todas las tareas de proyectos visibles. */
    listaPortfolioMode: boolean;
    kanbanBoards: KanbanBoardPayload[];
    kanbanPeopleOptions: TaskListPerson[];
    kanbanPortfolioMode: boolean;
    portfolioWorkload: WorkloadPayload;
    workloadThresholds: {
        tasks_per_day: number;
        alert_days: number;
        danger_days: number;
        overload_days: number;
    };
    ganttTaskGroupsByProject: Record<number, GanttTaskGroupOption[]>;
    calendar: {
        view: CalendarView;
        date: string;
        start_date: string;
        end_date: string;
        label: string;
        tasks_by_day: Record<string, TaskDay[]>;
    };
    taskStatuses: string[];
    canEditCarteraFull: boolean;
    usuarios_total: number;
    proyectos_total: number;
    tareas_total: number;
    tareas_abiertas: number;
    tareas_urgentes_pendientes: number;
    projects_by_status: Record<string, number>;
    tasks_by_status: Record<string, number>;
    tasks_abiertas_por_responsable: Record<string, number>;
}>();

const pageTitle = computed(() => {
    if (props.activeSegment === 'kpi') {
        return 'Indicadores — Tablero macro PMO';
    }
    if (props.activeSegment === 'gantt') {
        return 'Cronograma — Tablero macro PMO';
    }
    if (props.activeSegment === 'calendario') {
        return 'Calendario — Tablero macro PMO';
    }
    if (props.activeSegment === 'lista') {
        return 'Lista de tareas — Tablero macro PMO';
    }
    if (props.activeSegment === 'kanban') {
        return 'Kanban — Tablero macro PMO';
    }
    if (props.activeSegment === 'carga') {
        return 'Carga por persona — Tablero macro PMO';
    }
    return 'Tablero macro PMO';
});

const boardSubtitle = computed(() => {
    if (props.activeSegment === 'kpi') {
        return props.selectedProject !== null
            ? `Indicadores del proyecto «${props.selectedProject.name}»: tareas y distribución en ese alcance.`
            : 'Vista «Gráfico / KPI»: totales globales y distribución (Chart.js).';
    }
    if (props.activeSegment === 'gantt') {
        return 'Vista temporal: barras por tarea (fecha límite o alta).';
    }
    if (props.activeSegment === 'calendario') {
        return props.selectedProject !== null
            ? `Calendario mensual de tareas con vencimiento en «${props.selectedProject.name}».`
            : 'Calendario mensual de vencimientos para toda la cartera visible.';
    }
    if (props.activeSegment === 'lista') {
        return props.listaPortfolioMode
            ? 'Lista de todas las tareas de los proyectos visibles en la cartera.'
            : 'Lista de tareas del proyecto seleccionado: mismos datos que Proyecto → Lista de tareas, integrada en la cartera PMO.';
    }
    if (props.activeSegment === 'kanban') {
        return props.kanbanPortfolioMode
            ? 'Tableros Kanban de todos los proyectos visibles (un bloque por proyecto).'
            : 'Kanban del proyecto seleccionado; mismo comportamiento que Proyecto → Kanban.';
    }
    if (props.activeSegment === 'carga') {
        return props.selectedProject === null
            ? 'Vista de carga de trabajo: tareas abiertas por responsable, repartidas por proyecto (cartera completa).'
            : `Carga del equipo en «${props.selectedProject.name}»: desglose por estado de tarea.`;
    }
    if (props.selectedProject !== null) {
        return 'Proyecto seleccionado: resumen PMO y accesos al Kanban, tareas, cronograma, calendario y minutas.';
    }
    return 'Cartera completa: una fila por proyecto; busca, filtra por estado y ordena. Pulsa un nombre o la barra lateral para enfocar un proyecto.';
});

const projectFilter = ref('');
/** Vacío = todos los estados. */
const statusFilter = ref<string>('');
type TableSortKey = 'name_asc' | 'name_desc' | 'progress_desc' | 'progress_asc';
const sortKey = ref<TableSortKey>('name_asc');
const ganttRef = ref<InstanceType<typeof ProjectGanttChart> | null>(null);
const ganttViewMode = ref<GanttViewMode>('Week');
const ganttSelectedStatuses = ref<string[]>([]);
const ganttTaskModalOpen = ref(false);
const quickGanttTaskOpen = ref(false);
const selectedGanttTask = ref<GanttRow | null>(null);
const quickTaskForm = useForm({
    project_id: '' as string,
    task_group_id: '' as string,
    title: '',
});
const ganttViewModeLabel: Record<GanttViewMode, string> = {
    Day: 'Día',
    Week: 'Semana',
    Month: 'Mes',
    Year: 'Año',
};

function clearTableFilters(): void {
    projectFilter.value = '';
    statusFilter.value = '';
    sortKey.value = 'name_asc';
}

const filteredProjects = computed(() => {
    let list = [...props.projects];

    const q = projectFilter.value.trim().toLowerCase();
    if (q !== '') {
        list = list.filter((p) => {
            const name = p.name.toLowerCase();
            const code = (p.code ?? '').toLowerCase();
            return name.includes(q) || code.includes(q);
        });
    }

    if (statusFilter.value !== '') {
        list = list.filter((p) => p.status === statusFilter.value);
    }

    list.sort((a, b) => {
        switch (sortKey.value) {
            case 'name_asc':
                return a.name.localeCompare(b.name, 'es');
            case 'name_desc':
                return b.name.localeCompare(a.name, 'es');
            case 'progress_desc':
                return progressPercent(b) - progressPercent(a);
            case 'progress_asc':
                return progressPercent(a) - progressPercent(b);
            default:
                return 0;
        }
    });

    return list;
});

const ganttStatusLabels: Record<string, string> = {
    pendiente: 'Pendiente',
    en_progreso: 'En progreso',
    completada: 'Completada',
    bloqueada: 'Bloqueada',
};

const ganttStatusOrder = ['pendiente', 'en_progreso', 'bloqueada', 'completada'];

const ganttAvailableStatuses = computed(() => {
    const set = new Set(props.ganttProjects.map((t) => t.status).filter(Boolean));
    return ganttStatusOrder.filter((s) => set.has(s));
});

watch(
    ganttAvailableStatuses,
    (list) => {
        if (ganttSelectedStatuses.value.length === 0) {
            ganttSelectedStatuses.value = [...list];
            return;
        }
        ganttSelectedStatuses.value = ganttSelectedStatuses.value.filter((s) => list.includes(s));
    },
    { immediate: true },
);

const filteredGanttProjects = computed(() =>
    props.ganttProjects.filter((row) =>
        ganttSelectedStatuses.value.length === 0
            ? true
            : ganttSelectedStatuses.value.includes(row.status),
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

const detailOpen = ref(false);
const createOpen = ref(false);
const detailProject = ref<Project | null>(null);
const detailFileInputKey = ref(0);
const createFileInputKey = ref(0);

const detailForm = useForm({
    name: '',
    code: '',
    description: '',
    carta_inicio_at: '',
    starts_at: '',
    ends_at: '',
    status: 'borrador',
    jefe_proyecto_id: '' as string,
    member_ids: [] as number[],
    acta_constitucion: null as File | null,
    remove_acta_constitucion: false,
});

const createForm = useForm({
    name: '',
    code: '',
    description: '',
    carta_inicio_at: '',
    starts_at: '',
    ends_at: '',
    status: props.statuses[0] ?? 'borrador',
    jefe_proyecto_id: '' as string,
    member_ids: [] as number[],
    acta_constitucion: null as File | null,
});

/** Acento visual: borde izquierdo + tono de fila */
const statusAccent: Record<
    string,
    { border: string; row: string; pill: string }
> = {
    borrador: {
        border: 'border-l-slate-400',
        row: 'bg-slate-50/50',
        pill: 'bg-slate-200 text-slate-800',
    },
    activo: {
        border: 'border-l-emerald-500',
        row: 'bg-emerald-50/40',
        pill: 'bg-emerald-100 text-emerald-900',
    },
    en_pausa: {
        border: 'border-l-amber-500',
        row: 'bg-amber-50/35',
        pill: 'bg-amber-100 text-amber-950',
    },
    cerrado: {
        border: 'border-l-slate-500',
        row: 'bg-slate-100/50',
        pill: 'bg-slate-300 text-slate-900',
    },
};

function accent(status: string) {
    return statusAccent[status] ?? statusAccent.borrador;
}

function statusLabel(status: string): string {
    return status.replace(/_/g, ' ');
}

function progressPercent(p: Project): number {
    if (p.tasks_total <= 0) {
        return 0;
    }
    const hechas = p.tasks_total - p.tasks_abiertas;
    return Math.round((hechas / p.tasks_total) * 100);
}

/** Al menos una tarea y ninguna abierta (coherente con tasks_abiertas del backend). */
function allTasksDone(p: Project): boolean {
    return p.tasks_total > 0 && p.tasks_abiertas === 0;
}

function patchProjectStatus(project: Project, ev: Event): void {
    if (!props.canEditCarteraFull) {
        return;
    }
    const el = ev.target as HTMLSelectElement;
    const status = el.value;
    if (status === project.status) {
        return;
    }
    router.patch(patchProyecto.url(project.id), { status }, { preserveScroll: true });
}

function goSelectProject(p: Project): void {
    router.get(
        tableroMacro.url({ query: { project_id: p.id } }),
        {},
        { preserveScroll: true },
    );
}

/** En Gantt la fila puede existir aunque el objeto no sea el mismo tipo que en la cartera. */
function goSelectProjectById(projectId: number): void {
    const p = props.projects.find((x) => x.id === projectId);
    if (p !== undefined) {
        goSelectProject(p);
    } else {
        router.get(
            tableroMacro.url({ query: { project_id: projectId } }),
            {},
            { preserveScroll: true },
        );
    }
}

function goClearProjectSelection(): void {
    router.get(tableroMacro.url(), {}, { preserveScroll: true });
}

function toggleGanttStatus(status: string): void {
    if (ganttSelectedStatuses.value.includes(status)) {
        ganttSelectedStatuses.value = ganttSelectedStatuses.value.filter((s) => s !== status);
        return;
    }
    ganttSelectedStatuses.value = [...ganttSelectedStatuses.value, status];
}

function setAllGanttStatuses(): void {
    ganttSelectedStatuses.value = [...ganttAvailableStatuses.value];
}

function clearGanttStatuses(): void {
    ganttSelectedStatuses.value = [];
}

function goGanttToday(): void {
    ganttRef.value?.scrollToToday();
}

function onGanttTaskClick(payload: {
    id: number;
    project_id?: number;
}): void {
    const row = props.ganttProjects.find((task) => task.id === payload.id);
    if (!row) {
        return;
    }
    selectedGanttTask.value = row;
    ganttTaskModalOpen.value = true;
}

function goToKanbanTask(task: GanttRow): void {
    router.get('/proyecto/kanban', {
        project_id: task.project_id,
        focus_task_id: task.id,
    });
}

function openQuickTaskModal(projectId?: number): void {
    quickTaskForm.reset();
    quickTaskForm.clearErrors();

    const resolvedProjectId =
        projectId ??
        props.selectedProjectId ??
        props.projects[0]?.id ??
        null;

    quickTaskForm.project_id = resolvedProjectId !== null ? String(resolvedProjectId) : '';
    const options = quickTaskGroupOptions.value;
    quickTaskForm.task_group_id = options[0] ? String(options[0].id) : '';

    quickGanttTaskOpen.value = true;
}

const quickTaskGroupOptions = computed<GanttTaskGroupOption[]>(() => {
    const pid = Number(quickTaskForm.project_id);
    if (!Number.isFinite(pid) || pid <= 0) {
        return [];
    }
    return props.ganttTaskGroupsByProject[pid] ?? [];
});

watch(
    () => quickTaskForm.project_id,
    () => {
        const options = quickTaskGroupOptions.value;
        if (!options.some((g) => String(g.id) === quickTaskForm.task_group_id)) {
            quickTaskForm.task_group_id = options[0] ? String(options[0].id) : '';
        }
    },
);

function submitQuickGanttTask(): void {
    if (!quickTaskForm.project_id || !quickTaskForm.task_group_id || !quickTaskForm.title.trim()) {
        return;
    }

    quickTaskForm.transform((data) => ({
        project_id: Number(data.project_id),
        task_group_id: Number(data.task_group_id),
        title: data.title.trim(),
    })).post(postTarea.url(), {
        preserveScroll: true,
        onSuccess: () => {
            quickGanttTaskOpen.value = false;
            quickTaskForm.reset();
        },
    });
}

function openProjectDetail(p: Project): void {
    if (!props.canEditCarteraFull) {
        return;
    }
    detailProject.value = p;
    detailForm.name = p.name;
    detailForm.code = p.code ?? '';
    detailForm.description = p.description ?? '';
    detailForm.carta_inicio_at = p.carta_inicio_at ?? '';
    detailForm.starts_at = p.starts_at ?? '';
    detailForm.ends_at = p.ends_at ?? '';
    detailForm.status = p.status;
    detailForm.jefe_proyecto_id =
        p.jefe_proyecto !== null ? String(p.jefe_proyecto.id) : '';
    detailForm.member_ids = [...(p.member_ids ?? [])];
    detailForm.acta_constitucion = null;
    detailForm.remove_acta_constitucion = false;
    detailForm.clearErrors();
    detailFileInputKey.value += 1;
    detailOpen.value = true;
}

function closeProjectDetail(): void {
    detailOpen.value = false;
    detailProject.value = null;
}

function submitProjectDetail(): void {
    if (!detailProject.value) {
        return;
    }
    detailForm.patch(patchProyecto.url(detailProject.value.id), {
        preserveScroll: true,
        onSuccess: () => closeProjectDetail(),
    });
}

function openCreateModal(): void {
    createForm.reset();
    createForm.status = props.statuses[0] ?? 'borrador';
    createForm.member_ids = [];
    createForm.acta_constitucion = null;
    createForm.clearErrors();
    createFileInputKey.value += 1;
    createOpen.value = true;
}

function onDetailActaFile(ev: Event): void {
    const el = ev.target as HTMLInputElement;
    const f = el.files?.[0];
    detailForm.acta_constitucion = f ?? null;
    if (f) {
        detailForm.remove_acta_constitucion = false;
    }
}

function onCreateActaFile(ev: Event): void {
    const el = ev.target as HTMLInputElement;
    createForm.acta_constitucion = el.files?.[0] ?? null;
}

function submitCreate(): void {
    createForm.post(postProyecto.url(), {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
        },
    });
}

function toggleMemberSelection(target: 'detail' | 'create', userId: number, checked: boolean): void {
    const form = target === 'detail' ? detailForm : createForm;
    const current = new Set(form.member_ids);
    if (checked) {
        current.add(userId);
    } else {
        current.delete(userId);
    }
    form.member_ids = Array.from(current);
}

const inertiaPage = usePage();
const calendarView = ref<CalendarView>(props.calendar.view);
const calendarAnchorDate = ref(props.calendar.date);

function parseIsoLocalDate(iso: string): Date {
    const [y, m, d] = iso.split('-').map((v) => Number(v));
    return new Date(y, (m || 1) - 1, d || 1);
}

function formatIsoLocalDate(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

watch(
    () => [props.calendar.view, props.calendar.date],
    ([v, d]) => {
        calendarView.value = v;
        calendarAnchorDate.value = d;
    },
);

function shiftCalendarPeriod(delta: number): void {
    const base = parseIsoLocalDate(calendarAnchorDate.value);
    if (calendarView.value === 'day') {
        base.setDate(base.getDate() + delta);
    } else if (calendarView.value === 'week') {
        base.setDate(base.getDate() + (7 * delta));
    } else {
        base.setMonth(base.getMonth() + delta);
    }

    const q: Record<string, number | string> = {
        segment: 'calendario',
        view: calendarView.value,
        date: formatIsoLocalDate(base),
    };
    if (props.selectedProjectId !== null) {
        q.project_id = props.selectedProjectId;
    }

    router.get(tableroMacro.url({ query: q }), {}, { preserveScroll: true });
}

function openTaskInKanban(task: TaskDay): void {
    router.get('/proyecto/kanban', {
        project_id: task.project_id,
        focus_task_id: task.id,
    });
}

const calendarCells = computed(() => {
    const start = parseIsoLocalDate(props.calendar.start_date);
    const end = parseIsoLocalDate(props.calendar.end_date);
    const out: { day: number | null; key: string | null }[] = [];
    const isBusinessDay = (d: Date) => {
        const wd = d.getDay();
        return wd >= 1 && wd <= 5;
    };
    const mondayIndex = (d: Date) => (d.getDay() + 6) % 7;

    if (props.calendar.view === 'month') {
        const firstBusiness = new Date(start);
        while (!isBusinessDay(firstBusiness) && firstBusiness <= end) {
            firstBusiness.setDate(firstBusiness.getDate() + 1);
        }
        const pad = Math.min(mondayIndex(firstBusiness), 5);
        for (let i = 0; i < pad; i++) {
            out.push({ day: null, key: null });
        }
    }
    for (let dt = new Date(start); dt <= end; dt.setDate(dt.getDate() + 1)) {
        if (!isBusinessDay(dt)) {
            continue;
        }
        const key = `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`;
        out.push({ day: dt.getDate(), key });
    }

    if (props.calendar.view === 'month' && out.length % 5 !== 0) {
        const fill = 5 - (out.length % 5);
        for (let i = 0; i < fill; i++) {
            out.push({ day: null, key: null });
        }
    }

    return out;
});

const calendarWeekdayHeaders = computed(() => {
    if (props.calendar.view === 'day') {
        return ['Día'];
    }
    return ['Lun', 'Mar', 'Mié', 'Jue', 'Vie'];
});

const calendarGridClass = computed(() =>
    props.calendar.view === 'day' ? 'grid-cols-1' : 'grid-cols-5',
);

/** Abre el modal de alta desde el enlace «Nuevo proyecto» del sidebar (`?crear=1`). */
watch(
    () => inertiaPage.url,
    () => {
        try {
            const url = new URL(inertiaPage.url, window.location.origin);
            if (url.searchParams.get('crear') !== '1') {
                return;
            }
            openCreateModal();
            url.searchParams.delete('crear');
            router.visit(`${url.pathname}${url.search}`, {
                replace: true,
                preserveState: true,
                preserveScroll: true,
            });
        } catch {
            /* ignore */
        }
    },
    { immediate: true },
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Tablero macro', href: tableroMacro.url() },
        ],
    },
});
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex min-w-0 flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="space-y-4">
        <PmoMacroBoardShell
            :active="activeSegment"
            :visible-tab-segments="props.visibleTabSegments"
            :board-subtitle="boardSubtitle"
            :focused-project-id="props.selectedProjectId"
        >
            <template
                v-if="activeSegment === 'cartera' && props.selectedProject === null"
                #toolbar
            >
                <div
                    class="flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between"
                >
                    <div
                        class="flex w-full min-w-0 flex-col gap-3 lg:flex-row lg:items-end lg:gap-4"
                    >
                        <div
                            class="relative flex min-w-[12rem] max-w-lg flex-1 items-center"
                        >
                            <Search
                                class="pointer-events-none absolute left-2.5 h-4 w-4 text-slate-400"
                                aria-hidden="true"
                            />
                            <Input
                                v-model="projectFilter"
                                type="search"
                                autocomplete="off"
                                placeholder="Buscar por nombre o código…"
                                class="h-9 border-slate-200 pl-9 text-sm"
                            />
                        </div>
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="flex min-w-[9rem] flex-col gap-1">
                                <Label
                                    class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"
                                >
                                    Estado
                                </Label>
                                <select
                                    v-model="statusFilter"
                                    class="h-9 rounded-md border border-slate-200 bg-white px-2 text-sm text-slate-800 shadow-sm focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                                >
                                    <option value="">Todos</option>
                                    <option
                                        v-for="s in props.statuses"
                                        :key="s"
                                        :value="s"
                                    >
                                        {{ statusLabel(s) }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex min-w-[11rem] flex-col gap-1">
                                <Label
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500"
                                >
                                    <ArrowDownWideNarrow
                                        class="h-3 w-3"
                                        aria-hidden="true"
                                    />
                                    Orden
                                </Label>
                                <select
                                    v-model="sortKey"
                                    class="h-9 rounded-md border border-slate-200 bg-white px-2 text-sm text-slate-800 shadow-sm focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                                >
                                    <option value="name_asc">
                                        Nombre (A → Z)
                                    </option>
                                    <option value="name_desc">
                                        Nombre (Z → A)
                                    </option>
                                    <option value="progress_desc">
                                        Avance tareas (mayor → menor)
                                    </option>
                                    <option value="progress_asc">
                                        Avance tareas (menor → mayor)
                                    </option>
                                </select>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-9 border-slate-200 text-xs"
                                @click="clearTableFilters"
                            >
                                Limpiar filtros
                            </Button>
                        </div>
                    </div>
                    <span
                        class="shrink-0 text-right text-xs tabular-nums text-slate-500"
                    >
                        {{ filteredProjects.length }}/{{ props.projects.length }}
                        visibles
                    </span>
                </div>
            </template>
        </PmoMacroBoardShell>

        <div
            v-if="activeSegment === 'cartera'"
            class="space-y-4"
        >
            <PmoMacroProjectContext
                v-if="props.selectedProject !== null"
                :project="props.selectedProject"
                :can-edit-pmo="props.canEditCarteraFull"
                @edit-pmo="openProjectDetail(props.selectedProject!)"
                @clear-selection="goClearProjectSelection"
            />
            <template v-else>
            <div
                class="overflow-x-auto rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
            >
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead
                    class="sticky top-0 z-10 border-b border-[#003366]/15 bg-[#f1f5f9] text-xs font-semibold uppercase tracking-wide text-[#003366] shadow-sm"
                >
                    <tr>
                        <th class="px-3 py-3 pl-4">Proyecto</th>
                        <th class="px-3 py-3">Código</th>
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-3 py-3">Inicio</th>
                        <th class="px-3 py-3">Fin</th>
                        <th class="px-3 py-3">Carta inicio</th>
                        <th class="min-w-[10rem] px-3 py-3">Jefe</th>
                        <th class="px-3 py-3 text-right">Avance tareas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#003366]/8 text-[#1e293b]">
                    <tr
                        v-for="p in filteredProjects"
                        :key="p.id"
                        class="border-l-4 transition-colors hover:bg-[#e8f0f8]/55"
                        :class="[accent(p.status).border, accent(p.status).row]"
                    >
                        <td class="px-3 py-2.5 pl-4 align-middle">
                            <div class="flex min-w-[11rem] items-start gap-1">
                                <button
                                    type="button"
                                    class="min-w-0 flex-1 rounded-md px-1 py-1.5 text-left text-sm font-semibold text-[#003366] underline decoration-[#003366]/35 underline-offset-2 transition hover:bg-[#003366]/5 hover:decoration-[#003366]"
                                    :aria-label="`Enfocar proyecto ${p.name} en el tablero`"
                                    @click="goSelectProject(p)"
                                >
                                    {{ p.name }}
                                </button>
                                <button
                                    v-if="props.canEditCarteraFull"
                                    type="button"
                                    class="shrink-0 rounded-md p-1.5 text-[#003366] transition hover:bg-[#003366]/10"
                                    :aria-label="`Editar datos PMO de ${p.name}`"
                                    @click.stop="openProjectDetail(p)"
                                >
                                    <Pencil class="h-4 w-4" aria-hidden="true" />
                                </button>
                            </div>
                        </td>
                        <td
                            class="px-3 py-2.5 align-middle font-mono text-xs text-slate-700"
                        >
                            {{ p.code ?? '—' }}
                        </td>
                        <td class="px-3 py-2.5 align-middle">
                            <div class="flex min-w-[9rem] flex-col gap-1">
                                <select
                                    v-if="props.canEditCarteraFull"
                                    class="max-w-[13rem] rounded-md border border-slate-200 bg-white px-2 py-1.5 text-xs font-medium text-slate-800 shadow-sm focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                                    :value="p.status"
                                    :aria-label="`Estado del proyecto ${p.name}`"
                                    @change="patchProjectStatus(p, $event)"
                                >
                                    <option
                                        v-for="s in props.statuses"
                                        :key="s"
                                        :value="s"
                                    >
                                        {{ statusLabel(s) }}
                                    </option>
                                </select>
                                <span
                                    v-else
                                    class="inline-flex max-w-[13rem] rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                    :class="accent(p.status).pill"
                                >
                                    {{ statusLabel(p.status) }}
                                </span>
                                <p
                                    v-if="allTasksDone(p)"
                                    class="text-[10px] font-semibold leading-tight text-emerald-800"
                                >
                                    Todas las tareas listas — conviene revisar cierre
                                </p>
                            </div>
                        </td>
                        <td class="px-3 py-2.5 align-middle tabular-nums text-xs">
                            {{ formatDateChile(p.starts_at) }}
                        </td>
                        <td class="px-3 py-2.5 align-middle tabular-nums text-xs">
                            {{ formatDateChile(p.ends_at) }}
                        </td>
                        <td class="px-3 py-2.5 align-middle tabular-nums text-xs">
                            {{ formatDateChile(p.carta_inicio_at) }}
                        </td>
                        <td class="px-3 py-2.5 align-middle text-slate-700">
                            {{ p.jefe_proyecto?.name ?? '—' }}
                        </td>
                        <td class="px-3 py-2.5 align-middle text-right">
                            <div
                                class="ml-auto flex max-w-[12rem] flex-col items-end gap-1"
                            >
                                <div
                                    class="h-2 w-full overflow-hidden rounded-full bg-slate-200"
                                    :title="`${p.tasks_total - p.tasks_abiertas} de ${p.tasks_total} tareas listas`"
                                >
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-[#003366] to-[#1e5a8e] transition-[width] duration-300"
                                        :style="{
                                            width: `${progressPercent(p)}%`,
                                        }"
                                    />
                                </div>
                                <span
                                    class="text-[11px] tabular-nums text-slate-600"
                                >
                                    {{ p.tasks_total - p.tasks_abiertas }}/{{
                                        p.tasks_total
                                    }}
                                    · {{ progressPercent(p) }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    <tr
                        v-if="
                            props.projects.length === 0 ||
                            (filteredProjects.length === 0 &&
                                props.projects.length > 0)
                        "
                    >
                        <td
                            colspan="8"
                            class="px-4 py-10 text-center text-slate-500"
                        >
                            <template
                                v-if="
                                    props.projects.length === 0 &&
                                    props.canEditCarteraFull
                                "
                            >
                                No hay proyectos todavía. Pulsa + para crear el
                                primero.
                            </template>
                            <template
                                v-else-if="
                                    props.projects.length === 0 &&
                                    !props.canEditCarteraFull
                                "
                            >
                                No hay proyectos visibles con tu perfil, o aún
                                no se ha registrado cartera.
                            </template>
                            <template v-else>
                                Ningún proyecto coincide con la búsqueda o el
                                estado seleccionado. Ajusta los filtros o pulsa
                                «Limpiar filtros».
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
            </template>
        </div>

        <div
            v-else-if="activeSegment === 'kpi'"
            class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
        >
            <div
                class="border-b border-[#003366]/15 bg-[#f1f5f9] px-4 py-3 shadow-sm"
            >
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                >
                    {{
                        selectedProject !== null
                            ? 'Indicadores del proyecto'
                            : 'Indicadores de cartera'
                    }}
                </p>
                <p class="mt-0.5 text-xs text-slate-600">
                    <template v-if="selectedProject !== null">
                        Métricas y gráficos solo para
                        <span class="font-medium text-[#1e293b]">{{
                            selectedProject.name
                        }}</span>
                        (asignaciones y colaboradores en sus tareas).
                    </template>
                    <template v-else>
                        Totales globales y distribución (Chart.js), mismo
                        criterio que el tablero de proyectos.
                    </template>
                </p>
            </div>
            <div class="space-y-6 p-4">
                <div
                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
                >
                    <div
                        class="rounded-lg border border-[#003366]/12 border-l-4 border-l-[#003366] bg-white p-4 shadow-[0_1px_2px_rgba(0,51,102,0.06)]"
                    >
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#003366]/90"
                        >
                            {{
                                selectedProject !== null
                                    ? 'Personas en tareas'
                                    : 'Usuarios'
                            }}
                        </p>
                        <p class="mt-1.5 text-2xl font-semibold tabular-nums text-[#003366]">
                            {{ usuarios_total }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-[#003366]/12 border-l-4 border-l-[#003366] bg-white p-4 shadow-[0_1px_2px_rgba(0,51,102,0.06)]"
                    >
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#003366]/90"
                        >
                            {{
                                selectedProject !== null
                                    ? 'Proyecto (alcance)'
                                    : 'Proyectos'
                            }}
                        </p>
                        <p class="mt-1.5 text-2xl font-semibold tabular-nums text-[#003366]">
                            {{ proyectos_total }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-[#003366]/12 border-l-4 border-l-[#1e5a8e] bg-white p-4 shadow-[0_1px_2px_rgba(0,51,102,0.06)]"
                    >
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#003366]/90"
                        >
                            {{
                                selectedProject !== null
                                    ? 'Tareas (proyecto)'
                                    : 'Tareas'
                            }}
                        </p>
                        <p class="mt-1.5 text-2xl font-semibold tabular-nums text-[#1e5a8e]">
                            {{ tareas_total }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-[#003366]/12 border-l-4 border-l-[#1e5a8e] bg-white p-4 shadow-[0_1px_2px_rgba(0,51,102,0.06)]"
                    >
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#003366]/90"
                        >
                            {{
                                selectedProject !== null
                                    ? 'Tareas abiertas (proyecto)'
                                    : 'Tareas abiertas'
                            }}
                        </p>
                        <p class="mt-1.5 text-2xl font-semibold tabular-nums text-[#1e5a8e]">
                            {{ tareas_abiertas }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-[#003366]/12 border-l-4 border-l-[#F1C400] bg-white p-4 shadow-[0_1px_2px_rgba(0,51,102,0.06)] sm:col-span-2 lg:col-span-1 xl:col-span-1"
                    >
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#003366]/90"
                        >
                            Urgentes pend. validación
                        </p>
                        <p class="mt-1.5 text-2xl font-semibold tabular-nums text-[#b45309]">
                            {{ tareas_urgentes_pendientes }}
                        </p>
                    </div>
                </div>
                <div
                    class="border-t border-[#003366]/10 pt-2"
                >
                    <p
                        class="mb-3 text-xs font-semibold uppercase tracking-wide text-[#003366]"
                    >
                        {{
                            selectedProject !== null
                                ? 'Distribución en el proyecto'
                                : 'Distribución'
                        }}
                    </p>
                    <KpiStatusCharts
                        :project-scope="selectedProject !== null"
                        :projects-by-status="projects_by_status"
                        :tasks-by-status="tasks_by_status"
                        :tasks-abiertas-por-responsable="
                            tasks_abiertas_por_responsable
                        "
                    />
                </div>
            </div>
        </div>

        <div
            v-else-if="activeSegment === 'gantt'"
            class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
        >
            <div
                class="border-b border-[#003366]/15 bg-[#f1f5f9] px-4 py-3 shadow-sm"
            >
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                >
                    Cronograma (Gantt)
                </p>
                <p class="mt-0.5 text-xs text-slate-600">
                    Tareas de los proyectos visibles. La barra usa la fecha
                    límite si existe; si no, la fecha de alta y una ventana de
                    una semana.
                </p>
            </div>
            <div class="p-4">
                <div class="mb-4 rounded-xl border border-[#003366]/12 bg-white p-3 shadow-sm">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-[#003366]">
                                Escala de tiempo
                            </span>
                            <div class="inline-flex rounded-lg border border-[#003366]/20 bg-[#f8fafc] p-1">
                                <button
                                    v-for="mode in (['Day', 'Week', 'Month', 'Year'] as GanttViewMode[])"
                                    :key="mode"
                                    type="button"
                                    class="rounded-md px-2.5 py-1 text-xs font-medium transition"
                                    :class="
                                        ganttViewMode === mode
                                            ? 'bg-[#003366] text-white shadow-sm'
                                            : 'text-slate-700 hover:bg-[#e2e8f0]'
                                    "
                                    @click="ganttViewMode = mode"
                                >
                                    {{ ganttViewModeLabel[mode] }}
                                </button>
                            </div>
                            <Button variant="outline" size="sm" class="h-8" @click="goGanttToday">
                                Hoy
                            </Button>
                            <Button variant="outline" size="sm" class="h-8" @click="openQuickTaskModal()">
                                Nueva tarea
                            </Button>
                        </div>
                        <div class="text-xs text-slate-600">
                            Vista Gantt de planificación
                        </div>
                    </div>

                    <div class="mb-3 flex flex-wrap gap-2">
                        <button
                            v-for="status in ganttAvailableStatuses"
                            :key="status"
                            type="button"
                            class="rounded-full border px-2.5 py-1 text-xs font-medium transition"
                            :class="
                                ganttSelectedStatuses.includes(status)
                                    ? 'border-[#003366] bg-[#003366] text-white'
                                    : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                            "
                            @click="toggleGanttStatus(status)"
                        >
                            {{ ganttStatusLabels[status] ?? status }}
                        </button>

                        <button
                            type="button"
                            class="rounded-full border border-slate-300 px-2.5 py-1 text-xs text-slate-700 hover:bg-slate-50"
                            @click="setAllGanttStatuses"
                        >
                            Todos
                        </button>
                        <button
                            type="button"
                            class="rounded-full border border-slate-300 px-2.5 py-1 text-xs text-slate-700 hover:bg-slate-50"
                            @click="clearGanttStatuses"
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
                    :projects="filteredGanttProjects"
                    :view-mode="ganttViewMode"
                    @task-click="onGanttTaskClick"
                />
            </div>
        </div>

        <div
            v-else-if="activeSegment === 'calendario'"
            class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
        >
            <div class="border-b border-[#003366]/15 bg-[#f1f5f9] px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#003366]">
                    Calendario mensual
                </p>
                <p class="mt-0.5 text-xs text-slate-600">
                    Tareas con fecha de vencimiento dentro del mes seleccionado.
                </p>
            </div>
            <div class="p-4">
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[#003366]/12 bg-white p-3">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-[#003366]">
                            Vista
                        </label>
                        <select
                            v-model="calendarView"
                            class="h-8 rounded-md border border-input bg-white px-2 text-xs text-slate-800"
                            @change="shiftCalendarPeriod(0)"
                        >
                            <option value="day">Diario</option>
                            <option value="week">Semanal</option>
                            <option value="month">Mensual</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        class="rounded-md border border-input px-3 py-1 text-sm"
                        @click="shiftCalendarPeriod(-1)"
                    >
                        ← Anterior
                    </button>
                    <h2 class="text-sm font-semibold text-[#003366]">
                        {{ props.calendar.label }}
                    </h2>
                    <button
                        type="button"
                        class="rounded-md border border-input px-3 py-1 text-sm"
                        @click="shiftCalendarPeriod(1)"
                    >
                        Siguiente →
                    </button>
                </div>

                <div
                    v-if="calendarView !== 'day'"
                    class="mt-4 grid gap-px rounded-lg border border-[#003366]/15 bg-[#003366]/15 text-xs"
                    :class="calendarGridClass"
                >
                    <div
                        v-for="h in calendarWeekdayHeaders"
                        :key="h"
                        class="bg-[#f8fafc] py-2 text-center font-semibold text-[#003366]"
                    >
                        {{ h }}
                    </div>

                    <template v-for="(c, idx) in calendarCells" :key="idx">
                        <div v-if="c.day === null" class="min-h-[6rem] bg-slate-50/80" />
                        <div v-else class="min-h-[7rem] bg-white p-1.5 align-top">
                            <span class="font-medium text-[#003366]">{{ c.day }}</span>
                            <ul class="mt-1 space-y-1">
                                <li
                                    v-for="t in props.calendar.tasks_by_day[c.key!] ?? []"
                                    :key="t.id"
                                    class="rounded border border-[#003366]/12 bg-[#f8fafc] px-1 py-0.5 text-[10px] leading-tight text-[#333] whitespace-normal break-words transition hover:border-[#003366]/35 hover:bg-[#eef4fb]"
                                >
                                    <button
                                        type="button"
                                        class="w-full text-left"
                                        :title="`Ir a Kanban y destacar actividad: ${t.title}`"
                                        @click="openTaskInKanban(t)"
                                    >
                                        <span class="block font-medium">{{ t.title }}</span>
                                        <span v-if="props.selectedProjectId === null && t.project_name" class="block text-[#64748b]">
                                            {{ t.project_name }}
                                        </span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </template>
                </div>

                <div
                    v-if="calendarView === 'day'"
                    class="mt-4 rounded-lg border border-[#003366]/15 bg-white p-4"
                >
                    <h3 class="text-sm font-semibold text-[#003366]">Agenda del día</h3>
                    <p class="mt-1 text-xs text-slate-600">{{ props.calendar.label }}</p>
                    <ul class="mt-3 space-y-2">
                        <li
                            v-for="t in (props.calendar.tasks_by_day[props.calendar.start_date] ?? [])"
                            :key="t.id"
                            class="rounded-md border border-[#003366]/12 bg-[#f8fafc] px-3 py-2 text-sm text-[#1f2937] transition hover:border-[#003366]/35 hover:bg-[#eef4fb]"
                        >
                            <button
                                type="button"
                                class="w-full text-left"
                                :title="`Ir a Kanban y destacar actividad: ${t.title}`"
                                @click="openTaskInKanban(t)"
                            >
                                <span class="block font-medium">{{ t.title }}</span>
                                <span v-if="props.selectedProjectId === null && t.project_name" class="mt-0.5 block text-xs text-[#64748b]">
                                    {{ t.project_name }}
                                </span>
                            </button>
                        </li>
                        <li
                            v-if="(props.calendar.tasks_by_day[props.calendar.start_date] ?? []).length === 0"
                            class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600"
                        >
                            Sin tareas con vencimiento para este día.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div
            v-else-if="activeSegment === 'lista'"
            class="space-y-4"
        >
            <div
                v-if="props.listaPortfolioMode"
                class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
            >
                <div
                    class="border-b border-[#003366]/15 bg-[#f1f5f9] px-4 py-3 shadow-sm"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                    >
                        Lista de tareas (cartera completa)
                    </p>
                    <p class="mt-0.5 text-xs text-slate-600">
                        Todas las tareas de los proyectos que ves en esta cartera.
                        Para añadir segmentos usa la lista en un proyecto
                        concreto.
                    </p>
                </div>
                <div class="border-t border-[#003366]/8 p-4">
                    <ProyectoTaskListPanel
                        id-suffix="macro-cartera"
                        portfolio-mode
                        hide-segment-column
                        :project="{
                            id: 0,
                            name: 'Cartera completa',
                            code: null,
                        }"
                        :task-groups="listaTaskGroups"
                        :tasks="listaTasks"
                        :statuses="taskStatuses"
                        :people-options="listaPeopleOptions"
                    />
                </div>
            </div>
            <div
                v-else-if="props.selectedProject !== null"
                class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
            >
                <div
                    class="border-b border-[#003366]/15 bg-[#f1f5f9] px-4 py-3 shadow-sm"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                    >
                        Lista de tareas (proyecto)
                    </p>
                    <p class="mt-0.5 text-xs text-slate-600">
                        Edición en línea y asignaciones; se guardan con la misma
                        API que el espacio Proyecto → Lista de tareas.
                    </p>
                </div>
                <div class="border-t border-[#003366]/8 p-4">
                    <ProyectoTaskListPanel
                        id-suffix="macro"
                        hide-segment-column
                        :project="{
                            id: props.selectedProject!.id,
                            name: props.selectedProject!.name,
                            code: props.selectedProject!.code,
                        }"
                        :task-groups="listaTaskGroups"
                        :tasks="listaTasks"
                        :statuses="taskStatuses"
                        :people-options="listaPeopleOptions"
                    />
                </div>
            </div>
            <div
                v-else
                class="rounded-xl border border-dashed border-[#003366]/25 bg-[#fafbfc] px-4 py-10 text-center text-sm text-slate-600"
            >
                <p class="font-medium text-[#003366]">
                    No hay proyectos en tu cartera
                </p>
                <p class="mt-2 text-xs text-slate-600">
                    Cuando existan proyectos accesibles, aquí verás la lista
                    completa de tareas. También puedes enfocar un proyecto en la
                    barra lateral para filtrar solo ese proyecto.
                </p>
            </div>
        </div>

        <div
            v-else-if="activeSegment === 'kanban'"
            class="space-y-6"
        >
            <template v-if="props.kanbanBoards.length > 0">
                <div
                    v-for="board in props.kanbanBoards"
                    :key="board.project.id"
                    class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
                >
                    <div
                        class="border-b border-[#003366]/15 bg-[#f1f5f9] px-4 py-3 shadow-sm"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                        >
                            {{
                                props.kanbanPortfolioMode
                                    ? `Kanban — ${board.project.name}`
                                    : 'Kanban (proyecto)'
                            }}
                        </p>
                        <p class="mt-0.5 font-mono text-[11px] text-slate-600">
                            {{ board.project.code ?? 'Sin código' }}
                        </p>
                        <p
                            v-if="props.kanbanPortfolioMode"
                            class="mt-1 text-xs text-slate-600"
                        >
                            Arrastre y órdenes se guardan por proyecto; no se
                            mueven tarjetas entre proyectos distintos.
                        </p>
                    </div>
                    <div class="border-t border-[#003366]/8 p-4">
                        <ProyectoKanbanBoard
                            :project="board.project"
                            :groups="board.groups"
                            :statuses="taskStatuses"
                            :people-options="kanbanPeopleOptions"
                            :portfolio-mode="props.kanbanPortfolioMode"
                            :id-suffix="`macro-${board.project.id}`"
                        />
                    </div>
                </div>
            </template>
            <div
                v-else
                class="rounded-xl border border-dashed border-[#003366]/25 bg-[#fafbfc] px-4 py-10 text-center text-sm text-slate-600"
            >
                <p class="font-medium text-[#003366]">
                    No hay proyectos en tu cartera
                </p>
                <p class="mt-2 text-xs text-slate-600">
                    Cuando existan proyectos accesibles, aquí podrás usar el
                    Kanban integrado en la cartera PMO.
                </p>
            </div>
        </div>

        <div
            v-else-if="activeSegment === 'carga'"
            class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
        >
            <div
                class="border-b border-[#003366]/15 bg-[#f1f5f9] px-4 py-3 shadow-sm"
            >
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                >
                    Carga por persona (carga de trabajo)
                </p>
                <p class="mt-0.5 text-xs text-slate-600">
                    Basado en tareas
                    <span class="font-medium text-slate-800">no hechas</span>
                    con
                    <span class="font-medium text-slate-800"
                        >responsable asignado</span
                    >. En cartera completa las barras apilan
                    <span class="font-medium text-slate-800">proyectos</span>;
                    con un proyecto enfocado, apilan
                    <span class="font-medium text-slate-800">estados</span> del
                    Kanban.
                </p>
            </div>
            <div class="p-4">
                <PortfolioWorkloadPanel
                    :workload="props.portfolioWorkload"
                    :portfolio-scope="props.selectedProject === null"
                    :project-name="props.selectedProject?.name ?? null"
                    :thresholds="props.workloadThresholds"
                />
            </div>
        </div>
        </div>

        <button
            v-if="activeSegment === 'cartera' && props.canEditCarteraFull"
            type="button"
            class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-[#003366] text-white shadow-lg ring-2 ring-white/90 transition hover:bg-[#00264d] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#003366]"
            aria-label="Nuevo proyecto"
            @click="openCreateModal"
        >
            <Plus class="h-7 w-7" aria-hidden="true" />
        </button>

        <Dialog v-model:open="ganttTaskModalOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Tarea en cronograma</DialogTitle>
                    <DialogDescription>
                        Acción rápida desde el Gantt para abrir o continuar trabajo.
                    </DialogDescription>
                </DialogHeader>
                <div v-if="selectedGanttTask" class="space-y-3 text-sm">
                    <div class="rounded-md border border-[#003366]/12 bg-[#f8fafc] p-3">
                        <p class="font-semibold text-[#003366]">{{ selectedGanttTask.task_title }}</p>
                        <p class="text-xs text-slate-600">
                            {{ selectedGanttTask.project_name }} · Estado: {{ ganttStatusLabels[selectedGanttTask.status] ?? selectedGanttTask.status }}
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <Button type="button" class="bg-[#003366] hover:bg-[#00264d]" @click="goToKanbanTask(selectedGanttTask)">
                            Ir a la tarea
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="router.get(tableroMacro.url({ query: { segment: 'lista', project_id: selectedGanttTask.project_id } }))"
                        >
                            Ver lista del proyecto
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            class="sm:col-span-2"
                            @click="
                                ganttTaskModalOpen = false;
                                openQuickTaskModal(selectedGanttTask.project_id);
                            "
                        >
                            Crear nueva tarea en este proyecto
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="quickGanttTaskOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Nueva tarea desde Gantt</DialogTitle>
                    <DialogDescription>
                        Crea una tarea rápida sin salir del cronograma.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                    <div class="space-y-1">
                        <Label for="gantt-project">Proyecto</Label>
                        <select
                            id="gantt-project"
                            v-model="quickTaskForm.project_id"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option disabled value="">Selecciona un proyecto</option>
                            <option v-for="p in props.projects" :key="p.id" :value="String(p.id)">
                                {{ p.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <Label for="gantt-group">Segmento</Label>
                        <select
                            id="gantt-group"
                            v-model="quickTaskForm.task_group_id"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option disabled value="">Selecciona un segmento</option>
                            <option v-for="g in quickTaskGroupOptions" :key="g.id" :value="String(g.id)">
                                {{ g.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <Label for="gantt-title">Título de tarea</Label>
                        <Input id="gantt-title" v-model="quickTaskForm.title" maxlength="255" placeholder="Ej: Preparar reunión de avance" />
                    </div>
                    <p class="text-xs text-slate-600">
                        La tarea se crea en backlog y quedará disponible de inmediato en Kanban, Lista y Gantt.
                    </p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="secondary" @click="quickGanttTaskOpen = false">
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        class="bg-[#003366] hover:bg-[#00264d]"
                        :disabled="quickTaskForm.processing || !quickTaskForm.project_id || !quickTaskForm.task_group_id || !quickTaskForm.title.trim()"
                        @click="submitQuickGanttTask"
                    >
                        Crear tarea
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="detailOpen">
            <DialogContent
                class="flex max-h-[85vh] w-[min(98vw,88rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-[88rem]"
            >
                <DialogHeader class="shrink-0 space-y-1 border-b border-[#003366]/10 px-6 pb-3 pt-6 text-left">
                    <DialogTitle class="text-[#003366]">Proyecto</DialogTitle>
                    <DialogDescription class="text-sm">
                        Vista amplia: identidad, descripción y acta de
                        constitución (archivo de inicio). Guardar actualiza la
                        cartera PMO.
                    </DialogDescription>
                </DialogHeader>
                <div
                    v-if="detailProject"
                    class="min-h-0 flex-1 overflow-y-auto px-6 py-4"
                >
                    <div
                        class="grid gap-6 lg:grid-cols-2 lg:items-start lg:gap-8"
                    >
                        <div class="space-y-3">
                            <div class="space-y-1">
                                <Label for="d-name">Nombre</Label>
                                <Input id="d-name" v-model="detailForm.name" />
                                <InputError :message="detailForm.errors.name" />
                            </div>
                            <div class="space-y-1">
                                <Label for="d-code">Código (opcional)</Label>
                                <Input id="d-code" v-model="detailForm.code" />
                                <InputError :message="detailForm.errors.code" />
                            </div>
                            <div class="space-y-1">
                                <Label for="d-desc">Descripción</Label>
                                <textarea
                                    id="d-desc"
                                    v-model="detailForm.description"
                                    rows="5"
                                    class="flex max-h-[140px] min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <InputError
                                    :message="detailForm.errors.description"
                                />
                            </div>
                            <div
                                class="rounded-lg border border-[#003366]/12 bg-[#f8fafc] p-3"
                            >
                                <div class="flex items-start gap-2">
                                    <FileText
                                        class="mt-0.5 h-5 w-5 shrink-0 text-[#003366]"
                                        aria-hidden="true"
                                    />
                                    <div class="min-w-0 flex-1 space-y-2">
                                        <div>
                                            <p
                                                class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                                            >
                                                Acta de constitución
                                            </p>
                                            <p class="text-xs text-slate-600">
                                                Documento formal de inicio del
                                                proyecto (PDF o Word).
                                            </p>
                                        </div>
                                        <div
                                            v-if="
                                                detailProject.acta_constitucion &&
                                                !detailForm.remove_acta_constitucion
                                            "
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <a
                                                :href="
                                                    detailProject
                                                        .acta_constitucion
                                                        .download_url
                                                "
                                                class="inline-flex max-w-full truncate text-sm font-medium text-[#003366] underline underline-offset-2 hover:text-[#00264d]"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                {{
                                                    detailProject
                                                        .acta_constitucion
                                                        .original_name ??
                                                    'Descargar archivo'
                                                }}
                                            </a>
                                        </div>
                                        <p
                                            v-else-if="
                                                detailForm.remove_acta_constitucion
                                            "
                                            class="text-xs text-amber-800"
                                        >
                                            Se eliminará el archivo al guardar.
                                        </p>
                                        <div class="space-y-2">
                                            <Input
                                                :key="detailFileInputKey"
                                                type="file"
                                                accept=".pdf,.doc,.docx,application/pdf"
                                                class="cursor-pointer text-xs file:mr-2 file:rounded file:border-0 file:bg-[#003366]/10 file:px-2 file:py-1 file:text-xs file:font-medium file:text-[#003366]"
                                                @change="onDetailActaFile"
                                            />
                                            <InputError
                                                :message="
                                                    detailForm.errors
                                                        .acta_constitucion
                                                "
                                            />
                                            <label
                                                v-if="
                                                    detailProject.acta_constitucion
                                                "
                                                class="flex cursor-pointer items-center gap-2 text-xs text-slate-700"
                                            >
                                                <input
                                                    v-model="
                                                        detailForm.remove_acta_constitucion
                                                    "
                                                    type="checkbox"
                                                    class="rounded border-input"
                                                />
                                                Quitar acta actual
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div
                                class="rounded-md border border-[#003366]/10 bg-[#f8fafc] px-3 py-2 text-xs text-slate-600"
                            >
                                Avance de tareas:
                                <span class="font-medium text-[#003366]">{{
                                    detailProject.tasks_total -
                                    detailProject.tasks_abiertas
                                }}</span>
                                / {{ detailProject.tasks_total }} ·
                                {{ progressPercent(detailProject) }}%
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <Label for="d-carta">Carta inicio</Label>
                                    <Input
                                        id="d-carta"
                                        v-model="detailForm.carta_inicio_at"
                                        type="date"
                                    />
                                    <InputError
                                        :message="
                                            detailForm.errors.carta_inicio_at
                                        "
                                    />
                                </div>
                                <div class="space-y-1">
                                    <Label for="d-status">Estado</Label>
                                    <select
                                        id="d-status"
                                        v-model="detailForm.status"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs capitalize"
                                    >
                                        <option
                                            v-for="s in props.statuses"
                                            :key="s"
                                            :value="s"
                                        >
                                            {{ statusLabel(s) }}
                                        </option>
                                    </select>
                                    <InputError
                                        :message="detailForm.errors.status"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <Label for="d-start">Inicio</Label>
                                    <Input
                                        id="d-start"
                                        v-model="detailForm.starts_at"
                                        type="date"
                                    />
                                    <InputError
                                        :message="detailForm.errors.starts_at"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <Label for="d-end">Fin</Label>
                                    <Input
                                        id="d-end"
                                        v-model="detailForm.ends_at"
                                        type="date"
                                    />
                                    <InputError
                                        :message="detailForm.errors.ends_at"
                                    />
                                </div>
                            </div>
                            <div class="space-y-1">
                                <Label for="d-jefe">Jefe de proyecto</Label>
                                <select
                                    id="d-jefe"
                                    v-model="detailForm.jefe_proyecto_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="">Sin asignar</option>
                                    <option
                                        v-for="u in props.jefeOptions"
                                        :key="u.id"
                                        :value="String(u.id)"
                                    >
                                        {{ u.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="
                                        detailForm.errors.jefe_proyecto_id
                                    "
                                />
                            </div>
                            <div class="space-y-1">
                                <Label>Equipo asignado al proyecto</Label>
                                <div class="max-h-44 space-y-1 overflow-y-auto rounded-md border border-input p-2">
                                    <label
                                        v-for="u in props.memberOptions"
                                        :key="`d-member-${u.id}`"
                                        class="flex items-center gap-2 text-xs text-slate-700"
                                    >
                                        <input
                                            type="checkbox"
                                            class="rounded border-input"
                                            :checked="detailForm.member_ids.includes(u.id)"
                                            @change="
                                                toggleMemberSelection(
                                                    'detail',
                                                    u.id,
                                                    ($event.target as HTMLInputElement).checked,
                                                )
                                            "
                                        />
                                        <span>{{ u.name }}</span>
                                    </label>
                                </div>
                                <InputError :message="detailForm.errors.member_ids" />
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter
                    class="shrink-0 gap-2 border-t border-[#003366]/10 px-6 py-4 sm:gap-0"
                >
                    <Button
                        type="button"
                        variant="secondary"
                        @click="closeProjectDetail"
                    >
                        Cerrar
                    </Button>
                    <Button
                        type="button"
                        class="bg-[#003366] hover:bg-[#003366]/90"
                        :disabled="detailForm.processing"
                        @click="submitProjectDetail"
                    >
                        Guardar cambios
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="createOpen">
            <DialogContent
                class="flex max-h-[85vh] w-[min(98vw,88rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-[88rem]"
            >
                <DialogHeader class="shrink-0 space-y-1 border-b border-[#003366]/10 px-6 pb-3 pt-6 text-left">
                    <DialogTitle class="text-[#003366]">Nuevo proyecto</DialogTitle>
                    <DialogDescription class="text-sm">
                        Alta en cartera PMO. Puedes adjuntar el acta de
                        constitución como archivo de inicio.
                    </DialogDescription>
                </DialogHeader>
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                    <div
                        class="grid gap-6 lg:grid-cols-2 lg:items-start lg:gap-8"
                    >
                        <div class="space-y-3">
                            <div class="space-y-1">
                                <Label for="c-name">Nombre</Label>
                                <Input
                                    id="c-name"
                                    v-model="createForm.name"
                                    required
                                    autocomplete="off"
                                />
                                <InputError :message="createForm.errors.name" />
                            </div>
                            <div class="space-y-1">
                                <Label for="c-code">Código (opcional)</Label>
                                <Input id="c-code" v-model="createForm.code" />
                                <InputError :message="createForm.errors.code" />
                            </div>
                            <div class="space-y-1">
                                <Label for="c-desc">Descripción</Label>
                                <textarea
                                    id="c-desc"
                                    v-model="createForm.description"
                                    rows="5"
                                    class="flex max-h-[140px] min-h-[88px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <InputError
                                    :message="createForm.errors.description"
                                />
                            </div>
                            <div
                                class="rounded-lg border border-[#003366]/12 bg-[#f8fafc] p-3"
                            >
                                <div class="flex items-start gap-2">
                                    <FileText
                                        class="mt-0.5 h-5 w-5 shrink-0 text-[#003366]"
                                        aria-hidden="true"
                                    />
                                    <div class="min-w-0 flex-1 space-y-2">
                                        <p
                                            class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                                        >
                                            Acta de constitución
                                        </p>
                                        <p class="text-xs text-slate-600">
                                            Opcional. PDF o Word (máx. 15&nbsp;MB).
                                        </p>
                                        <Input
                                            :key="createFileInputKey"
                                            type="file"
                                            accept=".pdf,.doc,.docx,application/pdf"
                                            class="cursor-pointer text-xs file:mr-2 file:rounded file:border-0 file:bg-[#003366]/10 file:px-2 file:py-1 file:text-xs file:font-medium file:text-[#003366]"
                                            @change="onCreateActaFile"
                                        />
                                        <InputError
                                            :message="
                                                createForm.errors
                                                    .acta_constitucion
                                            "
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <Label for="c-carta">Carta inicio</Label>
                                    <Input
                                        id="c-carta"
                                        v-model="createForm.carta_inicio_at"
                                        type="date"
                                    />
                                    <InputError
                                        :message="
                                            createForm.errors.carta_inicio_at
                                        "
                                    />
                                </div>
                                <div class="space-y-1">
                                    <Label for="c-status">Estado</Label>
                                    <select
                                        id="c-status"
                                        v-model="createForm.status"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs capitalize"
                                    >
                                        <option
                                            v-for="s in props.statuses"
                                            :key="s"
                                            :value="s"
                                        >
                                            {{ statusLabel(s) }}
                                        </option>
                                    </select>
                                    <InputError
                                        :message="createForm.errors.status"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <Label for="c-start">Inicio</Label>
                                    <Input
                                        id="c-start"
                                        v-model="createForm.starts_at"
                                        type="date"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <Label for="c-end">Fin</Label>
                                    <Input
                                        id="c-end"
                                        v-model="createForm.ends_at"
                                        type="date"
                                    />
                                </div>
                            </div>
                            <div class="space-y-1">
                                <Label for="c-jefe">Jefe de proyecto</Label>
                                <select
                                    id="c-jefe"
                                    v-model="createForm.jefe_proyecto_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="">Sin asignar</option>
                                    <option
                                        v-for="u in props.jefeOptions"
                                        :key="u.id"
                                        :value="String(u.id)"
                                    >
                                        {{ u.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="
                                        createForm.errors.jefe_proyecto_id
                                    "
                                />
                            </div>
                            <div class="space-y-1">
                                <Label>Equipo asignado al proyecto</Label>
                                <div class="max-h-44 space-y-1 overflow-y-auto rounded-md border border-input p-2">
                                    <label
                                        v-for="u in props.memberOptions"
                                        :key="`c-member-${u.id}`"
                                        class="flex items-center gap-2 text-xs text-slate-700"
                                    >
                                        <input
                                            type="checkbox"
                                            class="rounded border-input"
                                            :checked="createForm.member_ids.includes(u.id)"
                                            @change="
                                                toggleMemberSelection(
                                                    'create',
                                                    u.id,
                                                    ($event.target as HTMLInputElement).checked,
                                                )
                                            "
                                        />
                                        <span>{{ u.name }}</span>
                                    </label>
                                </div>
                                <InputError :message="createForm.errors.member_ids" />
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter
                    class="shrink-0 gap-2 border-t border-[#003366]/10 px-6 py-4 sm:gap-0"
                >
                    <Button
                        type="button"
                        variant="secondary"
                        @click="createOpen = false"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        class="bg-[#003366] hover:bg-[#003366]/90"
                        :disabled="createForm.processing"
                        @click="submitCreate"
                    >
                        Crear proyecto
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
