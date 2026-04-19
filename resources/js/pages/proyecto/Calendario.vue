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
    assignee: { name: string } | null;
};

type Proj = { id: number; name: string; code: string | null };

const props = defineProps<{
    project: Proj | null;
    projects: Proj[];
    calendar: {
        month: number;
        year: number;
        label: string;
        tasks_by_day: Record<string, TaskDay[]>;
    };
}>();

const projectId = ref(props.project?.id ?? '');

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
        calendario.url({
            query: {
                project_id: Number(projectId.value),
                month: props.calendar.month,
                year: props.calendar.year,
            },
        }),
    );
}

function shiftMonth(delta: number) {
    let m = props.calendar.month + delta;
    let y = props.calendar.year;
    if (m < 1) {
        m = 12;
        y -= 1;
    } else if (m > 12) {
        m = 1;
        y += 1;
    }
    const q: Record<string, string | number> = { month: m, year: y };
    if (props.project) {
        q.project_id = props.project.id;
    }
    router.get(calendario.url({ query: q }));
}

const daysInMonth = computed(() => {
    const y = props.calendar.year;
    const m = props.calendar.month;
    return new Date(y, m, 0).getDate();
});

const firstWeekday = computed(() => {
    const y = props.calendar.year;
    const m = props.calendar.month;
    return new Date(y, m - 1, 1).getDay();
});

const cells = computed(() => {
    const total = daysInMonth.value;
    const pad = firstWeekday.value;
    const y = props.calendar.year;
    const m = props.calendar.month;
    const out: { day: number | null; key: string | null }[] = [];
    for (let i = 0; i < pad; i++) {
        out.push({ day: null, key: null });
    }
    for (let d = 1; d <= total; d++) {
        const key = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        out.push({ day: d, key });
    }
    return out;
});

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
                <button
                    type="button"
                    class="rounded-md border border-input px-3 py-1 text-sm"
                    @click="shiftMonth(-1)"
                >
                    ← Mes anterior
                </button>
                <h2 class="text-sm font-semibold text-[#003366]">
                    {{ calendar.label }}
                </h2>
                <button
                    type="button"
                    class="rounded-md border border-input px-3 py-1 text-sm"
                    @click="shiftMonth(1)"
                >
                    Mes siguiente →
                </button>
            </div>

            <div
                class="mt-4 grid grid-cols-7 gap-px rounded-lg border border-[#003366]/15 bg-[#003366]/15 text-xs"
            >
                <div
                    class="bg-[#f8fafc] py-2 text-center font-semibold text-[#003366]"
                >
                    Dom
                </div>
                <div
                    class="bg-[#f8fafc] py-2 text-center font-semibold text-[#003366]"
                >
                    Lun
                </div>
                <div
                    class="bg-[#f8fafc] py-2 text-center font-semibold text-[#003366]"
                >
                    Mar
                </div>
                <div
                    class="bg-[#f8fafc] py-2 text-center font-semibold text-[#003366]"
                >
                    Mié
                </div>
                <div
                    class="bg-[#f8fafc] py-2 text-center font-semibold text-[#003366]"
                >
                    Jue
                </div>
                <div
                    class="bg-[#f8fafc] py-2 text-center font-semibold text-[#003366]"
                >
                    Vie
                </div>
                <div
                    class="bg-[#f8fafc] py-2 text-center font-semibold text-[#003366]"
                >
                    Sáb
                </div>
                <template v-for="(c, idx) in cells" :key="idx">
                    <div
                        v-if="c.day === null"
                        class="min-h-[5rem] bg-slate-50/80"
                    />
                    <div
                        v-else
                        class="min-h-[5rem] bg-white p-1.5 align-top"
                    >
                        <span class="font-medium text-[#003366]">{{
                            c.day
                        }}</span>
                        <ul class="mt-1 space-y-1">
                            <li
                                v-for="t in calendar.tasks_by_day[c.key!] ?? []"
                                :key="t.id"
                                class="rounded border border-[#003366]/12 bg-[#f8fafc] px-1 py-0.5 text-[10px] leading-tight text-[#333]"
                            >
                                {{ t.title }}
                            </li>
                        </ul>
                    </div>
                </template>
            </div>
        </template>
    </WorkflowSection>
</template>
