<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { gantt, indicadores, proyectos, tableroMacro } from '@/routes/pmo';

type Row = {
    id: number;
    name: string;
    code: string | null;
    starts_at: string | null;
    ends_at: string | null;
    status: string;
};

defineProps<{
    projects: Row[];
}>();

const relatedLinks = [
    { title: 'Tablero macro', href: tableroMacro() },
    { title: 'Proyectos', href: proyectos() },
    { title: 'Indicadores (KPI)', href: indicadores() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Cronograma (Gantt)', href: gantt() },
        ],
    },
});
</script>

<template>
    <Head title="Gantt" />

    <WorkflowSection
        context-label="PMO — visión macro y seguimiento"
        title="Cronograma (Gantt)"
        description="Proyectos con fechas de inicio y fin. Vista previa antes de integrar librería de Gantt."
        :related-links="relatedLinks"
    >
        <div
            class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white shadow-sm"
        >
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead
                    class="border-b border-[#003366]/15 bg-[#f8fafc] text-xs font-semibold uppercase text-[#003366]"
                >
                    <tr>
                        <th class="px-4 py-3">Proyecto</th>
                        <th class="px-4 py-3">Inicio</th>
                        <th class="px-4 py-3">Fin</th>
                        <th class="px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#003366]/10">
                    <tr v-for="p in projects" :key="p.id">
                        <td class="px-4 py-3 font-medium">{{ p.name }}</td>
                        <td class="px-4 py-3 text-[#666]">{{ p.starts_at ?? '—' }}</td>
                        <td class="px-4 py-3 text-[#666]">{{ p.ends_at ?? '—' }}</td>
                        <td class="px-4 py-3 capitalize">{{ p.status }}</td>
                    </tr>
                    <tr v-if="projects.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-[#666]">
                            No hay proyectos con fechas. Define inicio/fin en
                            proyectos.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </WorkflowSection>
</template>
