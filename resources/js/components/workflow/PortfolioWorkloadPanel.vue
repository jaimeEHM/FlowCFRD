<script setup lang="ts">
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Title,
    Tooltip,
    type ChartData,
} from 'chart.js';
import { Eye } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
);

export type WorkloadPayload = {
    mode: string;
    people: { id: number; name: string; total: number; projects_count: number; max_daily_load: number; max_parallel_projects: number }[];
    stacks: { label: string; sub?: string | null }[];
    matrix: number[][];
    heatmap_max: number;
    chart: {
        labels: string[];
        datasets: {
            label: string;
            data: number[];
            backgroundColor: string;
        }[];
    };
    summary: { people_count: number; tasks_open_assigned: number; daily_alerts: number; parallel_alerts: number };
    alerts: {
        daily: {
            user_id: number;
            name: string;
            peak_date: string;
            max_daily_load: number;
            capacity_per_day: number;
            over_capacity_days: number;
            severity: 'normal' | 'alerta' | 'peligro';
        }[];
        parallel: {
            user_id: number;
            name: string;
            peak_date: string;
            max_parallel_projects: number;
            projects_at_peak: string[];
            severity: 'normal' | 'alerta' | 'peligro';
        }[];
    };
};

const props = defineProps<{
    workload: WorkloadPayload;
    /** true = cartera sin proyecto enfocado */
    portfolioScope: boolean;
    projectName?: string | null;
    thresholds: {
        tasks_per_day: number;
        alert_days: number;
        danger_days: number;
        overload_days: number;
    };
}>();

type WorkloadView = 'heatmap' | 'segments';
const currentView = ref<WorkloadView>('heatmap');
const personDetailOpen = ref(false);
const selectedPersonId = ref<number | null>(null);
const compactHeatmap = ref(false);

const chartHeightPx = computed(() => {
    const n = props.workload.people.length;
    if (n <= 0) {
        return 280;
    }
    return Math.min(720, Math.max(320, 56 + n * 42));
});

const barChartData = computed<ChartData<'bar'>>(
    () =>
        ({
            labels: props.workload.chart.labels,
            datasets: props.workload.chart.datasets.map((d) => ({
                label: d.label,
                data: d.data,
                backgroundColor: d.backgroundColor,
                borderRadius: 5,
                borderSkipped: false,
            })),
        }) as ChartData<'bar'>,
);

const isPortfolioLarge = computed(() => props.portfolioScope && props.workload.stacks.length >= 12);

if (isPortfolioLarge.value) {
    compactHeatmap.value = true;
}

const selectedPerson = computed(() => props.workload.people.find((p) => p.id === selectedPersonId.value) ?? null);

function topProjectsForPerson(personId: number): { label: string; sub: string | null; count: number }[] {
    const personIndex = props.workload.people.findIndex((p) => p.id === personId);
    if (personIndex < 0) {
        return [];
    }
    const row = props.workload.matrix[personIndex] ?? [];
    const out = row
        .map((count, idx) => ({
            label: props.workload.stacks[idx]?.label ?? `Segmento ${idx + 1}`,
            sub: props.workload.stacks[idx]?.sub ?? null,
            count,
        }))
        .filter((x) => x.count > 0)
        .sort((a, b) => b.count - a.count);

    return out.slice(0, 12);
}

const selectedPersonProjects = computed(() =>
    selectedPerson.value !== null ? topProjectsForPerson(selectedPerson.value.id) : [],
);

function openPersonDetail(personId: number): void {
    selectedPersonId.value = personId;
    personDetailOpen.value = true;
}

const barOptions = {
    indexAxis: 'y' as const,
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                boxWidth: 10,
                font: { size: 11 },
            },
        },
        tooltip: {
            mode: 'index' as const,
            intersect: false,
        },
    },
    scales: {
        x: {
            stacked: true,
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: 'rgba(0, 51, 102, 0.06)' },
        },
        y: {
            stacked: true,
            ticks: {
                font: { size: 11 },
                autoSkip: false,
            },
            grid: { display: false },
        },
    },
};

function heatBg(n: number, max: number): string {
    if (n <= 0) {
        return 'rgb(248 250 252)';
    }
    const t = Math.min(1, n / max);
    const opacity = 0.12 + t * 0.82;
    return `rgba(0, 51, 102, ${opacity})`;
}

