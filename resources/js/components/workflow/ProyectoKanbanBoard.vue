<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { SortableEvent } from 'sortablejs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import UserAvatarInline from '@/components/workflow/UserAvatarInline.vue';
import { sync as kanbanSync } from '@/routes/proyecto/kanban';
import { store as postTaskGroup } from '@/routes/proyecto/task-groups';
import { store as postTask, update as patchTask } from '@/routes/proyecto/tareas';

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

type StatusOption = {
    id: number | null;
    key: string;
    label: string;
    is_system: boolean;
    is_transversal: boolean;
};

const props = defineProps<{
    project: { id: number; name: string; code: string | null };
    /** Puede faltar en recargas parciales Inertia; se normaliza en el watch. */
    groups?: GroupPayload[] | null;
    statuses?: string[] | null;
    statusOptions?: StatusOption[] | null;
    peopleOptions: Person[];
    /** Tablero macro cartera: sin alta de segmento global. */
    portfolioMode?: boolean;
    idSuffix?: string;
    focusedTaskId?: number | null;
}>();

const suf = () => (props.idSuffix ? `-${props.idSuffix}` : '');

const localGroups = ref<GroupPayload[]>([]);

const DEFAULT_STATUSES: string[] = [
    'backlog',
    'pendiente',
    'en_curso',
    'revision',
    'hecha',
];

const statusOptionsList = computed<StatusOption[]>(() => {
    if (Array.isArray(props.statusOptions) && props.statusOptions.length > 0) {
        return props.statusOptions;
    }
    return DEFAULT_STATUSES.map((key) => ({
        id: null,
        key,
        label: key.replace(/_/g, ' '),
        is_system: key === 'backlog' || key === 'hecha',
        is_transversal: false,
    }));
});
const draggableCustomStatuses = ref<StatusOption[]>([]);

function resolveStatuses(): string[] {
    const groupDerived = (Array.isArray(props.groups) ? props.groups : [])
        .flatMap((g) => Object.keys(g.columns ?? {}));
    const optionsDerived = statusOptionsList.value.map((s) => s.key);
    const raw = Array.from(new Set([
        ...groupDerived,
        ...optionsDerived,
        ...(props.statuses && props.statuses.length > 0 ? props.statuses : []),
    ]));

    const filtered = raw.filter(
        (s): s is string => typeof s === 'string' && s !== '',
    );
    return filtered.length > 0 ? filtered : [...DEFAULT_STATUSES];
}

function normalizeGroup(
    raw: Partial<GroupPayload> & { id: number },
    statuses: string[],
): GroupPayload {
    const safeRaw =
        raw !== null && raw !== undefined && typeof raw === 'object'
            ? raw
            : { id: 0 };

    const rawCols: Record<string, unknown> =
        safeRaw.columns !== null &&
        safeRaw.columns !== undefined &&
        typeof safeRaw.columns === 'object' &&
        !Array.isArray(safeRaw.columns)
            ? (safeRaw.columns as Record<string, unknown>)
            : {};

    const columns = {} as Record<string, Ttask[]>;
    for (const s of statuses) {
        const arr = rawCols[s];
        columns[s] = Array.isArray(arr) ? [...(arr as Ttask[])] : [];
    }

    return {
        id: typeof safeRaw.id === 'number' ? safeRaw.id : 0,
        name: typeof safeRaw.name === 'string' ? safeRaw.name : '',
        color: typeof safeRaw.color === 'string' ? safeRaw.color : '#64748b',
        position: typeof safeRaw.position === 'number' ? safeRaw.position : 0,
        columns,
        progress_percent:
            typeof safeRaw.progress_percent === 'number'
                ? safeRaw.progress_percent
                : 0,
    };
}

const displayStatuses = computed(() => resolveStatuses());

/** Aísla el arrastre entre tableros de distintos proyectos en la cartera macro. */
const draggableGroupName = computed(
    () => `kanban-scope-${props.project.id}`,
);

watch(
    () => [props.groups, props.statuses] as const,
    () => {
        const statuses = resolveStatuses();
        const list = Array.isArray(props.groups) ? props.groups : [];
        localGroups.value = list
            .filter(
                (g) => g != null && typeof g === 'object' && 'id' in g,
            )
            .map((g) =>
                normalizeGroup(
                    g as Partial<GroupPayload> & { id: number },
                    statuses,
                ),
            );
    },
    { immediate: true, deep: true },
);

