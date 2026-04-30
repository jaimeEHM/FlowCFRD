<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ProyectoKanbanBoard from '@/components/workflow/ProyectoKanbanBoard.vue';
import ProyectoWorkspaceTabs from '@/components/workflow/ProyectoWorkspaceTabs.vue';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { kanban as kanbanPage } from '@/routes/proyecto';

type Person = { id: number; name: string; avatar?: string | null };

type Ttask = {
    id: number;
    title: string;
    description: string | null;
    assignee: Person | null;
    collaborators: Person[];
};

type GroupPayload = {
    id: number;
    name: string;
    color: string;
    position: number;
    columns: Record<string, Ttask[]>;
    progress_percent: number;
};

type Proj = { id: number; name: string; code: string | null };
type StatusOption = {
    id: number | null;
    key: string;
    label: string;
    is_system: boolean;
    is_transversal: boolean;
};

const props = defineProps<{
    project: Proj | null;
    projects: Proj[];
    groups?: GroupPayload[] | null;
    statuses?: string[] | null;
    statusOptions?: StatusOption[] | null;
    peopleOptions: Person[];
    focusedTaskId?: number | null;
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
        kanbanPage.url({ query: { project_id: Number(projectId.value) } }),
    );
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Kanban', href: kanbanPage() },
        ],
    },
});
</script>

<template>
    <Head title="Kanban" />

    <WorkflowSection
        context-label="Jefe de proyecto — ejecución"
        title="Kanban por proyecto"
        description="Segmentos (como Monday), arrastre entre columnas y segmentos con guardado automático, personas responsable + colaboradores, y detalle de tarea en el modal."
    >
        <div
            v-if="!project"
            class="rounded-lg border border-dashed border-[#003366]/30 p-8 text-center text-[#666]"
        >
            No hay proyectos. Crea uno en PMO → Proyectos.
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
                active="kanban"
                :project="project"
                :projects="projects"
            />

            <ProyectoKanbanBoard
                :project="project"
                :groups="groups"
                :statuses="statuses"
                :status-options="statusOptions ?? []"
                :people-options="peopleOptions"
                :focused-task-id="focusedTaskId ?? null"
                :portfolio-mode="false"
                id-suffix="proyecto"
            />
        </template>
    </WorkflowSection>
</template>
