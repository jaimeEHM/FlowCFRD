<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import UserAvatarInline from '@/components/workflow/UserAvatarInline.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { backlogTareas } from '@/routes/coordinacion';
import { store as postBacklog } from '@/routes/coordinacion/backlog-tareas';

type Task = {
    id: number;
    title: string;
    status: string;
    is_urgent: boolean;
    backlog_order: number;
    project: { name: string; code: string | null };
    assignee: { name: string; avatar?: string | null } | null;
};

type Proj = { id: number; name: string; code: string | null };

defineProps<{
    tasks: Task[];
    projects: Proj[];
    statuses: string[];
}>();

const form = useForm({
    project_id: '' as string | number,
    title: '',
    description: '',
    is_urgent: false,
});

function submit() {
    form.post(postBacklog.url());
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Backlog de tareas', href: backlogTareas() },
        ],
    },
});
</script>

<template>
    <Head title="Backlog" />

    <WorkflowSection
        context-label="Coordinación — personas y backlog"
        title="Backlog de tareas"
        description="Listado ordenado y alta de ítems vinculados a un proyecto."
    >
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_300px]">
            <div
                class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white shadow-sm"
            >
                <table class="w-full min-w-[800px] text-left text-sm">
                    <thead
                        class="border-b border-[#003366]/15 bg-[#f8fafc] text-xs font-semibold uppercase text-[#003366]"
                    >
                        <tr>
                            <th class="px-4 py-3">Título</th>
                            <th class="px-4 py-3">Proyecto</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Asignado</th>
                            <th class="px-4 py-3">Orden</th>
                            <th class="px-4 py-3">Urgente</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#003366]/10">
                        <tr v-for="t in tasks" :key="t.id">
                            <td class="px-4 py-3 font-medium">{{ t.title }}</td>
                            <td class="px-4 py-3 text-[#666]">
                                {{ t.project.name }}
                            </td>
                            <td class="px-4 py-3 capitalize">{{ t.status }}</td>
                            <td class="px-4 py-3 text-[#666]">
                                <UserAvatarInline
                                    v-if="t.assignee"
                                    :name="t.assignee.name"
                                    :avatar="t.assignee.avatar"
                                    size="xs"
                                />
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ t.backlog_order }}</td>
                            <td class="px-4 py-3">{{ t.is_urgent ? 'Sí' : 'No' }}</td>
                        </tr>
                        <tr v-if="tasks.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-[#666]">
                                Sin tareas en backlog.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="h-fit rounded-lg border border-[#003366]/15 bg-[#f8fafc] p-4"
            >
                <h2 class="text-sm font-semibold text-[#003366]">Nueva tarea</h2>
                <form class="mt-4 space-y-3" @submit.prevent="submit">
                    <div class="space-y-1">
                        <Label for="project_id">Proyecto</Label>
                        <select
                            id="project_id"
                            v-model="form.project_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option disabled value="">Selecciona…</option>
                            <option
                                v-for="p in projects"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.project_id" />
                    </div>
                    <div class="space-y-1">
                        <Label for="title">Título</Label>
                        <Input id="title" v-model="form.title" required />
                        <InputError :message="form.errors.title" />
                    </div>
                    <div class="space-y-1">
                        <Label for="description">Descripción</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            class="flex min-h-[48px] w-full rounded-md border border-input px-3 py-2 text-sm"
                        />
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="form.is_urgent"
                            type="checkbox"
                            class="rounded border-input"
                        />
                        Marcar como urgente (requiere validación)
                    </label>
                    <Button
                        type="submit"
                        class="w-full bg-[#003366]"
                        :disabled="form.processing"
                    >
                        Añadir al backlog
                    </Button>
                </form>
            </div>
        </div>
    </WorkflowSection>
</template>