function heatTextClass(n: number, max: number): string {
    if (n <= 0) {
        return 'text-slate-400';
    }
    const t = n / max;
    return t > 0.55 ? 'text-white font-semibold' : 'text-[#0f172a] font-medium';
}

function estimatedDays(totalTasks: number): number {
    const capacity = Math.max(1, props.thresholds.tasks_per_day || 1);
    return totalTasks / capacity;
}

function workloadLevel(totalTasks: number): 'normal' | 'alerta' | 'peligro' | 'sobrecarga' {
    const days = estimatedDays(totalTasks);
    if (days >= props.thresholds.overload_days) {
        return 'sobrecarga';
    }
    if (days >= props.thresholds.danger_days) {
        return 'peligro';
    }
    if (days >= props.thresholds.alert_days) {
        return 'alerta';
    }
    return 'normal';
}

function rowClass(totalTasks: number): string {
    const level = workloadLevel(totalTasks);
    if (level === 'sobrecarga') {
        return 'bg-[#d21428]/10';
    }
    if (level === 'peligro') {
        return 'bg-[#e69b0a]/18';
    }
    if (level === 'alerta') {
        return 'bg-[#e69b0a]/10';
    }
    return '';
}

function rowCellClass(totalTasks: number): string {
    const level = workloadLevel(totalTasks);
    if (level === 'sobrecarga') {
        return 'bg-[#d21428]/10';
    }
    if (level === 'peligro') {
        return 'bg-[#e69b0a]/18';
    }
    if (level === 'alerta') {
        return 'bg-[#e69b0a]/10';
    }
    return 'bg-white';
}

function rowCellStyle(totalTasks: number): Record<string, string> {
    const level = workloadLevel(totalTasks);
    if (level === 'sobrecarga') {
        return { backgroundColor: 'rgba(210, 20, 40, 0.14)' };
    }
    if (level === 'peligro') {
        return { backgroundColor: 'rgba(230, 155, 10, 0.22)' };
    }
    if (level === 'alerta') {
        return { backgroundColor: 'rgba(230, 155, 10, 0.14)' };
    }
    return { backgroundColor: '#ffffff' };
}
</script>

