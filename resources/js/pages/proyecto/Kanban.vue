<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { SortableEvent } from 'sortablejs';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import UserAvatarInline from '@/components/workflow/UserAvatarInline.vue';
import { backlogTareas } from '@/routes/coordinacion';
import { proyectos } from '@/routes/pmo';
import { dashboard } from '@/routes';
import { kanban, minutas } from '@/routes/proyecto';
import { update as patchTaskEstado } from '@/routes/proyecto/tareas';

type Ttask = {
    id: number;
    title: string;
    assignee: { name: string; avatar?: string | null } | null;
};

type Proj = { id: number; name: string; code: string | null };

const props = defineProps<{
    project: Proj | null;
    projects: Proj[];
    columns: Record<string, Ttask[]>;
    statuses: string[];
}>();

const projectId = ref(props.project?.id ?? '');

const localColumns = ref<Record<string, Ttask[]>>({});

watch(
    () => [props.columns, props.statuses] as const,
    () => {
        const next: Record<string, Ttask[]> = {};
        for (const s of props.statuses) {
            next[s] = [...(props.columns[s] ?? [])];
        }
        localColumns.value = next;
    },
    { immediate: true, deep: true },
);

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
    router.get(kanban.url({ query: { project_id: Number(projectId.value) } }));
}

function moveTask(taskId: number, status: string) {
    router.patch(patchTaskEstado.url(taskId), { status });
}

/** Solo cuando una tarjeta entra desde otra columna (Sortable `onAdd`). */
function onTaskAdded(targetStatus: string, evt: SortableEvent) {
    const from = evt.from;
    const to = evt.to;
    if (from === to) {
        return;
    }
    const raw = (evt.item as HTMLElement).dataset.taskId;
    const taskId = raw ? Number(raw) : Number.NaN;
    if (!Number.isFinite(taskId)) {
        return;
    }
    moveTask(taskId, targetStatus);
}

const columnLabels: Record<string, string> = {
    backlog: 'Backlog',
    pendiente: 'Pendiente',
    en_curso: 'En curso',
    revision: 'Revisión',
    hecha: 'Hecha',
};

const relatedLinks = [
    { title: 'Minutas', href: minutas() },
    { title: 'Backlog', href: backlogTareas() },
    { title: 'Proyectos', href: proyectos() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Kanban', href: kanban() },
        ],
    },
});
</script>

<template>
    <Head title="Kanban" />

    <WorkflowSection
        context-label="Jefe de proyecto — ejecución"
        title="Kanban por proyecto"
        description="Arrastra las tarjetas entre columnas para cambiar el estado. También puedes reordenar dentro de una columna (solo vista; el orden no se guarda aún)."
        :related-links="relatedLinks"
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
            <div
                class="grid gap-3 overflow-x-auto pb-2 md:grid-cols-5"
            >
                <div
                    v-for="st in statuses"
                    :key="st"
                    class="flex min-w-[200px] flex-col rounded-lg border border-[#003366]/15 bg-[#f8fafc] p-2"
                >
                    <h3
                        class="mb-2 border-b border-[#003366]/10 pb-1 text-xs font-semibold uppercase text-[#003366]"
                    >
                        {{ columnLabels[st] ?? st }}
                    </h3>
                    <VueDraggable
                        v-model="localColumns[st]"
                        :group="{ name: 'kanban', pull: true, put: true }"
                        :animation="200"
                        ghost-class="opacity-50"
                        class="min-h-[120px] flex-1 space-y-2"
                        :on-add="(e: SortableEvent) => onTaskAdded(st, e)"
                    >
                        <div
                            v-for="t in localColumns[st]"
                            :key="t.id"
                            :data-task-id="String(t.id)"
                            class="cursor-grab rounded border border-white bg-white p-2 text-sm shadow-sm active:cursor-grabbing"
                        >
                            <p class="font-medium text-[#333]">{{ t.title }}</p>
                            <div class="text-xs text-[#666]">
                                <UserAvatarInline
                                    v-if="t.assignee"
                                    :name="t.assignee.name"
                                    :avatar="t.assignee.avatar"
                                    size="xs"
                                />
                                <span v-else>Sin asignar</span>
                            </div>
                        </div>
                    </VueDraggable>
                </div>
            </div>
        </template>
    </WorkflowSection>
</template>
