<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { formatDateChile } from '@/lib/dateFormat';
import { dashboard } from '@/routes';

type Row = {
    id: number;
    name: string;
    cargo: string | null;
    email: string;
    roles: string[];
    areas: string[];
    created_at: string | null;
};

const props = defineProps<{
    users: Row[];
    available_roles: string[];
    available_areas: string[];
}>();

const toggleRole = (user: Row, role: string, enabled: boolean) => {
    const next = enabled
        ? Array.from(new Set([...user.roles, role]))
        : user.roles.filter((r) => r !== role);

    if (next.length === 0) {
        return;
    }

    router.patch(`/sistema/usuarios-roles/${user.id}`, { roles: next }, { preserveScroll: true });
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Usuarios y roles', href: '/sistema/usuarios-roles' },
        ],
    },
});
</script>

<template>
    <Head title="Usuarios y roles" />

    <WorkflowSection
        context-label="Sistema y permisos"
        title="Gestión interna de roles"
        description="Admin, PMO y Coordinador pueden asignar o actualizar roles de operación por usuario."
    >
        <div class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white shadow-sm">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="border-b border-[#003366]/15 bg-[#f8fafc] text-xs font-semibold uppercase text-[#003366]">
                    <tr>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3">Cargo</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Roles</th>
                        <th class="px-4 py-3">Áreas</th>
                        <th class="px-4 py-3">Alta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#003366]/10">
                    <tr v-for="u in users" :key="u.id">
                        <td class="px-4 py-3 font-medium text-[#1f2937]">{{ u.name }}</td>
                        <td class="px-4 py-3 text-[#4b5563]">{{ u.cargo ?? '—' }}</td>
                        <td class="px-4 py-3 text-[#6b7280]">{{ u.email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-3">
                                <label
                                    v-for="role in available_roles"
                                    :key="`${u.id}-${role}`"
                                    class="inline-flex items-center gap-1.5 text-xs text-[#374151]"
                                >
                                    <input
                                        type="checkbox"
                                        class="h-3.5 w-3.5 rounded border-[#9ca3af] text-[#223c6a] focus:ring-[#223c6a]"
                                        :checked="u.roles.includes(role)"
                                        @change="toggleRole(u, role, ($event.target as HTMLInputElement).checked)"
                                    />
                                    <span>{{ role }}</span>
                                </label>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-[#4b5563]">
                            {{ u.areas.length > 0 ? u.areas.join(', ') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-[#6b7280]">{{ formatDateChile(u.created_at) }}</td>
                    </tr>
                    <tr v-if="users.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-[#6b7280]">
                            No hay usuarios registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </WorkflowSection>
</template>

