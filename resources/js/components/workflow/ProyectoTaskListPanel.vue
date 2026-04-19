<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
import { store as postTaskGroup } from '@/routes/proyecto/task-groups';
import { store as postTask, update as patchTask } from '@/routes/proyecto/tareas';
import type {
    TaskListGroup,
    TaskListPerson,
    TaskListRow,
} from '@/types/proyectoTaskList';

export type { TaskListGroup, TaskListPerson, TaskListRow };

const props = defineProps<{
    project: { id: number; name: string; code: string | null };
    taskGroups: TaskListGroup[];
    tasks: TaskListRow[];
    statuses: string[];
    peopleOptions: TaskListPerson[];
    /** Sufijo para ids de campos si hay varios paneles en la misma página */
    idSuffix?: string;
    /** Lista de toda la cartera (varios proyectos); oculta alta de segmento sin contexto único. */
    portfolioMode?: boolean;
}>();

const suf = () => (props.idSuffix ? `-${props.idSuffix}` : '');

const searchQuery = ref('');
const newSegmentName = ref('');
const newTaskTitles = ref<Record<number, string>>({});
const collapsed = ref<Record<number, boolean>>({});

const sortedGroups = computed(() =>
    [...props.taskGroups].sort((a, b) => {
        if (a.position !== b.position) {
            return a.position - b.position;
        }
        return a.id - b.id;
    }),
);

function groupsForTask(t: TaskListRow): TaskListGroup[] {
    const pid = t.project_id;
    if (pid === undefined) {
        return sortedGroups.value;
    }
    return sortedGroups.value.filter((g) => (g.project_id ?? props.project.id) === pid);
}

function tasksForGroup(groupId: number): TaskListRow[] {
    const q = searchQuery.value.trim().toLowerCase();
    return props.tasks.filter((t) => {
        if (t.task_group_id !== groupId) {
            return false;
        }
        if (!q) {
            return true;
        }
        return t.title.toLowerCase().includes(q);
    });
}

function toggleGroupCollapse(id: number) {
    collapsed.value = {
        ...collapsed.value,
        [id]: !collapsed.value[id],
    };
}

function statusLabel(s: string): string {
    const map: Record<string, string> = {
        backlog: 'Backlog',
        pendiente: 'Pendiente',
        en_curso: 'En curso',
        revision: 'Revisión',
        hecha: 'Hecha',
    };
    return map[s] ?? s.replace(/_/g, ' ');
}

function patchTaskField(
    taskId: number,
    data: Record<string, unknown>,
): void {
    router.patch(patchTask.url(taskId), data, { preserveScroll: true });
}

function submitSegment() {
    if (!newSegmentName.value.trim() || props.portfolioMode) {
        return;
    }
    router.post(postTaskGroup.url(), {
        project_id: props.project.id,
        name: newSegmentName.value.trim(),
        color: '#64748b',
    });
    newSegmentName.value = '';
}

function resolveProjectIdForGroup(groupId: number): number {
    const g = props.taskGroups.find((x) => x.id === groupId);
    return g?.project_id ?? props.project.id;
}

function submitQuickTask(groupId: number) {
    const title = (newTaskTitles.value[groupId] ?? '').trim();
    if (!title) {
        return;
    }
    router.post(postTask.url(), {
        project_id: resolveProjectIdForGroup(groupId),
        task_group_id: groupId,
        title,
    });
    newTaskTitles.value[groupId] = '';
}

const dialogOpen = ref(false);
const editing = ref<TaskListRow | null>(null);
const editForm = useForm({
    title: '',
    description: '',
    assignee_id: null as number | null,
    collaborator_ids: [] as number[],
});

