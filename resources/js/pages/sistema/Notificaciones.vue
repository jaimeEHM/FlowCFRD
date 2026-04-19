<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { auditoria, lrs, notificaciones } from '@/routes/sistema';

type N = {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
};

defineProps<{
    notifications: N[];
}>();

const relatedLinks = [
    { title: 'Auditoría', href: auditoria() },
    { title: 'LRS / integración', href: lrs() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Notificaciones', href: notificaciones() },
        ],
    },
});
</script>

<template>
    <Head title="Notificaciones" />

    <WorkflowSection
        context-label="Sistema y cumplimiento"
        title="Notificaciones y avisos"
        description="Cola de notificaciones de base de datos (Laravel notifications)."
        :related-links="relatedLinks"
    >
        <ul class="space-y-2">
            <li
                v-for="n in notifications"
                :key="n.id"
                class="rounded-lg border border-[#003366]/15 bg-white p-3 text-sm shadow-sm"
                :class="{ 'opacity-60': n.read_at }"
            >
                <p class="text-xs text-[#666]">{{ n.created_at }}</p>
                <p class="font-mono text-xs text-[#003366]">{{ n.type }}</p>
                <pre class="mt-1 max-h-32 overflow-auto text-xs text-[#333]">{{
                    JSON.stringify(n.data, null, 2)
                }}</pre>
            </li>
            <li v-if="notifications.length === 0" class="text-[#666]">
                No hay notificaciones en la bandeja.
            </li>
        </ul>
    </WorkflowSection>
</template>
