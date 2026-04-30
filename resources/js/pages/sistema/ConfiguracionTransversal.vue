<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

const props = defineProps<{
    workloadThresholds: {
        tasks_per_day: number;
        alert_days: number;
        danger_days: number;
        overload_days: number;
        parallel_alert_projects: number;
        parallel_danger_projects: number;
    };
    kanbanDefaultStatuses: {
        id: number;
        key: string;
        label: string;
        position: number;
    }[];
}>();

const form = useForm({
    tasks_per_day: props.workloadThresholds.tasks_per_day,
    alert_days: props.workloadThresholds.alert_days,
    danger_days: props.workloadThresholds.danger_days,
    overload_days: props.workloadThresholds.overload_days,
    parallel_alert_projects: props.workloadThresholds.parallel_alert_projects,
    parallel_danger_projects: props.workloadThresholds.parallel_danger_projects,
});

const statusCreateForm = useForm({
    label: '',
});
const statusEditForm = useForm({
    label: '',
});
const editingStatusId = ref<number | null>(null);
const draggableStatuses = ref<{ id: number; key: string; label: string; position: number }[]>([]);

watch(
    () => props.kanbanDefaultStatuses,
    (list) => {
        draggableStatuses.value = list.map((s) => ({ ...s }));
    },
    { immediate: true, deep: true },
);

const submit = () => {
    form.patch('/sistema/configuracion-transversal', { preserveScroll: true });
};

const submitCreateStatus = () => {
    if (!statusCreateForm.label.trim()) {
        return;
    }
    statusCreateForm.post('/sistema/configuracion-transversal/kanban-estados', {
        preserveScroll: true,
        onSuccess: () => statusCreateForm.reset(),
    });
};

const startEditStatus = (status: { id: number; label: string }) => {
    editingStatusId.value = status.id;
    statusEditForm.label = status.label;
};

const saveEditStatus = () => {
    if (editingStatusId.value === null) {
        return;
    }
    statusEditForm.patch(`/sistema/configuracion-transversal/kanban-estados/${editingStatusId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingStatusId.value = null;
            statusEditForm.reset();
        },
    });
};

const deleteStatus = (id: number) => {
    statusCreateForm.delete(`/sistema/configuracion-transversal/kanban-estados/${id}`, { preserveScroll: true });
};

const persistStatusOrder = () => {
    if (draggableStatuses.value.length === 0) {
        return;
    }
    statusCreateForm.patch('/sistema/configuracion-transversal/kanban-estados/orden', {
        status_ids: draggableStatuses.value.map((s) => s.id),
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Configuración transversal', href: '/sistema/configuracion-transversal' },
        ],
    },
});
</script>

<template>
    <Head title="Configuración transversal" />

    <WorkflowSection
        context-label="Sistema y cumplimiento"
        title="Configuración transversal"
        description="Parámetros operativos usados por módulos compartidos (por ejemplo, alertas de carga)."
    >
        <div class="max-w-3xl space-y-4 rounded-xl border border-[#003366]/12 bg-white p-4 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <Label for="tasks_per_day">Tareas por día (tolerancia diaria por persona)</Label>
                    <Input id="tasks_per_day" v-model.number="form.tasks_per_day" type="number" min="1" />
                    <p class="text-xs text-slate-600">
                        Define cuántas tareas abiertas por día toleras como capacidad normal.
                    </p>
                </div>
                <div class="space-y-1">
                    <Label for="alert_days">Días para alerta</Label>
                    <Input id="alert_days" v-model.number="form.alert_days" type="number" min="1" />
                </div>
                <div class="space-y-1">
                    <Label for="danger_days">Días para peligro</Label>
                    <Input id="danger_days" v-model.number="form.danger_days" type="number" min="1" />
                </div>
                <div class="space-y-1">
                    <Label for="overload_days">Días para sobrecarga</Label>
                    <Input id="overload_days" v-model.number="form.overload_days" type="number" min="1" />
                </div>
                <div class="space-y-1">
                    <Label for="parallel_alert_projects">Alerta por proyectos en paralelo</Label>
                    <Input id="parallel_alert_projects" v-model.number="form.parallel_alert_projects" type="number" min="1" />
                </div>
                <div class="space-y-1">
                    <Label for="parallel_danger_projects">Peligro por proyectos en paralelo</Label>
                    <Input id="parallel_danger_projects" v-model.number="form.parallel_danger_projects" type="number" min="1" />
                </div>
            </div>

            <div class="rounded-md border border-[#003366]/10 bg-[#f8fafc] px-3 py-2 text-xs text-slate-700">
                Cálculo: días estimados = tareas abiertas de la persona / tolerancia diaria.
                Colores: alerta (amarillo), peligro (naranjo), sobrecarga (rojo). Paralelismo: alerta/peligro según número de proyectos simultáneos.
            </div>

            <div class="pt-2">
                <Button class="bg-[#003366] hover:bg-[#00264d]" :disabled="form.processing" @click="submit">
                    Guardar configuración
                </Button>
            </div>
        </div>

        <div class="max-w-3xl space-y-4 rounded-xl border border-[#003366]/12 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#003366]">Estados iniciales Kanban (transversal)</p>
            <p class="text-xs text-slate-600">
                Se usan como plantilla inicial para proyectos que no tengan configuración propia.
            </p>

            <div class="flex gap-2">
                <Input v-model="statusCreateForm.label" placeholder="Nuevo estado inicial (ej: bloqueada)" />
                <Button class="bg-[#003366] hover:bg-[#00264d]" @click="submitCreateStatus">
                    Añadir
                </Button>
            </div>

            <div class="space-y-2 rounded-md border border-[#003366]/10 p-3">
                <VueDraggable
                    v-model="draggableStatuses"
                    class="space-y-2"
                    :animation="180"
                    ghost-class="opacity-50"
                    @end="persistStatusOrder"
                >
                <div
                    v-for="status in draggableStatuses"
                    :key="status.id"
                    class="flex items-center justify-between gap-2 rounded-md border border-[#003366]/10 p-2"
                >
                    <div class="min-w-0 flex-1 text-sm">
                        <template v-if="editingStatusId === status.id">
                            <Input v-model="statusEditForm.label" />
                        </template>
                        <template v-else>
                            {{ status.label }}
                            <span class="ml-2 font-mono text-xs text-slate-500">{{ status.key }}</span>
                        </template>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            v-if="editingStatusId !== status.id"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="startEditStatus(status)"
                        >
                            Editar
                        </Button>
                        <Button
                            v-else
                            type="button"
                            size="sm"
                            class="bg-[#003366] hover:bg-[#00264d]"
                            @click="saveEditStatus"
                        >
                            Guardar
                        </Button>
                        <Button type="button" variant="outline" size="sm" @click="deleteStatus(status.id)">
                            Eliminar
                        </Button>
                    </div>
                </div>
                </VueDraggable>
                <p v-if="draggableStatuses.length === 0" class="text-xs text-slate-500">
                    Sin estados iniciales configurados.
                </p>
            </div>
        </div>
    </WorkflowSection>
</template>

