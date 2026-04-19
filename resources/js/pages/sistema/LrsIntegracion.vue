<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { lrs } from '@/routes/sistema';

defineProps<{
    lrs_enabled: boolean;
    lrs_endpoint: string | null;
    lrs_has_key: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Integraciones', href: lrs() },
        ],
    },
});
</script>

<template>
    <Head title="Integraciones — LRS / xAPI" />

    <WorkflowSection
        context-label="Sistema y cumplimiento"
        title="Integraciones — LRS / xAPI (CFRD)"
        description="Conexión con el LRS institucional mediante statements xAPI; aquí ves si el conector está configurado en el entorno."
    >
        <dl class="space-y-3 rounded-lg border border-[#003366]/15 bg-[#f8fafc] p-4 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-[#666]">Activado</dt>
                <dd class="font-medium text-[#003366]">{{ lrs_enabled ? 'Sí' : 'No' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-[#666]">Endpoint</dt>
                <dd class="break-all text-right font-mono text-xs">
                    {{ lrs_endpoint ?? '—' }}
                </dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-[#666]">Clave configurada</dt>
                <dd>{{ lrs_has_key ? 'Sí' : 'No' }}</dd>
            </div>
        </dl>
        <p class="mt-4 text-sm text-[#666]">
            Define <code class="rounded bg-neutral-100 px-1">WORKFLOW_LRS_ENABLED</code>,
            <code class="rounded bg-neutral-100 px-1">WORKFLOW_LRS_ENDPOINT</code> y
            <code class="rounded bg-neutral-100 px-1">WORKFLOW_LRS_KEY</code> en
            <code class="rounded bg-neutral-100 px-1">.env</code> cuando se integre el
            conector.
        </p>
    </WorkflowSection>
</template>
