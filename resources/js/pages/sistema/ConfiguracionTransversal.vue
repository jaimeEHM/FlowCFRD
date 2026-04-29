<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
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
    };
}>();

const form = useForm({
    tasks_per_day: props.workloadThresholds.tasks_per_day,
    alert_days: props.workloadThresholds.alert_days,
    danger_days: props.workloadThresholds.danger_days,
    overload_days: props.workloadThresholds.overload_days,
});

const submit = () => {
    form.patch('/sistema/configuracion-transversal', { preserveScroll: true });
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
            </div>

            <div class="rounded-md border border-[#003366]/10 bg-[#f8fafc] px-3 py-2 text-xs text-slate-700">
                Cálculo: días estimados = tareas abiertas de la persona / tolerancia diaria.
                Colores: alerta (amarillo), peligro (naranjo), sobrecarga (rojo).
            </div>

            <div class="pt-2">
                <Button class="bg-[#003366] hover:bg-[#00264d]" :disabled="form.processing" @click="submit">
                    Guardar configuración
                </Button>
            </div>
        </div>
    </WorkflowSection>
</template>

