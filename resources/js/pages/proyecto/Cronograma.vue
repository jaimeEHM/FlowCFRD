<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ProjectGanttChart from '@/components/workflow/ProjectGanttChart.vue';
import ProyectoWorkspaceTabs from '@/components/workflow/ProyectoWorkspaceTabs.vue';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { cronograma } from '@/routes/proyecto';
type GanttRow = {
    id: number;
    name: string;
    starts_at: string | null;
    ends_at: string | null;
    status: string;
    project_id: number;
    project_name: string;
    project_code: string | null;
    task_title: string;
};

type Proj = { id: number; name: string; code: string | null };

const props = defineProps<{
    project: Proj | null;
    projects: Proj[];
    ganttProjects: GanttRow[];
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
        cronograma.url({ query: { project_id: Number(projectId.value) } }),
    );
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Cronograma del proyecto', href: cronograma() },
        ],
    },
});
</script>

<template>
    <Head title="Cronograma — proyecto" />

    <WorkflowSection
        context-label="Jefe de proyecto — ejecución"
        title="Cronograma (Gantt) del proyecto"
        description="Cronograma de tareas del proyecto (fechas a partir de la fecha límite o de la alta de cada tarea)."
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
                active="cronograma"
                :project="project"
                :projects="projects"
            />

            <ProjectGanttChart
                class="mb-6"
                :projects="ganttProjects"
                empty-hint="No hay tareas en este proyecto. La vista detallada por tarea está en Lista de tareas."
            />
        </template>
    </WorkflowSection>
</template>
