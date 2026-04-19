<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';
import { proyectos, tableroMacro } from '@/routes/pmo';

type Project = {
    id: number;
    name: string;
    code: string | null;
    status: string;
    carta_inicio_at: string | null;
    jefe_proyecto: { name: string; email: string } | null;
    created_by: { name: string } | null;
};

defineProps<{
    projects: Project[];
    statuses: string[];
}>();

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
        description="Listado rápido. Para descripción, alta y edición completa usa el tablero macro (botón inferior)."
    >
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <Button
                as-child
                class="bg-[#003366] hover:bg-[#003366]/90"
            >
                <Link :href="tableroMacro()"> Abrir tablero macro </Link>
            </Button>
            <p class="text-sm text-[#666]">
                Allí: clic en el nombre del proyecto, FAB + para crear.
            </p>
        </div>

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
                            Sin proyectos. Créalos desde el tablero macro.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </WorkflowSection>
</template>
