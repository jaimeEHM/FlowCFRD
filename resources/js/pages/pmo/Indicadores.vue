<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { validacionAvances } from '@/routes/coordinacion';
import { dashboard } from '@/routes';
import { gantt, indicadores, proyectos, tableroMacro } from '@/routes/pmo';

defineProps<{
    usuarios_total: number;
    proyectos_total: number;
    tareas_total: number;
    tareas_urgentes_pendientes: number;
    projects_by_status: Record<string, number>;
    tasks_by_status: Record<string, number>;
}>();

const relatedLinks = [
    { title: 'Tablero macro', href: tableroMacro() },
    { title: 'Proyectos', href: proyectos() },
    { title: 'Gantt', href: gantt() },
    { title: 'Validación de avances', href: validacionAvances() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Indicadores (KPI)', href: indicadores() },
        ],
    },
});
</script>

<template>
    <Head title="Indicadores" />

    <WorkflowSection
        context-label="PMO — visión macro y seguimiento"
        title="Indicadores y KPI"
        description="Métricas agregadas en tiempo real desde la base de datos."
        :related-links="relatedLinks"
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                class="rounded-lg border border-[#003366]/15 bg-white p-4 shadow-sm"
            >
                <p class="text-xs font-medium uppercase text-[#666]">Usuarios</p>
                <p class="mt-1 text-2xl font-semibold text-[#003366]">
                    {{ usuarios_total }}
                </p>
            </div>
            <div
                class="rounded-lg border border-[#003366]/15 bg-white p-4 shadow-sm"
            >
                <p class="text-xs font-medium uppercase text-[#666]">
                    Proyectos
                </p>
                <p class="mt-1 text-2xl font-semibold text-[#003366]">
                    {{ proyectos_total }}
                </p>
            </div>
            <div
                class="rounded-lg border border-[#003366]/15 bg-white p-4 shadow-sm"
            >
                <p class="text-xs font-medium uppercase text-[#666]">Tareas</p>
                <p class="mt-1 text-2xl font-semibold text-[#003366]">
                    {{ tareas_total }}
                </p>
            </div>
            <div
                class="rounded-lg border border-[#003366]/15 bg-white p-4 shadow-sm"
            >
                <p class="text-xs font-medium uppercase text-[#666]">
                    Urgentes pendientes validación
                </p>
                <p class="mt-1 text-2xl font-semibold text-[#F1C400]">
                    {{ tareas_urgentes_pendientes }}
                </p>
            </div>
        </div>
        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div
                class="rounded-lg border border-[#003366]/15 bg-[#f8fafc] p-4 text-sm"
            >
                <h3 class="font-semibold text-[#003366]">Proyectos por estado</h3>
                <ul class="mt-2 space-y-1 text-[#333]">
                    <li
                        v-for="(c, st) in projects_by_status"
                        :key="st"
                        class="flex justify-between"
                    >
                        <span class="capitalize">{{ st }}</span>
                        <span class="tabular-nums font-medium">{{ c }}</span>
                    </li>
                    <li
                        v-if="Object.keys(projects_by_status).length === 0"
                        class="text-[#666]"
                    >
                        Sin datos
                    </li>
                </ul>
            </div>
            <div
                class="rounded-lg border border-[#003366]/15 bg-[#f8fafc] p-4 text-sm"
            >
                <h3 class="font-semibold text-[#003366]">Tareas por estado</h3>
                <ul class="mt-2 space-y-1 text-[#333]">
                    <li
                        v-for="(c, st) in tasks_by_status"
                        :key="st"
                        class="flex justify-between"
                    >
                        <span class="capitalize">{{ String(st).replace('_', ' ') }}</span>
                        <span class="tabular-nums font-medium">{{ c }}</span>
                    </li>
                    <li
                        v-if="Object.keys(tasks_by_status).length === 0"
                        class="text-[#666]"
                    >
                        Sin datos
                    </li>
                </ul>
            </div>
        </div>
    </WorkflowSection>
</template>
