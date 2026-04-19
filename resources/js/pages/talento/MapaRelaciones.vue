<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import RelacionesFlow from '@/components/workflow/RelacionesFlow.vue';
import { proyectos } from '@/routes/pmo';
import { dashboard } from '@/routes';
import { mapaRelaciones, matrizSkills } from '@/routes/talento';

type Edge = {
    user_id: number;
    project_id: number;
    user_name: string;
    project_name: string;
    tareas_vinculadas: number;
};

defineProps<{
    edges: Edge[];
    nodes_summary: { usuarios: number; proyectos: number; relaciones: number };
}>();

const relatedLinks = [
    { title: 'Matriz de skills', href: matrizSkills() },
    { title: 'Proyectos', href: proyectos() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Mapa de relaciones', href: mapaRelaciones() },
        ],
    },
});
</script>

<template>
    <Head title="Mapa de relaciones" />

    <WorkflowSection
        context-label="Talento y mejora continua"
        title="Mapa colaboradores–proyectos"
        description="Red tipo telaraña: colaboradores y proyectos en el mismo plano, posiciones calculadas para cruzar relaciones de forma orgánica. Arrastra el lienzo, usa zoom (controles o rueda) y puedes mover nodos. Las aristas curvas muestran cuántas tareas vinculan cada par persona–proyecto."
        :related-links="relatedLinks"
    >
        <p class="text-sm text-[#666]">
            Resumen: {{ nodes_summary.usuarios }} usuarios con tareas,
            {{ nodes_summary.proyectos }} proyectos con asignaciones,
            {{ nodes_summary.relaciones }} relaciones únicas.
        </p>

        <RelacionesFlow v-if="edges.length > 0" class="mt-4" :edges="edges" />

        <div
            v-else
            class="mt-4 rounded-lg border border-dashed border-[#003366]/30 p-8 text-center text-sm text-[#666]"
        >
            Sin aristas: asigna tareas a usuarios para ver el grafo.
        </div>

        <div
            class="mt-4 overflow-x-auto rounded-lg border border-[#003366]/15 bg-white"
        >
            <table class="w-full min-w-[480px] text-sm">
                <thead class="bg-[#f8fafc] text-xs font-semibold text-[#003366]">
                    <tr>
                        <th class="px-4 py-2 text-left">Persona</th>
                        <th class="px-4 py-2 text-left">Proyecto</th>
                        <th class="px-4 py-2 text-right">Tareas</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="(e, i) in edges" :key="i">
                        <td class="px-4 py-2">{{ e.user_name }}</td>
                        <td class="px-4 py-2">{{ e.project_name }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">
                            {{ e.tareas_vinculadas }}
                        </td>
                    </tr>
                    <tr v-if="edges.length === 0">
                        <td colspan="3" class="px-4 py-6 text-center text-[#666]">
                            Sin filas en el detalle.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </WorkflowSection>
</template>
