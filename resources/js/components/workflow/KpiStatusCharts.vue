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
import { computed, withDefaults } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = withDefaults(
    defineProps<{
        projectsByStatus: Record<string, number>;
        tasksByStatus: Record<string, number>;
        tasksAbiertasPorResponsable: Record<string, number>;
        /** Un solo proyecto enfocado: títulos y etiquetas del primer gráfico. */
        projectScope?: boolean;
    }>(),
    { projectScope: false },
);

const palette = ['#003366', '#1e4a7a', '#2d6a9f', '#F1C400', '#94a3b8', '#64748b'];

function barData(
    title: string,
    data: Record<string, number>,
): ChartData<'bar'> {
    const labels = Object.keys(data).map((k) =>
        String(k).replaceAll('_', ' '),
    );
    const values = Object.values(data);

    return {
        labels,
        datasets: [
            {
                label: title,
                data: values,
                backgroundColor: labels.map(
                    (_, i) => palette[i % palette.length],
                ),
                borderRadius: 6,
            },
        ],
    };
}

const chartProjects = computed(() =>
    barData(
        props.projectScope ? 'Este proyecto' : 'Proyectos',
        props.projectsByStatus,
    ),
);
const chartTasks = computed(() =>
    barData(props.projectScope ? 'Tareas del proyecto' : 'Tareas', props.tasksByStatus),
);

const chartWorkload = computed(() =>
    barData(
        props.projectScope ? 'Tareas abiertas (proyecto)' : 'Tareas abiertas',
        props.tasksAbiertasPorResponsable,
    ),
);

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        x: {
            ticks: { maxRotation: 45, minRotation: 0 },
        },
        y: {
            beginAtZero: true,
            ticks: { precision: 0 },
        },
    },
};

const barOptionsHorizontal = {
    indexAxis: 'y' as const,
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        x: {
            beginAtZero: true,
            ticks: { precision: 0 },
        },
        y: {
            ticks: { maxRotation: 0 },
        },
    },
};
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <div
            class="flex flex-col overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
        >
            <div
                class="border-b border-[#003366]/15 bg-[#f1f5f9] px-3 py-2.5 shadow-sm"
            >
                <h3
                    class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                >
                    {{
                        projectScope
                            ? 'Estado del proyecto'
                            : 'Proyectos por estado'
                    }}
                </h3>
            </div>
            <div class="h-60 p-3">
                <Bar
                    v-if="Object.keys(projectsByStatus).length > 0"
                    :data="chartProjects"
                    :options="barOptions"
                />
                <p
                    v-else
                    class="flex h-full items-center justify-center text-sm text-slate-500"
                >
                    Sin datos
                </p>
            </div>
        </div>
        <div
            class="flex flex-col overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
        >
            <div
                class="border-b border-[#003366]/15 bg-[#f1f5f9] px-3 py-2.5 shadow-sm"
            >
                <h3
                    class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                >
                    {{
                        projectScope
                            ? 'Tareas por estado (proyecto)'
                            : 'Tareas por estado'
                    }}
                </h3>
            </div>
            <div class="h-60 p-3">
                <Bar
                    v-if="Object.keys(tasksByStatus).length > 0"
                    :data="chartTasks"
                    :options="barOptions"
                />
                <p
                    v-else
                    class="flex h-full items-center justify-center text-sm text-slate-500"
                >
                    Sin datos
                </p>
            </div>
        </div>
        <div
            class="flex flex-col overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)] lg:col-span-2"
        >
            <div
                class="border-b border-[#003366]/15 bg-[#f1f5f9] px-3 py-2.5 shadow-sm"
            >
                <h3
                    class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
                >
                    {{
                        projectScope
                            ? 'Carga por responsable en el proyecto'
                            : 'Carga por responsable (tareas no hechas)'
                    }}
                </h3>
                <p class="mt-0.5 text-[11px] leading-snug text-slate-600">
                    {{
                        projectScope
                            ? 'Solo tareas abiertas de este proyecto con persona asignada.'
                            : 'Barras horizontales por persona asignada (mapa de trabajo).'
                    }}
                </p>
            </div>
            <div class="h-72 p-3">
                <Bar
                    v-if="
                        Object.keys(tasksAbiertasPorResponsable).length > 0
                    "
                    :data="chartWorkload"
                    :options="barOptionsHorizontal"
                />
                <p
                    v-else
                    class="flex h-full items-center justify-center text-sm text-slate-500"
                >
                    Sin tareas abiertas con responsable
                </p>
            </div>
        </div>
    </div>
</template>
