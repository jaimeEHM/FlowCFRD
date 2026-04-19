<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import UserAvatarInline from '@/components/workflow/UserAvatarInline.vue';
import { misTareas } from '@/routes/colaborador';
import { dashboard } from '@/routes';
import {
    backlogTareas,
    equiposCarga,
    validacionAvances,
} from '@/routes/coordinacion';

type Row = {
    id: number;
    /** Nombre sin cargo (listado). */
    nombre: string;
    /** Nombre completo como en BD (incluye cargo si aplica). */
    name: string;
    cargo: string | null;
    email: string;
    avatar?: string | null;
    roles: string[];
    tareas_abiertas: number;
};

defineProps<{
    users: Row[];
}>();

const relatedLinks = [
    { title: 'Backlog', href: backlogTareas() },
    { title: 'Validación de avances', href: validacionAvances() },
    { title: 'Mis tareas', href: misTareas() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Equipos y carga', href: equiposCarga() },
        ],
    },
});
</script>

<template>
    <Head title="Equipos y carga" />

    <WorkflowSection
        context-label="Coordinación — personas y backlog"
        title="Equipos y distribución de carga"
        description="Usuarios del sistema con cargo CFRD, roles y cantidad de tareas no cerradas."
        :related-links="relatedLinks"
    >
        <div
            class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white shadow-sm"
        >
            <table class="w-full min-w-[800px] text-left text-sm">
                <thead
                    class="border-b border-[#003366]/15 bg-[#f8fafc] text-xs font-semibold uppercase text-[#003366]"
                >
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Cargo</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Roles</th>
                        <th class="px-4 py-3 text-right">Tareas abiertas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#003366]/10">
                    <tr v-for="u in users" :key="u.id">
                        <td class="px-4 py-3 font-medium">
                            <UserAvatarInline
                                :name="u.nombre"
                                :avatar="u.avatar"
                                size="sm"
                            />
                        </td>
                        <td class="max-w-[220px] px-4 py-3 text-[#333]">
                            {{ u.cargo ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-[#666]">{{ u.email }}</td>
                        <td class="px-4 py-3 text-xs text-[#666]">
                            {{ u.roles.join(', ') }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ u.tareas_abiertas }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </WorkflowSection>
</template>
