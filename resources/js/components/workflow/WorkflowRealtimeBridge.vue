<script setup lang="ts">
/**
 * Suscripción Echo (Reverb) al canal privado del usuario.
 * Ante cambios en tareas/proyectos relevantes, avisa y recarga props Inertia.
 */
import { useEcho } from '@laravel/echo-vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { workflowReloadOnlyKeys } from '@/lib/workflowInertiaReloadOnly';

const props = defineProps<{
    userId: number;
}>();

const page = usePage();
const reloadTimer = ref<ReturnType<typeof setTimeout> | null>(null);

function scheduleReload(): void {
    if (reloadTimer.value !== null) {
        clearTimeout(reloadTimer.value);
    }
    reloadTimer.value = setTimeout(() => {
        const c = page.component;
        const componentName = typeof c === 'string' ? c : undefined;
        const only = workflowReloadOnlyKeys(componentName);
        if (only !== null && only.length > 0) {
            router.reload({ only });
        } else {
            router.reload();
        }
    }, 400);
}

function onRealtimePayload(payload: unknown): void {
    const p = payload as {
        task?: { title?: string };
        project?: { name?: string };
        skill?: { name?: string };
        minute?: { title?: string };
    };
    const label =
        p.task?.title ??
        p.minute?.title ??
        p.skill?.name ??
        p.project?.name ??
        'Datos actualizados';
    toast.info('Actualización en tiempo real', { description: label });
    scheduleReload();
}

useEcho(
    `App.Models.User.${props.userId}`,
    [
        'workflow.task.changed',
        'workflow.project.changed',
        'workflow.skill_validation.changed',
        'workflow.project_minute.created',
    ],
    onRealtimePayload,
    [props.userId],
);
</script>

<template>
    <span aria-hidden="true" class="sr-only">WebSocket Workflow</span>
</template>
