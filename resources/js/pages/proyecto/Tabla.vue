<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ProyectoTaskListPanel from '@/components/workflow/ProyectoTaskListPanel.vue';
import ProyectoWorkspaceTabs from '@/components/workflow/ProyectoWorkspaceTabs.vue';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { tabla } from '@/routes/proyecto';
import type {
    TaskListGroup,
    TaskListPerson,
    TaskListRow,
} from '@/types/proyectoTaskList';

type Proj = { id: number; name: string; code: string | null };

const props = defineProps<{
    project: (Proj & { status?: string }) | null;
    projects: Proj[];
    taskGroups: TaskListGroup[];
    tasks: TaskListRow[];
    statuses: string[];
    peopleOptions: TaskListPerson[];
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
    router.get(tabla.url({ query: { project_id: Number(projectId.value) } }));
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Lista de tareas', href: tabla() },
        ],
    },
});
</script>

<template>
    <Head title="Lista de tareas — proyecto" />

    <WorkflowSection
        context-label="Jefe de proyecto — ejecución"
        title="Lista de tareas (por proyecto)"
        description="Vista tipo Monday: segmentos agrupados, tareas editables en línea, responsable, colaboradores y fechas. Los cambios se guardan al instante; el detalle abre un panel para descripción y asignaciones múltiples."
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
                    class="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-800 shadow-sm focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
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
                active="tabla"
                :project="project"
                :projects="projects"
            />

            <ProyectoTaskListPanel
                :project="project"
                :task-groups="taskGroups"
                :tasks="tasks"
                :statuses="statuses"
                :people-options="peopleOptions"
            />
        </template>
    </WorkflowSection>
</template>
