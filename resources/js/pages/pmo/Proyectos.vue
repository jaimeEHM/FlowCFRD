<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { backlogTareas } from '@/routes/coordinacion';
import { gantt, indicadores, proyectos, tableroMacro } from '@/routes/pmo';
import { kanban } from '@/routes/proyecto';
import { store as postProyecto } from '@/routes/pmo/proyectos';

type Project = {
    id: number;
    name: string;
    code: string | null;
    status: string;
    carta_inicio_at: string | null;
    jefe_proyecto: { name: string; email: string } | null;
    created_by: { name: string } | null;
};

const props = defineProps<{
    projects: Project[];
    statuses: string[];
}>();

const form = useForm({
    name: '',
    code: '',
    description: '',
    carta_inicio_at: '',
    starts_at: '',
    ends_at: '',
    status: props.statuses[0] ?? 'borrador',
    jefe_proyecto_id: '' as string,
});

function submit() {
    form.post(postProyecto.url());
}

const relatedLinks = [
    { title: 'Tablero macro', href: tableroMacro() },
    { title: 'Indicadores (KPI)', href: indicadores() },
    { title: 'Gantt', href: gantt() },
    { title: 'Kanban', href: kanban() },
    { title: 'Backlog', href: backlogTareas() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Proyectos e iniciativas', href: proyectos() },
        ],
    },
});
</script>

<template>
    <Head title="Proyectos" />

    <WorkflowSection
        context-label="PMO — visión macro y seguimiento"
        title="Proyectos e iniciativas"
        description="Alta y listado de proyectos (Carta de Inicio, fechas, estado, responsable)."
        :related-links="relatedLinks"
    >
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div
                class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white shadow-sm"
            >
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead
                        class="border-b border-[#003366]/15 bg-[#f8fafc] text-xs font-semibold uppercase tracking-wide text-[#003366]"
                    >
                        <tr>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Código</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Carta inicio</th>
                            <th class="px-4 py-3">Jefe</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#003366]/10">
                        <tr v-for="p in projects" :key="p.id">
                            <td class="px-4 py-3 font-medium text-[#333]">
                                {{ p.name }}
                            </td>
                            <td class="px-4 py-3 text-[#666]">
                                {{ p.code ?? '—' }}
                            </td>
                            <td class="px-4 py-3 capitalize">{{ p.status }}</td>
                            <td class="px-4 py-3 text-[#666]">
                                {{ p.carta_inicio_at ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-[#666]">
                                {{ p.jefe_proyecto?.name ?? '—' }}
                            </td>
                        </tr>
                        <tr v-if="projects.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-[#666]"
                            >
                                Sin proyectos. Usa el formulario para crear el
                                primero.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="h-fit rounded-lg border border-[#003366]/15 bg-[#f8fafc] p-4"
            >
                <h2 class="text-sm font-semibold text-[#003366]">
                    Nuevo proyecto
                </h2>
                <form class="mt-4 space-y-3" @submit.prevent="submit">
                    <div class="space-y-1">
                        <Label for="name">Nombre</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="space-y-1">
                        <Label for="code">Código (opcional)</Label>
                        <Input id="code" v-model="form.code" autocomplete="off" />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div class="space-y-1">
                        <Label for="description">Descripción</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-1">
                            <Label for="carta_inicio_at">Carta inicio</Label>
                            <Input
                                id="carta_inicio_at"
                                v-model="form.carta_inicio_at"
                                type="date"
                            />
                            <InputError :message="form.errors.carta_inicio_at" />
                        </div>
                        <div class="space-y-1">
                            <Label for="status">Estado</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    v-for="s in statuses"
                                    :key="s"
                                    :value="s"
                                >
                                    {{ s }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-1">
                            <Label for="starts_at">Inicio</Label>
                            <Input
                                id="starts_at"
                                v-model="form.starts_at"
                                type="date"
                            />
                        </div>
                        <div class="space-y-1">
                            <Label for="ends_at">Fin</Label>
                            <Input
                                id="ends_at"
                                v-model="form.ends_at"
                                type="date"
                            />
                        </div>
                    </div>
                    <Button
                        type="submit"
                        class="w-full bg-[#003366] hover:bg-[#003366]/90"
                        :disabled="form.processing"
                    >
                        Guardar proyecto
                    </Button>
                </form>
            </div>
        </div>
    </WorkflowSection>
</template>
