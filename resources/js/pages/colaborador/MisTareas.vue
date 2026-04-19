<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { misTareas } from '@/routes/colaborador';
import { dashboard } from '@/routes';

type Task = {
    id: number;
    title: string;
    status: string;
    due_date: string | null;
    is_urgent: boolean;
    project: { name: string; code: string | null };
};

defineProps<{
    tasks: Task[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Mis tareas', href: misTareas() },
        ],
    },
});
</script>

<template>
    <Head title="Mis tareas" />

    <WorkflowSection
        context-label="Colaborador — trabajo diario (web)"
        title="Mis tareas"
        description="Tareas donde figuras como responsable."
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
                        <th class="px-4 py-2 text-left">Vence</th>
                        <th class="px-4 py-2 text-left">Urgente</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="t in tasks" :key="t.id">
                        <td class="px-4 py-2 font-medium">{{ t.title }}</td>
                        <td class="px-4 py-2 text-[#666]">{{ t.project.name }}</td>
                        <td class="px-4 py-2 capitalize">{{ t.status }}</td>
                        <td class="px-4 py-2 text-[#666]">{{ t.due_date ?? '—' }}</td>
                        <td class="px-4 py-2">{{ t.is_urgent ? 'Sí' : 'No' }}</td>
                    </tr>
                    <tr v-if="tasks.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-[#666]">
                            No tienes tareas asignadas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </WorkflowSection>
</template>