watch(
    statusOptionsList,
    (list) => {
        draggableCustomStatuses.value = list.filter((s) => !s.is_system).map((s) => ({ ...s }));
    },
    { immediate: true, deep: true },
);

function ensureColumnTasks(g: GroupPayload, st: string): void {
    if (
        g.columns === null ||
        g.columns === undefined ||
        typeof g.columns !== 'object' ||
        Array.isArray(g.columns)
    ) {
        g.columns = Object.fromEntries(
            resolveStatuses().map((s) => [s, [] as Ttask[]]),
        ) as Record<string, Ttask[]>;
    }
    if (!Array.isArray(g.columns[st])) {
        g.columns[st] = [];
    }
}

function setColumnTasks(g: GroupPayload, st: string, tasks: Ttask[]): void {
    ensureColumnTasks(g, st);
    g.columns![st] = tasks;
}

function buildOrders(): {
    task_id: number;
    status: string;
    task_group_id: number;
    kanban_order: number;
}[] {
    const orders: {
        task_id: number;
        status: string;
        task_group_id: number;
        kanban_order: number;
    }[] = [];
    const statuses = resolveStatuses();
    for (const g of localGroups.value) {
        const cols = g.columns ?? {};
        for (const st of statuses) {
            const list = cols[st] ?? [];
            list.forEach((task, idx) => {
                orders.push({
                    task_id: task.id,
                    status: st,
                    task_group_id: g.id,
                    kanban_order: idx,
                });
            });
        }
    }
    return orders;
}

let flushTimer: ReturnType<typeof setTimeout> | null = null;

function scheduleFlushKanban() {
    if (flushTimer) {
        clearTimeout(flushTimer);
    }
    flushTimer = setTimeout(() => {
        flushTimer = null;
        router.patch(kanbanSync.url(), {
            project_id: props.project.id,
            orders: buildOrders(),
        });
    }, 280);
}

function onTaskAdded(targetStatus: string, evt: SortableEvent) {
    const from = evt.from;
    const to = evt.to;
    if (from === to) {
        return;
    }
    scheduleFlushKanban();
}

function onSortEnd() {
    scheduleFlushKanban();
}

const columnLabels: Record<string, string> = {
    backlog: 'Backlog',
    pendiente: 'Pendiente',
    en_curso: 'En curso',
    revision: 'Revisión',
    hecha: 'Hecha',
};

const columnLabelByKey = computed<Record<string, string>>(() => {
    const map: Record<string, string> = { ...columnLabels };
    statusOptionsList.value.forEach((s) => {
        map[s.key] = s.label;
    });
    return map;
});

const newSegmentName = ref('');
const newStatusLabel = ref('');
const newTaskTitles = ref<Record<number, string>>({});

function submitSegment() {
    if (props.portfolioMode || !newSegmentName.value.trim()) {
        return;
    }
    router.post(postTaskGroup.url(), {
        project_id: props.project.id,
        name: newSegmentName.value.trim(),
        color: '#64748b',
    });
    newSegmentName.value = '';
}

function submitQuickTask(groupId: number) {
    const title = (newTaskTitles.value[groupId] ?? '').trim();
    if (!title) {
        return;
    }
    router.post(postTask.url(), {
        project_id: props.project.id,
        task_group_id: groupId,
        title,
    });
    newTaskTitles.value[groupId] = '';
}

const dialogOpen = ref(false);
const statusDialogOpen = ref(false);
const editingStatus = ref<StatusOption | null>(null);
const editing = ref<Ttask | null>(null);
const editForm = useForm({
    title: '',
    description: '',
    assignee_id: null as number | null,
    collaborator_ids: [] as number[],
});

const statusForm = useForm({
    label: '',
});

function isFocusedTask(taskId: number): boolean {
    return props.focusedTaskId !== null && props.focusedTaskId !== undefined && props.focusedTaskId === taskId;
}

watch(
    () => [props.focusedTaskId, localGroups.value.length] as const,
    async () => {
        if (!props.focusedTaskId) {
            return;
        }
        await nextTick();
        const el = document.querySelector(`[data-task-id="${props.focusedTaskId}"]`);
        if (el instanceof HTMLElement) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        }
    },
    { immediate: true },
);

