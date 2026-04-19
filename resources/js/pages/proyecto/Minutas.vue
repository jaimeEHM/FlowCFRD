<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { backlogTareas } from '@/routes/coordinacion';
import { dashboard } from '@/routes';
import { kanban, minutas } from '@/routes/proyecto';
import { store as postMinuta } from '@/routes/proyecto/minutas';

type Row = {
    id: number;
    title: string;
    body: string;
    held_at: string;
    project: { name: string; code: string | null };
    created_by: { name: string } | null;
};

type Proj = { id: number; name: string; code: string | null };

defineProps<{
    minutes: Row[];
    projects: Proj[];
}>();

const form = useForm({
    project_id: '' as string | number,
    title: '',
    body: '',
    held_at: '',
});

function submit() {
    form.post(postMinuta.url());
}

const relatedLinks = [
    { title: 'Kanban', href: kanban() },
    { title: 'Backlog', href: backlogTareas() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Minutas', href: minutas() },
        ],
    },
});
</script>

<template>
    <Head title="Minutas" />

    <WorkflowSection
        context-label="Jefe de proyecto — ejecución"
        title="Minutas de avance y contingencia"
        description="Registro de reuniones por proyecto."
        :related-links="relatedLinks"
    >
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-3">
                <div
                    v-for="m in minutes"
                    :key="m.id"
                    class="rounded-lg border border-[#003366]/15 bg-white p-4 shadow-sm"
                >
                    <h3 class="font-semibold text-[#003366]">{{ m.title }}</h3>
                    <p class="text-xs text-[#666]">
                        {{ m.project.name }} · {{ m.held_at }} ·
                        {{ m.created_by?.name ?? '—' }}
                    </p>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-[#333]">
                        {{ m.body }}
                    </p>
                </div>
                <p v-if="minutes.length === 0" class="text-[#666]">
                    No hay minutas registradas.
                </p>
            </div>

            <div
                class="h-fit rounded-lg border border-[#003366]/15 bg-[#f8fafc] p-4"
            >
                <h2 class="text-sm font-semibold text-[#003366]">Nueva minuta</h2>
                <form class="mt-4 space-y-3" @submit.prevent="submit">
                    <div class="space-y-1">
                        <Label for="project_id">Proyecto</Label>
                        <select
                            id="project_id"
                            v-model="form.project_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input px-3 text-sm"
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
                        <Label for="held_at">Fecha y hora</Label>
                        <Input
                            id="held_at"
                            v-model="form.held_at"
                            type="datetime-local"
                            required
                        />
                        <InputError :message="form.errors.held_at" />
                    </div>
                    <div class="space-y-1">
                        <Label for="body">Contenido</Label>
                        <textarea
                            id="body"
                            v-model="form.body"
                            required
                            rows="5"
                            class="flex w-full rounded-md border border-input px-3 py-2 text-sm"
                        />
                        <InputError :message="form.errors.body" />
                    </div>
                    <Button
                        type="submit"
                        class="w-full bg-[#003366]"
                        :disabled="form.processing"
                    >
                        Guardar minuta
                    </Button>
                </form>
            </div>
        </div>
    </WorkflowSection>
</template>
