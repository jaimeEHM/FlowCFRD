<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { addRecentProject } from '@/lib/recentProjects';
import {
    calendario,
    cronograma,
    kanban,
    minutas,
    tabla,
} from '@/routes/proyecto';

type Proj = { id: number; name: string; code: string | null };

const props = defineProps<{
    active:
        | 'tabla'
        | 'kanban'
        | 'cronograma'
        | 'calendario'
        | 'minutas';
    project: Proj | null;
    projects: Proj[];
}>();

const tabClass = (name: typeof props.active) => {
    const base =
        'inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium transition-colors';
    if (props.active === name) {
        return `${base} bg-[#003366] text-white shadow-sm`;
    }
    return `${base} text-[#003366] hover:bg-[#003366]/10`;
};

onMounted(() => {
    if (props.project) {
        addRecentProject({
            id: props.project.id,
            name: props.project.name,
            code: props.project.code,
        });
    }
});

function q() {
    return props.project
        ? { query: { project_id: props.project.id } }
        : undefined;
}
</script>

<template>
    <div
        v-if="project"
        class="mb-4 flex flex-col gap-3 rounded-xl border border-[#003366]/12 bg-[#f8fafc] p-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
    >
        <p class="px-2 text-xs font-medium uppercase tracking-wide text-[#003366]">
            Vista del proyecto
        </p>
        <nav
            class="flex flex-wrap gap-1"
            aria-label="Vistas del proyecto"
        >
            <Link :class="tabClass('tabla')" :href="tabla.url(q())">
                Lista de tareas
            </Link>
            <Link :class="tabClass('kanban')" :href="kanban.url(q())">
                Kanban
            </Link>
            <Link
                :class="tabClass('cronograma')"
                :href="cronograma.url(q())"
            >
                Cronograma
            </Link>
            <Link
                :class="tabClass('calendario')"
                :href="calendario.url(q())"
            >
                Calendario
            </Link>
            <Link
                :class="tabClass('minutas')"
                :href="minutas.url(q())"
            >
                Minutas
            </Link>
        </nav>
    </div>
</template>