function openTaskModal(task: TaskListRow) {
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
</script>

<template>
    <div>
        <div
            class="mb-4 flex flex-col gap-3 rounded-xl border border-[#003366]/12 bg-[#f8fafc] p-4 shadow-[0_1px_2px_rgba(0,51,102,0.06)] sm:flex-row sm:flex-wrap sm:items-end sm:justify-between"
        >
            <div class="flex min-w-0 flex-1 flex-col gap-1">
                <Label
                    :for="`buscar-tareas${suf()}`"
                    class="text-xs text-[#003366]"
                    >Buscar en tareas</Label
                >
                <Input
                    :id="`buscar-tareas${suf()}`"
                    v-model="searchQuery"
                    class="max-w-md border-slate-200"
                    placeholder="Filtrar por título…"
                />
            </div>
            <div
                v-if="!portfolioMode"
                class="flex flex-col gap-1"
            >
                <Label
                    :for="`seg-nuevo${suf()}`"
                    class="text-xs text-[#003366]"
                    >Nuevo segmento</Label
                >
                <div class="flex flex-wrap gap-2">
                    <Input
                        :id="`seg-nuevo${suf()}`"
                        v-model="newSegmentName"
                        class="min-w-[12rem] max-w-xs"
                        placeholder="Nombre del segmento"
                        @keydown.enter.prevent="submitSegment"
                    />
                    <Button
                        type="button"
                        class="bg-[#003366] hover:bg-[#00264d]"
                        @click="submitSegment"
                    >
                        Añadir segmento
                    </Button>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <section
                v-for="g in sortedGroups"
                :key="g.id"
                class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
            >
                <div
                    class="flex flex-wrap items-center gap-3 border-b border-[#003366]/10 px-3 py-2.5"
                    :style="{
                        borderLeftWidth: '4px',
                        borderLeftColor: g.color,
                    }"
                >
                    <button
                        type="button"
                        class="flex items-center gap-1 text-sm font-semibold text-[#003366] hover:underline"
                        :aria-expanded="!collapsed[g.id]"
                        @click="toggleGroupCollapse(g.id)"
                    >
                        <span class="tabular-nums text-slate-500">{{
                            collapsed[g.id] ? '▸' : '▾'
                        }}</span>
                        {{ g.name }}
                    </button>
                    <div
                        class="h-1.5 min-w-[100px] flex-1 overflow-hidden rounded-full bg-slate-200"
                    >
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[#003366] to-[#1e5a8e] transition-all"
                            :style="{ width: `${g.progress_percent}%` }"
                        />
                    </div>
                    <span class="text-xs tabular-nums text-slate-600"
                        >{{ g.progress_percent }}% listo</span
                    >
                </div>

                <div v-show="!collapsed[g.id]" class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[960px] text-left text-sm">
                            <thead
                                class="border-b border-[#003366]/10 bg-[#f1f5f9] text-[11px] font-semibold uppercase tracking-wide text-[#003366]"
                            >
                                <tr>
                                    <th class="px-3 py-2">Tarea</th>
                                    <th class="px-3 py-2">Segmento</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="px-3 py-2">Responsable</th>
                                    <th class="px-3 py-2">Colaboradores</th>
                                    <th class="px-3 py-2">Vence</th>
                                    <th class="px-3 py-2 text-right">Detalle</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#003366]/8">
                                <tr
                                    v-for="t in tasksForGroup(g.id)"
                                    :key="t.id"
                                    class="bg-white transition hover:bg-[#003366]/[0.03]"
                                >
                                    <td
                                        class="max-w-[min(28rem,40vw)] px-3 py-2 align-middle"
                                    >
                                        <input
                                            type="text"
                                            class="w-full min-w-[8rem] rounded-md border border-transparent bg-transparent px-1.5 py-1 text-sm font-medium text-[#1e293b] outline-none hover:border-slate-200 focus:border-[#003366] focus:ring-1 focus:ring-[#003366]"
                                            :value="t.title"
                                            @change="
                                                patchTaskField(t.id, {
                                                    title: (
                                                        $event.target as HTMLInputElement
                                                    ).value,
                                                })
                                            "
                                        />
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <select
                                            class="h-8 max-w-[10rem] rounded-md border border-slate-200 bg-white px-2 text-xs text-slate-800 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                                            :value="t.task_group_id"
                                            @change="
                                                patchTaskField(t.id, {
                                                    task_group_id: Number(
                                                        (
                                                            $event.target as HTMLSelectElement
                                                        ).value,
                                                    ),
                                                })
                                            "
                                        >
                                            <option
                                                v-for="tg in groupsForTask(t)"
                                                :key="tg.id"
                                                :value="tg.id"
                                            >
                                                {{ tg.name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <select
                                            class="h-8 min-w-[7.5rem] rounded-md border border-slate-200 bg-white px-2 text-xs capitalize text-slate-800 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                                            :value="t.status"
                                            @change="
                                                patchTaskField(t.id, {
                                                    status: (
                                                        $event.target as HTMLSelectElement
                                                    ).value,
                                                })
                                            "
                                        >
                                            <option
                                                v-for="s in statuses"
                                                :key="s"
                                                :value="s"
                                            >
                                                {{ statusLabel(s) }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <select
                                            class="h-8 max-w-[11rem] rounded-md border border-slate-200 bg-white px-2 text-xs focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                                            :value="t.assignee?.id ?? ''"
                                            @change="
                                                patchTaskField(t.id, {
                                                    assignee_id:
                                                        (
                                                            $event.target as HTMLSelectElement
                                                        ).value === ''
                                                            ? null
                                                            : Number(
                                                                  (
                                                                      $event.target as HTMLSelectElement
                                                                  ).value,
                                                              ),
                                                })
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
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <div
                                            class="flex flex-wrap items-center gap-1"
                                        >
                                            <UserAvatarInline
                                                v-for="c in t.collaborators"
                                                :key="c.id"
                                                :name="c.name"
                                                :avatar="c.avatar"
                                                size="xs"
                                            />
                                            <span
                                                v-if="t.collaborators.length === 0"
                                                class="text-xs text-slate-400"
                                                >—</span
                                            >
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <input
                                            type="date"
                                            class="h-8 rounded-md border border-slate-200 px-1.5 text-xs text-slate-800 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                                            :value="t.due_date ?? ''"
                                            @change="
                                                patchTaskField(t.id, {
                                                    due_date:
                                                        (
                                                            $event.target as HTMLInputElement
                                                        ).value || null,
                                                })
                                            "
                                        />
                                    </td>
                                    <td class="px-3 py-2 text-right align-middle">
                                        <button
                                            type="button"
                                            class="text-xs font-medium text-[#003366] underline decoration-[#003366]/30 underline-offset-2 hover:decoration-[#003366]"
                                            @click="openTaskModal(t)"
                                        >
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="tasksForGroup(g.id).length === 0">
                                    <td
                                        colspan="7"
                                        class="px-3 py-6 text-center text-sm text-slate-500"
                                    >
                                        {{
                                            searchQuery.trim()
                                                ? 'Ninguna tarea coincide con la búsqueda en este segmento.'
                                                : 'Sin tareas en este segmento. Añade una abajo.'
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-2 border-t border-dashed border-[#003366]/15 bg-[#fafbfc] px-3 py-2"
                    >
                        <span class="text-sm text-slate-500">+</span>
                        <Input
                            v-model="newTaskTitles[g.id]"
                            class="h-9 max-w-md flex-1 border-slate-200"
                            placeholder="Nueva tarea…"
                            @keydown.enter.prevent="submitQuickTask(g.id)"
                        />
                        <Button
                            type="button"
                            variant="secondary"
                            class="h-9 bg-white text-[#003366] hover:bg-[#003366]/5"
                            @click="submitQuickTask(g.id)"
                        >
                            Agregar tarea
                        </Button>
                    </div>
                </div>
            </section>

            <p
                v-if="taskGroups.length === 0"
                class="rounded-lg border border-dashed border-[#003366]/25 px-4 py-8 text-center text-sm text-slate-600"
            >
                No hay segmentos. Crea uno con el formulario superior.
            </p>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="text-[#003366]"
                        >Detalle de tarea</DialogTitle
                    >
                    <DialogDescription>
                        Descripción, responsable y colaboradores. Guardar
                        sincroniza con el tablero y esta lista.
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
                            class="flex min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-[#003366] focus-visible:ring-[3px] focus-visible:ring-[#003366]/25"
                        />
                    </div>
                    <div class="space-y-1">
                        <Label :for="`t-assignee${suf()}`">Responsable</Label>
                        <select
                            :id="`t-assignee${suf()}`"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-[#003366] focus-visible:ring-[#003366]/20"
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
                                    class="rounded border-input text-[#003366] focus:ring-[#003366]"
                                    :checked="
                                        editForm.collaborator_ids.includes(u.id)
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
                        class="bg-[#003366] hover:bg-[#00264d]"
                        :disabled="editForm.processing"
                        @click="saveTaskModal"
                    >
                        Guardar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
