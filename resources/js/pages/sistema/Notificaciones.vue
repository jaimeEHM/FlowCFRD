<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { notificaciones } from '@/routes/sistema';

type N = {
    id: string;
    type: string;
    data: {
        kind?: string;
        title?: string;
        body?: string;
        meta?: Record<string, unknown>;
    };
    read_at: string | null;
    created_at: string;
};

defineProps<{
    notifications: N[];
}>();

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
    >
        <ul class="space-y-2">
            <li
                v-for="n in notifications"
                :key="n.id"
                class="rounded-lg border border-[#003366]/15 bg-white p-3 text-sm shadow-sm"
                :class="{ 'opacity-60': n.read_at }"
            >
                <p class="text-xs text-[#666]">{{ n.created_at }}</p>
                <p
                    v-if="n.data?.kind"
                    class="text-[11px] font-medium uppercase tracking-wide text-[#003366]/80"
                >
                    {{ n.data.kind }}
                </p>
                <p v-if="n.data?.title" class="mt-1 font-semibold text-[#1a1a1a]">
                    {{ n.data.title }}
                </p>
                <p v-if="n.data?.body" class="mt-0.5 text-[#444]">
                    {{ n.data.body }}
                </p>
                <p class="mt-1 font-mono text-[10px] text-[#888]">{{ n.type }}</p>
                <pre
                    v-if="n.data?.meta && Object.keys(n.data.meta).length > 0"
                    class="mt-1 max-h-24 overflow-auto rounded bg-[#f8fafc] p-2 text-[10px] text-[#555]"
                    >{{ JSON.stringify(n.data.meta, null, 2) }}</pre
                >
            </li>
            <li v-if="notifications.length === 0" class="text-[#666]">
                No hay notificaciones en la bandeja.
            </li>
        </ul>
    </WorkflowSection>
</template>
