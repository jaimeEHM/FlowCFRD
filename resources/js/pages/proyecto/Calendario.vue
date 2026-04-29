<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ProyectoWorkspaceTabs from '@/components/workflow/ProyectoWorkspaceTabs.vue';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { calendario } from '@/routes/proyecto';

type TaskDay = {
    id: number;
    title: string;
    status: string;
    project_id: number;
    assignee: { name: string } | null;
};

type Proj = { id: number; name: string; code: string | null };
type CalendarView = 'day' | 'week' | 'month';

const props = defineProps<{
    project: Proj | null;
    projects: Proj[];
    calendar: {
        view: CalendarView;
        date: string;
        start_date: string;
        end_date: string;
        label: string;
        tasks_by_day: Record<string, TaskDay[]>;
    };
}>();

const projectId = ref(props.project?.id ?? '');
const view = ref<CalendarView>(props.calendar.view);
const anchorDate = ref(props.calendar.date);

function parseIsoLocalDate(iso: string): Date {
    const [y, m, d] = iso.split('-').map((v) => Number(v));
    return new Date(y, (m || 1) - 1, d || 1);
}

function formatIsoLocalDate(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

watch(
    () => props.project?.id,
    (id) => {
        projectId.value = id ?? '';
    },
);
watch(
    () => props.calendar.view,
    (v) => {
        view.value = v;
    },
);
watch(
    () => props.calendar.date,
    (d) => {
        anchorDate.value = d;
    },
);

function changeProject() {
    if (!projectId.value) {
        return;
    }
    router.get(
        calendario.url({
            query: {
                project_id: Number(projectId.value),
                view: view.value,
                date: anchorDate.value,
            },
        }),
    );
}

function shiftPeriod(delta: number) {
    const base = parseIsoLocalDate(anchorDate.value);
    if (view.value === 'day') {
        base.setDate(base.getDate() + delta);
    } else if (view.value === 'week') {
        base.setDate(base.getDate() + (7 * delta));
    } else {
        base.setMonth(base.getMonth() + delta);
    }

    const q: Record<string, string | number> = {
        view: view.value,
        date: formatIsoLocalDate(base),
    };
    if (props.project) {
        q.project_id = props.project.id;
    }
    router.get(calendario.url({ query: q }));
}

function openTaskInKanban(task: TaskDay): void {
    router.get('/proyecto/kanban', {
        project_id: task.project_id,
        focus_task_id: task.id,
    });
}

const cells = computed(() => {
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

const weekdayHeaders = computed(() => {
    if (props.calendar.view === 'day') {
        return ['Día'];
    }
    return ['Lun', 'Mar', 'Mié', 'Jue', 'Vie'];
});

const calendarGridClass = computed(() =>
    props.calendar.view === 'day' ? 'grid-cols-1' : 'grid-cols-5',
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Calendario del proyecto', href: calendario() },
        ],
    },
});
</script>

<template>
    <Head title="Calendario — proyecto" />

    <WorkflowSection
        context-label="Jefe de proyecto — ejecución"
        title="Calendario (vencimientos)"
        description="Tareas con fecha de vencimiento en el mes seleccionado. Cambia de mes con los botones."
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
                active="calendario"
                :project="project"
                :projects="projects"
            />

            <div
                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[#003366]/12 bg-white p-3"
            >
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-[#003366]">
                        Vista
                    </label>
                    <select
                        v-model="view"
                        class="h-8 rounded-md border border-input bg-white px-2 text-xs text-slate-800"
                        @change="shiftPeriod(0)"
                    >
                        <option value="day">Diario</option>
                        <option value="week">Semanal</option>
                        <option value="month">Mensual</option>
                    </select>
                </div>
                <button
                    type="button"
                    class="rounded-md border border-input px-3 py-1 text-sm"
                    @click="shiftPeriod(-1)"
                >
                    ← Anterior
                </button>
                <h2 class="text-sm font-semibold text-[#003366]">
                    {{ calendar.label }}
                </h2>
                <button
                    type="button"
                    class="rounded-md border border-input px-3 py-1 text-sm"
                    @click="shiftPeriod(1)"
                >
                    Siguiente →
                </button>
            </div>

            <div
                v-if="view !== 'day'"
                class="mt-4 grid gap-px rounded-lg border border-[#003366]/15 bg-[#003366]/15 text-xs"
                :class="calendarGridClass"
            >
                <div
                    v-for="h in weekdayHeaders"
                    :key="h"
                    class="bg-[#f8fafc] py-2 text-center font-semibold text-[#003366]"
                >
                    {{ h }}
                </div>
                <template v-for="(c, idx) in cells" :key="idx">
                    <div
                        v-if="c.day === null"
                        class="min-h-[5rem] bg-slate-50/80"
                    />
                    <div
                        v-else
                        class="min-h-[7rem] bg-white p-1.5 align-top"
                    >
                        <span class="font-medium text-[#003366]">{{
                            c.day
                        }}</span>
                        <ul class="mt-1 space-y-1">
                            <li
                                v-for="t in calendar.tasks_by_day[c.key!] ?? []"
                                :key="t.id"
                                class="rounded border border-[#003366]/12 bg-[#f8fafc] px-1 py-0.5 text-[10px] leading-tight text-[#333] whitespace-normal break-words transition hover:border-[#003366]/35 hover:bg-[#eef4fb]"
                            >
                                <button
                                    type="button"
                                    class="w-full text-left"
                                    :title="`Ir a Kanban y destacar actividad: ${t.title}`"
                                    @click="openTaskInKanban(t)"
                                >
                                    {{ t.title }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </template>
            </div>

            <div
                v-if="view === 'day'"
                class="mt-4 rounded-lg border border-[#003366]/15 bg-white p-4"
            >
                <h3 class="text-sm font-semibold text-[#003366]">Agenda del día</h3>
                <p class="mt-1 text-xs text-slate-600">{{ calendar.label }}</p>
                <ul class="mt-3 space-y-2">
                    <li
                        v-for="t in (calendar.tasks_by_day[calendar.start_date] ?? [])"
                        :key="t.id"
                        class="rounded-md border border-[#003366]/12 bg-[#f8fafc] px-3 py-2 text-sm text-[#1f2937] transition hover:border-[#003366]/35 hover:bg-[#eef4fb]"
                    >
                        <button
                            type="button"
                            class="w-full text-left"
                            :title="`Ir a Kanban y destacar actividad: ${t.title}`"
                            @click="openTaskInKanban(t)"
                        >
                            {{ t.title }}
                        </button>
                    </li>
                    <li
                        v-if="(calendar.tasks_by_day[calendar.start_date] ?? []).length === 0"
                        class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-600"
                    >
                        Sin tareas con vencimiento para este día.
                    </li>
                </ul>
            </div>
        </template>
    </WorkflowSection>
</template>
