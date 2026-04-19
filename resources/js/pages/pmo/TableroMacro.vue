<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { gantt, indicadores, proyectos, tableroMacro } from '@/routes/pmo';
import { kanban } from '@/routes/proyecto';

type Project = {
    id: number;
    name: string;
    code: string | null;
    status: string;
    tasks_abiertas: number;
    jefe_proyecto: { name: string } | null;
};

defineProps<{
    projects: Project[];
}>();

const relatedLinks = [
    { title: 'Proyectos', href: proyectos() },
    { title: 'Indicadores (KPI)', href: indicadores() },
    { title: 'Gantt', href: gantt() },
    { title: 'Kanban', href: kanban() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Tablero macro', href: tableroMacro() },
        ],
    },
});
</script>

<template>
    <Head title="Tablero macro" />

    <WorkflowSection
        context-label="PMO — visión macro y seguimiento"
        title="Tablero macro (estilo Monday)"
        description="Resumen de iniciativas y tareas abiertas por proyecto. Los datos provienen de la base de datos."
        :related-links="relatedLinks"
    >
        <div
            class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white shadow-sm"
        >
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead
                    class="border-b border-[#003366]/15 bg-[#f8fafc] text-xs font-semibold uppercase tracking-wide text-[#003366]"
                >
                    <tr>
                        <th class="px-4 py-3">Proyecto</th>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Jefe de proyecto</th>
                        <th class="px-4 py-3 text-right">Tareas abiertas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#003366]/10 text-[#333]">
                    <tr v-for="p in projects" :key="p.id">
                        <td class="px-4 py-3 font-medium">{{ p.name }}</td>
                        <td class="px-4 py-3 text-[#666]">
                            {{ p.code ?? '—' }}
                        </td>
                        <td class="px-4 py-3 capitalize">{{ p.status }}</td>
                        <td class="px-4 py-3">
                            {{ p.jefe_proyecto?.name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ p.tasks_abiertas }}
                        </td>
                    </tr>
                    <tr v-if="projects.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-[#666]"
                        >
                            No hay proyectos todavía. Crea uno en «Proyectos».
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </WorkflowSection>
</template>
