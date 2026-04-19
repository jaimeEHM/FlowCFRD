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
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

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
    people: { id: number; name: string; total: number }[];
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
    summary: { people_count: number; tasks_open_assigned: number };
};

const props = defineProps<{
    workload: WorkloadPayload;
    /** true = cartera sin proyecto enfocado */
    portfolioScope: boolean;
    projectName?: string | null;
}>();

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
                class="mt-3 grid gap-3 sm:grid-cols-3"
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
            </div>
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
                </div>
                <div class="overflow-x-auto p-3">
                    <table
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
                                    class="min-w-[3rem] bg-slate-100 px-2 py-2 text-center font-semibold text-slate-700"
                                >
                                    Σ
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(p, i) in workload.people"
                                :key="p.id"
                                class="border-b border-[#003366]/8"
                            >
                                <td
                                    class="sticky left-0 z-10 max-w-[14rem] truncate bg-white px-2 py-1.5 font-medium text-[#1e293b]"
                                    :title="p.name"
                                >
                                    {{ p.name }}
                                </td>
                                <td
                                    v-for="(cell, j) in workload.matrix[i] ?? []"
                                    :key="j"
                                    class="px-1 py-1 text-center tabular-nums"
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
                                    class="bg-slate-50 px-2 py-1.5 text-center font-semibold tabular-nums text-[#003366]"
                                >
                                    {{ p.total }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>
