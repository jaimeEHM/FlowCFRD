<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { validacionAvances } from '@/routes/coordinacion';
import { misTareas, urgentes } from '@/routes/colaborador';
import { dashboard } from '@/routes';

type Task = {
    id: number;
    title: string;
    status: string;
    validation_status: string | null;
    project: { name: string };
};

defineProps<{
    tasks: Task[];
}>();

const relatedLinks = [
    { title: 'Mis tareas', href: misTareas() },
    { title: 'Validación de avances', href: validacionAvances() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Urgentes', href: urgentes() },
        ],
    },
});
</script>

<template>
    <Head title="Urgentes" />

    <WorkflowSection
        context-label="Colaborador — trabajo diario (web)"
        title="Tareas urgentes / imprevistas"
        description="Tareas marcadas como urgentes a tu cargo."
        :related-links="relatedLinks"
    >
        <div
            class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white shadow-sm"
        >
            <table class="w-full min-w-[560px] text-sm">
                <thead
                    class="bg-[#f8fafc] text-xs font-semibold uppercase text-[#003366]"
                >
                    <tr>
                        <th class="px-4 py-2 text-left">Tarea</th>
                        <th class="px-4 py-2 text-left">Proyecto</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                        <th class="px-4 py-2 text-left">Validación</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="t in tasks" :key="t.id">
                        <td class="px-4 py-2 font-medium">{{ t.title }}</td>
                        <td class="px-4 py-2">{{ t.project.name }}</td>
                        <td class="px-4 py-2 capitalize">{{ t.status }}</td>
                        <td class="px-4 py-2 text-[#666]">
                            {{ t.validation_status ?? '—' }}
                        </td>
                    </tr>
                    <tr v-if="tasks.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-[#666]">
                            No hay tareas urgentes asignadas a tu usuario.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </WorkflowSection>
</template>
