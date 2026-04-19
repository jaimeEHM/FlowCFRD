<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { auditoria, lrs, notificaciones } from '@/routes/sistema';

type Log = {
    id: number;
    action: string;
    auditable_type: string | null;
    auditable_id: number | null;
    properties: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
    user: { name: string; email: string } | null;
};

defineProps<{
    logs: Log[];
}>();

const relatedLinks = [
    { title: 'Notificaciones', href: notificaciones() },
    { title: 'LRS / integración', href: lrs() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Auditoría', href: auditoria() },
        ],
    },
});
</script>

<template>
    <Head title="Auditoría" />

    <WorkflowSection
        context-label="Sistema y cumplimiento"
        title="Auditoría y trazabilidad"
        description="Últimos eventos registrados (creación de proyectos, cambios de estado, etc.)."
        :related-links="relatedLinks"
    >
        <div
            class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white shadow-sm"
        >
            <table class="w-full min-w-[720px] text-xs">
                <thead class="bg-[#f8fafc] font-semibold text-[#003366]">
                    <tr>
                        <th class="px-3 py-2 text-left">Fecha</th>
                        <th class="px-3 py-2 text-left">Usuario</th>
                        <th class="px-3 py-2 text-left">Acción</th>
                        <th class="px-3 py-2 text-left">Entidad</th>
                        <th class="px-3 py-2 text-left">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y font-mono text-[#333]">
                    <tr v-for="l in logs" :key="l.id">
                        <td class="px-3 py-2 whitespace-nowrap">{{ l.created_at }}</td>
                        <td class="px-3 py-2">
                            {{ l.user?.email ?? '—' }}
                        </td>
                        <td class="px-3 py-2">{{ l.action }}</td>
                        <td class="px-3 py-2">
                            {{ l.auditable_type }} #{{ l.auditable_id }}
                        </td>
                        <td class="px-3 py-2">{{ l.ip_address ?? '—' }}</td>
                    </tr>
                    <tr v-if="logs.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-[#666]">
                            Sin registros de auditoría todavía.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </WorkflowSection>
</template>
