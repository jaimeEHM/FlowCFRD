<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import UserAvatarInline from '@/components/workflow/UserAvatarInline.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { validacionAvances } from '@/routes/coordinacion';
import { update as patchTaskVal } from '@/routes/coordinacion/validacion-avances/tareas';
import { update as patchSkillVal } from '@/routes/coordinacion/validacion-avances/skills';

type UrgentTask = {
    id: number;
    title: string;
    project: { name: string };
    assignee: { name: string; avatar?: string | null } | null;
};

type SkillVal = {
    id: number;
    status: string;
    skill: { name: string };
    subject: { name: string; avatar?: string | null };
    validator: { name: string; avatar?: string | null };
};

defineProps<{
    urgent_tasks: UrgentTask[];
    skill_validations: SkillVal[];
}>();

function resolveTask(id: number, status: 'aprobada' | 'rechazada') {
    router.patch(patchTaskVal.url(id), { validation_status: status });
}

function resolveSkill(id: number, status: 'aprobada' | 'rechazada') {
    router.patch(patchSkillVal.url(id), { status });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Validación de avances', href: validacionAvances() },
        ],
    },
});
</script>

<template>
    <Head title="Validación de avances" />

    <WorkflowSection
        context-label="Coordinación — personas y backlog"
        title="Validación de avances"
        description="Tareas urgentes pendientes y validaciones 360° de skills."
    >
        <div class="space-y-8">
            <div>
                <h2 class="text-sm font-semibold text-[#003366]">
                    Urgentes pendientes de validación
                </h2>
                <div
                    class="mt-2 overflow-x-auto rounded-lg border border-[#003366]/15 bg-white"
                >
                    <table class="w-full min-w-[560px] text-sm">
                        <thead
                            class="bg-[#f8fafc] text-xs font-semibold uppercase text-[#003366]"
                        >
                            <tr>
                                <th class="px-4 py-2 text-left">Tarea</th>
                                <th class="px-4 py-2 text-left">Proyecto</th>
                                <th class="px-4 py-2 text-left">Asignado</th>
                                <th class="px-4 py-2 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="t in urgent_tasks" :key="t.id">
                                <td class="px-4 py-2">{{ t.title }}</td>
                                <td class="px-4 py-2 text-[#666]">
                                    {{ t.project.name }}
                                </td>
                                <td class="px-4 py-2">
                                    <UserAvatarInline
                                        v-if="t.assignee"
                                        :name="t.assignee.name"
                                        :avatar="t.assignee.avatar"
                                        size="xs"
                                    />
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-2 text-right space-x-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="border-green-600 text-green-700"
                                        type="button"
                                        @click="resolveTask(t.id, 'aprobada')"
                                    >
                                        Aprobar
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="border-red-300 text-red-700"
                                        type="button"
                                        @click="resolveTask(t.id, 'rechazada')"
                                    >
                                        Rechazar
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="urgent_tasks.length === 0">
                                <td colspan="4" class="px-4 py-6 text-[#666]">
                                    No hay urgentes pendientes.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-[#003366]">
                    Validaciones de skills (pendientes)
                </h2>
                <div
                    class="mt-2 overflow-x-auto rounded-lg border border-[#003366]/15 bg-white"
                >
                    <table class="w-full min-w-[560px] text-sm">
                        <thead
                            class="bg-[#f8fafc] text-xs font-semibold uppercase text-[#003366]"
                        >
                            <tr>
                                <th class="px-4 py-2 text-left">Skill</th>
                                <th class="px-4 py-2 text-left">Evaluado</th>
                                <th class="px-4 py-2 text-left">Validador</th>
                                <th class="px-4 py-2 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="s in skill_validations" :key="s.id">
                                <td class="px-4 py-2">{{ s.skill.name }}</td>
                                <td class="px-4 py-2">
                                    <UserAvatarInline
                                        :name="s.subject.name"
                                        :avatar="s.subject.avatar"
                                        size="xs"
                                    />
                                </td>
                                <td class="px-4 py-2 text-[#666]">
                                    <UserAvatarInline
                                        :name="s.validator.name"
                                        :avatar="s.validator.avatar"
                                        size="xs"
                                    />
                                </td>
                                <td class="px-4 py-2 text-right space-x-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="border-green-600 text-green-700"
                                        type="button"
                                        @click="resolveSkill(s.id, 'aprobada')"
                                    >
                                        Aprobar
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="border-red-300 text-red-700"
                                        type="button"
                                        @click="resolveSkill(s.id, 'rechazada')"
                                    >
                                        Rechazar
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="skill_validations.length === 0">
                                <td colspan="4" class="px-4 py-6 text-[#666]">
                                    No hay validaciones de skills pendientes.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </WorkflowSection>
</template>