<template>
    <div class="space-y-8">
        <div
            class="rounded-xl border border-[#003366]/12 bg-gradient-to-br from-[#f8fafc] to-white p-4 shadow-[0_1px_3px_rgba(0,51,102,0.08)] sm:p-5"
        >
            <p class="text-sm font-semibold text-[#003366]">
                Resumen
            </p>
            <div
                class="mt-3 grid gap-3 sm:grid-cols-4"
            >
                <div
                    class="rounded-lg border border-[#003366]/10 bg-white/90 px-4 py-3 text-center shadow-sm"
                >
                    <p
                        class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"
                    >
                        Personas con carga
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold tabular-nums text-[#003366]"
                    >
                        {{ workload.summary.people_count }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#003366]/10 bg-white/90 px-4 py-3 text-center shadow-sm"
                >
                    <p
                        class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"
                    >
                        Tareas abiertas (como responsable)
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold tabular-nums text-[#1e5a8e]"
                    >
                        {{ workload.summary.tasks_open_assigned }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#003366]/10 bg-white/90 px-4 py-3 text-center shadow-sm"
                >
                    <p
                        class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"
                    >
                        Proyectos considerados
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold tabular-nums text-[#003366]"
                    >
                        {{ workload.stacks.length }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#F1C400]/40 bg-amber-50/80 px-4 py-3 text-center shadow-sm"
                >
                    <p
                        class="text-[10px] font-semibold uppercase tracking-wide text-amber-950/80"
                    >
                        Lectura
                    </p>
                    <p class="mt-1 text-xs leading-snug text-amber-950/90">
                        Barras apiladas = reparto por
                        {{
                            portfolioScope ? 'proyecto' : 'estado Kanban'
                        }}; el mapa de calor repite la misma matriz con intensidad.
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#e69b0a]/30 bg-amber-50/80 px-4 py-3 text-center shadow-sm"
                >
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-950/80">
                        Alertas diarias
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-amber-900">
                        {{ workload.summary.daily_alerts }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#d21428]/30 bg-rose-50/80 px-4 py-3 text-center shadow-sm"
                >
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-rose-950/80">
                        Proyectos en paralelo
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-rose-900">
                        {{ workload.summary.parallel_alerts }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="workload.alerts.daily.length > 0 || workload.alerts.parallel.length > 0"
            class="rounded-xl border border-[#003366]/12 bg-white p-4 shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
        >
            <p class="text-sm font-semibold text-[#003366]">Alertas operativas por fecha y asignación</p>
            <p class="mt-1 text-xs text-slate-600">
                Se calculan cruzando fechas de proyectos con tareas asignadas (responsable + colaboraciones ponderadas) para estimar carga diaria y solapamiento.
            </p>
            <div class="mt-3 grid gap-3 lg:grid-cols-2">
                <div class="rounded-lg border border-[#e69b0a]/25 bg-amber-50/60 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-900">Carga diaria estimada</p>
                    <ul class="mt-2 space-y-1 text-xs text-amber-950">
                        <li v-for="a in workload.alerts.daily.slice(0, 6)" :key="`daily-${a.user_id}`">
                            <span class="font-semibold">{{ a.name }}</span> · {{ a.max_daily_load.toFixed(2) }}/{{ a.capacity_per_day }} tareas/día · pico {{ a.peak_date }}
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-[#d21428]/25 bg-rose-50/60 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-900">Proyectos en paralelo</p>
                    <ul class="mt-2 space-y-1 text-xs text-rose-950">
                        <li v-for="a in workload.alerts.parallel.slice(0, 6)" :key="`parallel-${a.user_id}`">
                            <span class="font-semibold">{{ a.name }}</span> · {{ a.max_parallel_projects }} proyectos simultáneos · pico {{ a.peak_date }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="inline-flex overflow-hidden rounded-md border border-[#223c6a]/20 bg-[#f1f5f9]">
            <button
                type="button"
                class="px-4 py-2 text-xs font-semibold uppercase tracking-wide transition"
                :class="
                    currentView === 'heatmap'
                        ? 'bg-[#e69b0a]/20 text-[#223c6a] border-b-2 border-[#223c6a]'
                        : 'text-[#223c6a]/80 hover:bg-[#223c6a]/5'
                "
                @click="currentView = 'heatmap'"
            >
                Mapa de calor por persona
            </button>
            <button
                type="button"
                class="px-4 py-2 text-xs font-semibold uppercase tracking-wide transition"
                :class="
                    currentView === 'segments'
                        ? 'bg-[#e69b0a]/20 text-[#223c6a] border-b-2 border-[#223c6a]'
                        : 'text-[#223c6a]/80 hover:bg-[#223c6a]/5'
                "
                @click="currentView = 'segments'"
            >
                Distribución por segmentos
            </button>
        </div>

        <div
            v-if="workload.people.length === 0"
            class="rounded-xl border border-dashed border-[#003366]/25 bg-[#fafbfc] px-6 py-14 text-center text-sm text-slate-600"
        >
            <p class="font-medium text-[#003366]">
                Sin tareas abiertas con responsable asignado
            </p>
            <p class="mt-2 max-w-md mx-auto text-xs text-slate-600">
                Solo se cuentan tareas no «hechas» con persona responsable. Las
                tareas solo como colaborador no aparecen aquí.
            </p>
        </div>

        <template v-else>
            <div
                v-if="currentView === 'segments'"
                class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
            >
                <div
                    class="border-b border-[#003366]/15 bg-[#f1f5f9] px-4 py-3 shadow-sm"
                >
                    <h3
                        class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                    >
                        Distribución de carga (barras apiladas)
                    </h3>
                    <p class="mt-1 text-[11px] leading-snug text-slate-600">
                        Cada fila es una persona; el largo total es su carga.
                        {{
                            portfolioScope
                                ? 'Los colores son proyectos distintos.'
                                : 'Los colores son estados de tarea en el proyecto.'
                        }}
                    </p>
                </div>
                <div
                    class="p-3"
                    :style="{ height: `${chartHeightPx}px` }"
                >
                    <Bar
                        :data="barChartData"
                        :options="barOptions"
                    />
                </div>
            </div>

            <div
                v-else
                class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
            >
                <div
                    class="border-b border-[#003366]/15 bg-[#f1f5f9] px-4 py-3 shadow-sm"
                >
                    <h3
                        class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                    >
                        Mapa de calor (persona ×
                        {{ portfolioScope ? 'proyecto' : 'estado' }})
                    </h3>
                    <p class="mt-1 text-[11px] leading-snug text-slate-600">
                        Intensidad UdeC (#003366): más oscuro = más tareas en esa
                        celda. Útil para ver cuellos de botella y focos de
                        trabajo cruzado.
                    </p>
                    <div
                        v-if="isPortfolioLarge"
                        class="mt-2 flex items-center justify-between gap-2"
                    >
                        <p class="text-[11px] text-slate-600">
                            Cartera amplia detectada ({{ workload.stacks.length }} proyectos): se recomienda la vista resumida por persona.
                        </p>
                        <button
                            type="button"
                            class="rounded-md border border-[#003366]/20 bg-white px-2 py-1 text-[11px] font-semibold text-[#003366] hover:bg-[#003366]/5"
                            @click="compactHeatmap = !compactHeatmap"
                        >
                            {{ compactHeatmap ? 'Ver matriz completa' : 'Ver vista resumida' }}
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto p-3">
                    <div class="mb-2 flex flex-wrap gap-2 text-[11px] text-slate-700">
                        <span class="rounded border border-[#e69b0a]/40 bg-[#e69b0a]/10 px-2 py-1">Alerta ≥ {{ props.thresholds.alert_days }} días</span>
                        <span class="rounded border border-[#e69b0a]/45 bg-[#e69b0a]/18 px-2 py-1">Peligro ≥ {{ props.thresholds.danger_days }} días</span>
                        <span class="rounded border border-[#d21428]/40 bg-[#d21428]/10 px-2 py-1">Sobrecarga ≥ {{ props.thresholds.overload_days }} días</span>
                    </div>
                    <table
                        v-if="!compactHeatmap"
                        class="w-full min-w-[640px] border-collapse text-left text-xs"
                    >
                        <thead>
                            <tr class="border-b border-[#003366]/15">
                                <th
                                    class="sticky left-0 z-10 min-w-[10rem] bg-[#f1f5f9] px-2 py-2 font-semibold text-[#003366]"
                                >
                                    Persona
                                </th>
                                <th
                                    v-for="(s, j) in workload.stacks"
                                    :key="j"
                                    class="min-w-[5.5rem] px-1.5 py-2 text-center font-semibold capitalize text-[#003366]"
                                >
                                    <span class="line-clamp-2">{{
                                        s.label
                                    }}</span>
                                    <span
                                        v-if="s.sub"
                                        class="mt-0.5 block font-mono text-[10px] font-normal text-slate-500"
                                        >{{ s.sub }}</span
                                    >
                                </th>
                                <th
                                    class="min-w-[4.5rem] bg-slate-100 px-2 py-2 text-center font-semibold text-slate-700"
                                >
                                    Proy.
                                </th>
                                <th
                                    class="min-w-[3rem] bg-slate-100 px-2 py-2 text-center font-semibold text-slate-700"
                                >
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(p, i) in workload.people"
                                :key="p.id"
                                class="border-b border-[#003366]/8"
                                :class="rowClass(p.total)"
                            >
                                <td
                                    class="sticky left-0 z-10 max-w-[14rem] truncate px-2 py-1.5 font-medium text-[#1e293b]"
                                    :class="rowCellClass(p.total)"
                                    :style="rowCellStyle(p.total)"
                                    :title="p.name"
                                >
                                    {{ p.name }}
                                </td>
                                <td
                                    v-for="(cell, j) in workload.matrix[i] ?? []"
                                    :key="j"
                                    class="px-1 py-1 text-center tabular-nums"
                                    :class="rowCellClass(p.total)"
                                    :style="rowCellStyle(p.total)"
                                >
                                    <span
                                        class="inline-flex min-h-[1.75rem] min-w-[2rem] items-center justify-center rounded-md px-1"
                                        :style="{
                                            backgroundColor: heatBg(
                                                cell,
                                                workload.heatmap_max,
                                            ),
                                        }"
                                        :class="
                                            heatTextClass(
                                                cell,
                                                workload.heatmap_max,
                                            )
                                        "
                                    >
                                        {{ cell > 0 ? cell : '·' }}
                                    </span>
                                </td>
                                <td
                                    class="px-2 py-1.5 text-center font-semibold tabular-nums text-[#003366]"
                                    :class="rowCellClass(p.total)"
                                    :style="rowCellStyle(p.total)"
                                >
                                    {{ p.projects_count }}
                                </td>
                                <td
                                    class="px-2 py-1.5 text-center font-semibold tabular-nums text-[#003366]"
                                    :class="rowCellClass(p.total)"
                                    :style="rowCellStyle(p.total)"
                                >
                                    {{ p.total }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table
                        v-else
                        class="w-full min-w-[760px] border-collapse text-left text-xs"
                    >
                        <thead>
                            <tr class="border-b border-[#003366]/15">
                                <th class="px-2 py-2 font-semibold text-[#003366]">Persona</th>
                                <th class="px-2 py-2 text-center font-semibold text-[#003366]">Proy.</th>
                                <th class="px-2 py-2 text-center font-semibold text-[#003366]">Total</th>
                                <th class="px-2 py-2 text-center font-semibold text-[#003366]">Pico diario</th>
                                <th class="px-2 py-2 text-center font-semibold text-[#003366]">Paralelos</th>
                                <th class="px-2 py-2 text-center font-semibold text-[#003366]">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="p in workload.people"
                                :key="`compact-${p.id}`"
                                class="border-b border-[#003366]/8"
                                :class="rowClass(p.total)"
                            >
                                <td class="px-2 py-1.5 font-medium text-[#1e293b]">{{ p.name }}</td>
                                <td class="px-2 py-1.5 text-center font-semibold tabular-nums text-[#003366]">{{ p.projects_count }}</td>
                                <td class="px-2 py-1.5 text-center font-semibold tabular-nums text-[#003366]">{{ p.total }}</td>
                                <td class="px-2 py-1.5 text-center tabular-nums text-slate-700">{{ p.max_daily_load.toFixed(2) }}</td>
                                <td class="px-2 py-1.5 text-center tabular-nums text-slate-700">{{ p.max_parallel_projects }}</td>
                                <td class="px-2 py-1.5 text-center">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-md border border-[#003366]/25 p-1.5 text-[#003366] transition hover:bg-[#003366]/8"
                                        title="Ver resumen de proyectos por persona"
                                        @click="openPersonDetail(p.id)"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <Dialog v-model:open="personDetailOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Resumen por persona</DialogTitle>
                    <DialogDescription>
                        {{ selectedPerson ? `Detalle de proyectos para ${selectedPerson.name}.` : 'Selecciona una persona.' }}
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                    <div
                        v-if="selectedPerson"
                        class="grid grid-cols-3 gap-2 rounded-md border border-[#003366]/10 bg-[#f8fafc] p-3 text-xs"
                    >
                        <div>
                            <p class="text-slate-500">Total tareas</p>
                            <p class="font-semibold text-[#003366]">{{ selectedPerson.total }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Proyectos</p>
                            <p class="font-semibold text-[#003366]">{{ selectedPerson.projects_count }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Pico diario</p>
                            <p class="font-semibold text-[#003366]">{{ selectedPerson.max_daily_load.toFixed(2) }}</p>
                        </div>
                    </div>
                    <div class="max-h-72 overflow-y-auto rounded-md border border-[#003366]/10">
                        <table class="w-full text-left text-xs">
                            <thead class="sticky top-0 bg-[#f1f5f9]">
                                <tr class="border-b border-[#003366]/10 text-[#003366]">
                                    <th class="px-3 py-2">Proyecto</th>
                                    <th class="px-3 py-2">Código</th>
                                    <th class="px-3 py-2 text-right">Carga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in selectedPersonProjects"
                                    :key="`${item.label}-${item.sub}`"
                                    class="border-b border-[#003366]/8"
                                >
                                    <td class="px-3 py-2">{{ item.label }}</td>
                                    <td class="px-3 py-2 font-mono text-[11px] text-slate-600">{{ item.sub ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right font-semibold tabular-nums text-[#003366]">{{ item.count }}</td>
                                </tr>
                                <tr v-if="selectedPersonProjects.length === 0">
                                    <td colspan="3" class="px-3 py-5 text-center text-slate-500">
                                        Sin carga distribuida por proyecto para esta persona.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="personDetailOpen = false">Cerrar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
