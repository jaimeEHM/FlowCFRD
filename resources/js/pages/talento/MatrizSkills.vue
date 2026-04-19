<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import UserAvatarInline from '@/components/workflow/UserAvatarInline.vue';
import { equiposCarga } from '@/routes/coordinacion';
import { dashboard } from '@/routes';
import { mapaRelaciones, matrizSkills } from '@/routes/talento';

type Skill = {
    id: number;
    name: string;
    description: string | null;
    users: {
        id: number;
        name: string;
        avatar?: string | null;
        pivot: { level: number };
    }[];
};

type UserS = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    skills: { id: number; name: string; pivot: { level: number } }[];
};

defineProps<{
    skills: Skill[];
    users_with_skills: UserS[];
}>();

const relatedLinks = [
    { title: 'Mapa de relaciones', href: mapaRelaciones() },
    { title: 'Equipos y carga', href: equiposCarga() },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Matriz de skills', href: matrizSkills() },
        ],
    },
});
</script>

<template>
    <Head title="Skills" />

    <WorkflowSection
        context-label="Talento y mejora continua"
        title="Matriz de competencias (skills)"
        description="Diccionario de skills y niveles declarados por persona (nivel 1–5)."
        :related-links="relatedLinks"
    >
        <div class="space-y-6">
            <div
                class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white"
            >
                <table class="w-full min-w-[480px] text-sm">
                    <thead class="bg-[#f8fafc] text-xs font-semibold text-[#003366]">
                        <tr>
                            <th class="px-4 py-2 text-left">Skill</th>
                            <th class="px-4 py-2 text-left">Personas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="s in skills" :key="s.id">
                            <td class="px-4 py-2">
                                <span class="font-medium">{{ s.name }}</span>
                                <p v-if="s.description" class="text-xs text-[#666]">
                                    {{ s.description }}
                                </p>
                            </td>
                            <td class="px-4 py-2 text-[#333]">
                                <span
                                    v-for="u in s.users"
                                    :key="u.id"
                                    class="mr-2 mb-1 inline-flex items-center gap-1 rounded bg-[#f0f4f8] px-2 py-0.5 text-xs"
                                >
                                    <UserAvatarInline
                                        :name="u.name"
                                        :avatar="u.avatar"
                                        size="xs"
                                        :show-name="false"
                                    />
                                    <span>{{ u.name }} ({{ u.pivot.level }})</span>
                                </span>
                                <span v-if="s.users.length === 0" class="text-[#666]"
                                    >—</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div
                class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-[#f8fafc] p-4"
            >
                <h3 class="text-sm font-semibold text-[#003366]">Por persona</h3>
                <ul class="mt-2 space-y-2 text-sm">
                    <li
                        v-for="u in users_with_skills"
                        :key="u.id"
                        class="flex flex-wrap items-start gap-2"
                    >
                        <UserAvatarInline
                            :name="u.name"
                            :avatar="u.avatar"
                            size="sm"
                        />
                        <span>
                        —
                        <span
                            v-for="sk in u.skills"
                            :key="sk.id"
                            class="ml-1 text-[#333]"
                        >
                            {{ sk.name }} ({{ sk.pivot.level }});
                        </span>
                        </span>
                    </li>
                    <li v-if="users_with_skills.length === 0" class="text-[#666]">
                        Aún no hay niveles registrados en el seeder.
                    </li>
                </ul>
            </div>
        </div>
    </WorkflowSection>
</template>