function openTaskModal(task: Ttask) {
    editing.value = task;
    editForm.title = task.title;
    editForm.description = task.description ?? '';
    editForm.assignee_id = task.assignee?.id ?? null;
    editForm.collaborator_ids = task.collaborators.map((c) => c.id);
    dialogOpen.value = true;
}

function saveTaskModal() {
    if (!editing.value) {
        return;
    }
    editForm.patch(patchTask.url(editing.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
        },
    });
}

function toggleCollaborator(id: number) {
    const i = editForm.collaborator_ids.indexOf(id);
    if (i === -1) {
        editForm.collaborator_ids = [...editForm.collaborator_ids, id];
    } else {
        editForm.collaborator_ids = editForm.collaborator_ids.filter(
            (x) => x !== id,
        );
    }
}

function submitStatusCreate(): void {
    const label = newStatusLabel.value.trim();
    if (!label) {
        return;
    }
    router.post('/proyecto/kanban/estados', {
        project_id: props.project.id,
        label,
    });
    newStatusLabel.value = '';
}

function openStatusEdit(status: StatusOption): void {
    if (status.is_system) {
        return;
    }
    editingStatus.value = status;
    statusForm.label = status.label;
    statusDialogOpen.value = true;
}

function saveStatusEdit(): void {
    if (!editingStatus.value || editingStatus.value.id === null) {
        return;
    }
    statusForm.patch(`/proyecto/kanban/${props.project.id}/estados/${editingStatus.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            statusDialogOpen.value = false;
        },
    });
}

function deleteStatus(status: StatusOption): void {
    if (status.is_system || status.id === null) {
        return;
    }
    router.delete(`/proyecto/kanban/${props.project.id}/estados/${status.id}`, { preserveScroll: true });
}

function persistStatusOrder(): void {
    if (draggableCustomStatuses.value.length === 0) {
        return;
    }
    router.patch(`/proyecto/kanban/${props.project.id}/estados/orden`, {
        statuses: draggableCustomStatuses.value.map((s) => ({ key: s.key, label: s.label })),
    }, { preserveScroll: true });
}
</script>

<template>
    <div>
        <div
            v-if="!portfolioMode"
            class="mb-4 flex flex-wrap items-end gap-2 rounded-lg border border-[#003366]/12 bg-[#f8fafc] p-3"
        >
            <div class="flex flex-1 flex-col gap-1">
                <Label
                    :for="`seg-nuevo${suf()}`"
                    class="text-xs text-[#003366]"
                    >Nuevo segmento</Label
                >
                <div class="flex gap-2">
                    <Input
                        :id="`seg-nuevo${suf()}`"
                        v-model="newSegmentName"
                        class="max-w-xs"
                        placeholder="Nombre del segmento"
                        @keydown.enter.prevent="submitSegment"
                    />
                    <Button
                        type="button"
                        class="bg-[#003366] hover:bg-[#003366]/90"
                        @click="submitSegment"
                    >
                        Añadir
                    </Button>
                </div>
            </div>
        </div>

        <div
            v-if="!portfolioMode"
            class="mb-4 rounded-lg border border-[#003366]/12 bg-white p-3"
        >
            <div class="mb-2 flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#003366]">
                    Estados Kanban del proyecto
                </p>
                <span class="text-[11px] text-slate-500">
                    Backlog y Hecha son fijos.
                </span>
            </div>
            <div class="mb-3 flex flex-wrap gap-2">
                <span
                    class="inline-flex items-center gap-1 rounded-full border border-slate-300 bg-slate-100 px-2 py-1 text-xs text-slate-700"
                >
                    Backlog
                </span>
                <VueDraggable
                    v-model="draggableCustomStatuses"
                    class="inline-flex flex-wrap gap-2"
                    :animation="180"
                    ghost-class="opacity-50"
                    @end="persistStatusOrder"
                >
                    <span
                        v-for="status in draggableCustomStatuses"
                        :key="`status-chip-${status.key}`"
                        class="inline-flex items-center gap-1 rounded-full border border-[#003366]/25 bg-white px-2 py-1 text-xs text-[#003366]"
                    >
                        <span class="cursor-grab active:cursor-grabbing">↕</span>
                        {{ status.label }}
                        <button
                            type="button"
                            class="rounded px-1 text-[10px] hover:bg-[#003366]/10"
                            @click="openStatusEdit(status)"
                        >
                            editar
                        </button>
                        <button
                            type="button"
                            class="rounded px-1 text-[10px] text-rose-700 hover:bg-rose-100"
                            @click="deleteStatus(status)"
                        >
                            eliminar
                        </button>
                    </span>
                </VueDraggable>
                <span
                    class="inline-flex items-center gap-1 rounded-full border border-slate-300 bg-slate-100 px-2 py-1 text-xs text-slate-700"
                >
                    Hecha
                </span>
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <div class="flex min-w-[15rem] flex-1 flex-col gap-1">
                    <Label :for="`status-nuevo${suf()}`" class="text-xs text-[#003366]">
                        Nuevo estado
                    </Label>
                    <Input
                        :id="`status-nuevo${suf()}`"
                        v-model="newStatusLabel"
                        class="max-w-sm"
                        placeholder="Ej: bloqueada por cliente"
                        @keydown.enter.prevent="submitStatusCreate"
                    />
                </div>
                <Button type="button" class="bg-[#003366] hover:bg-[#003366]/90" @click="submitStatusCreate">
                    Añadir estado
                </Button>
            </div>
        </div>

        <div class="space-y-8">
            <div
                v-if="props.focusedTaskId"
                class="rounded-md border border-[#e69b0a]/35 bg-amber-50 px-3 py-2 text-xs text-amber-900"
            >
                Actividad destacada desde calendario. La tarjeta correspondiente queda resaltada en el tablero.
            </div>
            <section
                v-for="g in localGroups"
                :key="g.id"
                class="rounded-xl border border-[#003366]/12 bg-white shadow-sm"
            >
                <div
                    class="flex flex-wrap items-center gap-3 border-b border-[#003366]/10 px-3 py-2"
                    :style="{
                        borderLeftWidth: '4px',
                        borderLeftColor: g.color,
                    }"
                >
                    <h2 class="text-sm font-semibold text-[#003366]">
                        {{ g.name }}
                    </h2>
                    <div
                        class="h-1.5 min-w-[120px] flex-1 overflow-hidden rounded-full bg-slate-200"
                    >
                        <div
                            class="h-full rounded-full bg-[#003366] transition-all"
                            :style="{ width: `${g.progress_percent}%` }"
                        />
                    </div>
                    <span class="text-xs tabular-nums text-slate-600"
                        >{{ g.progress_percent }}%</span
                    >
                </div>

                <div
                    class="grid gap-2 overflow-x-auto p-2 pb-3 md:grid-cols-5"
                >
                    <div
                        v-for="st in displayStatuses"
                        :key="`${g.id}-${st}`"
                        class="flex min-w-[200px] flex-col rounded-lg border border-[#003366]/10 bg-[#f8fafc] p-2"
                    >
                        <h3
                            class="mb-2 border-b border-[#003366]/10 pb-1 text-xs font-semibold uppercase text-[#003366]"
                        >
                            {{ columnLabelByKey[st] ?? st }}
                            <span class="font-normal text-slate-500">{{
                                ((g.columns ?? {})[st] ?? []).length
                            }}</span>
                        </h3>
                        <VueDraggable
                            v-model="g.columns[st]"
                            :group="{
                                name: draggableGroupName,
                                pull: true,
                                put: true,
                            }"
                            :animation="200"
                            ghost-class="opacity-50"
                            class="min-h-[100px] flex-1 space-y-2"
                            :data-group-id="g.id"
                            :data-status="st"
                            @add="(e: SortableEvent) => onTaskAdded(st, e)"
                            @end="onSortEnd"
                        >
                            <div
                                v-for="t in (g.columns ?? {})[st] ?? []"
                                :key="t.id"
                                :data-task-id="String(t.id)"
                                class="cursor-grab rounded border bg-white p-2 text-left text-sm shadow-sm active:cursor-grabbing"
                                :class="
                                    isFocusedTask(t.id)
                                        ? 'border-[#e69b0a] ring-2 ring-[#e69b0a]/35'
                                        : 'border-white'
                                "
                            >
                                <div
                                    class="flex items-start justify-between gap-1"
                                >
                                    <p class="font-medium text-[#333]">
                                        {{ t.title }}
                                    </p>
                                    <button
                                        type="button"
                                        class="shrink-0 rounded px-1 text-xs font-medium text-[#003366] hover:underline"
                                        @click.stop="openTaskModal(t)"
                                    >
                                        Detalle
                                    </button>
                                </div>
                                <div
                                    class="mt-1 flex flex-wrap items-center gap-1"
                                >
                                    <template v-if="t.assignee">
                                        <UserAvatarInline
                                            :name="t.assignee.name"
                                            :avatar="t.assignee.avatar"
                                            size="xs"
                                        />
                                    </template>
                                    <UserAvatarInline
                                        v-for="c in t.collaborators"
                                        :key="c.id"
                                        :name="c.name"
                                        :avatar="c.avatar"
                                        size="xs"
                                    />
                                    <span
                                        v-if="
                                            !t.assignee &&
                                            t.collaborators.length === 0
                                        "
                                        class="text-xs text-[#666]"
                                        >Sin asignar</span
                                    >
                                </div>
                            </div>
                        </VueDraggable>
                        <div
                            v-if="st === 'backlog'"
                            class="mt-2 flex gap-1 border-t border-dashed border-[#003366]/15 pt-2"
                        >
                            <Input
                                v-model="newTaskTitles[g.id]"
                                class="h-8 text-xs"
                                placeholder="Nueva tarea…"
                                @keydown.enter.prevent="
                                    submitQuickTask(g.id)
                                "
                            />
                            <Button
                                type="button"
                                variant="secondary"
                                class="h-8 shrink-0 px-2 text-xs"
                                @click="submitQuickTask(g.id)"
                            >
                                +
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Editar tarea</DialogTitle>
                    <DialogDescription>
                        Responsable, colaboradores y descripción. Guardar
                        actualiza el tablero.
                    </DialogDescription>
                </DialogHeader>
                <div v-if="editing" class="space-y-3 py-2">
                    <div class="space-y-1">
                        <Label :for="`t-title${suf()}`">Título</Label>
                        <Input
                            :id="`t-title${suf()}`"
                            v-model="editForm.title"
                        />
                        <p
                            v-if="editForm.errors.title"
                            class="text-xs text-destructive"
                        >
                            {{ editForm.errors.title }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <Label :for="`t-desc${suf()}`">Descripción</Label>
                        <textarea
                            :id="`t-desc${suf()}`"
                            v-model="editForm.description"
                            rows="4"
                            class="flex min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                    </div>
                    <div class="space-y-1">
                        <Label :for="`t-assignee${suf()}`">Responsable</Label>
                        <select
                            :id="`t-assignee${suf()}`"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            :value="editForm.assignee_id ?? ''"
                            @change="
                                editForm.assignee_id =
                                    ($event.target as HTMLSelectElement)
                                        .value === ''
                                        ? null
                                        : Number(
                                              (
                                                  $event.target as HTMLSelectElement
                                              ).value,
                                          )
                            "
                        >
                            <option value="">Sin asignar</option>
                            <option
                                v-for="u in peopleOptions"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label>Colaboradores</Label>
                        <div
                            class="flex max-h-40 flex-col gap-1 overflow-y-auto rounded-md border border-input p-2"
                        >
                            <label
                                v-for="u in peopleOptions"
                                :key="u.id"
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    class="rounded border-input"
                                    :checked="
                                        editForm.collaborator_ids.includes(
                                            u.id,
                                        )
                                    "
                                    :disabled="editForm.assignee_id === u.id"
                                    @change="toggleCollaborator(u.id)"
                                />
                                <span>{{ u.name }}</span>
                            </label>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            El responsable no se duplica como colaborador.
                        </p>
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        variant="secondary"
                        type="button"
                        @click="dialogOpen = false"
                    >
                        Cerrar
                    </Button>
                    <Button
                        type="button"
                        class="bg-[#003366] hover:bg-[#003366]/90"
                        :disabled="editForm.processing"
                        @click="saveTaskModal"
                    >
                        Guardar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="statusDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar estado Kanban</DialogTitle>
                    <DialogDescription>
                        Cambiar nombre de estado en el contexto del proyecto.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-1 py-2">
                    <Label :for="`status-edit${suf()}`">Nombre</Label>
                    <Input :id="`status-edit${suf()}`" v-model="statusForm.label" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="statusDialogOpen = false">Cancelar</Button>
                    <Button type="button" class="bg-[#003366] hover:bg-[#003366]/90" @click="saveStatusEdit">
                        Guardar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
