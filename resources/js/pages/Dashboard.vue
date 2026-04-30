<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { Bell, LayoutGrid, Sparkles } from 'lucide-vue-next';
import { getWorkflowNavGroupsForUser } from '@/config/workflowNavigation';
import { formatDateTimeChile } from '@/lib/dateFormat';
import { getRecentProjects, type RecentProject } from '@/lib/recentProjects';
import { dashboard } from '@/routes';
import { kanban } from '@/routes/proyecto';
import { notificaciones } from '@/routes/sistema';

type Metric = {
    key: string;
    label: string;
    value: string;
    href: string;
    tone: 'primary' | 'accent' | 'neutral' | 'warning';
};

type FeedItem = {
    title: string;
    meta: string;
    href: string;
    status_label: string;
    status_tone: 'neutral' | 'warning';
};

type DashboardBlock = {
    id: string;
    title: string;
    subtitle: string;
    metrics: Metric[];
    items: FeedItem[];
    extra?: { projects_by_status: Record<string, number> };
};

type NotificationFeedRow = {
    id: string;
    title: string;
    body: string;
    kind: string | null;
    created_at: string | null;
    read_at: string | null;
    href: string;
};

const props = defineProps<{
    greeting: {
        title: string;
        subtitle: string;
    };
    blocks: DashboardBlock[];
    notification_feed: NotificationFeedRow[];
}>();

const page = usePage();
const roleSlugs = computed(
    () => (page.props.auth?.user?.role_slugs as string[] | undefined) ?? [],
);

const navGroups = computed(() =>
    getWorkflowNavGroupsForUser(roleSlugs.value),
);

const recentProjects = ref<RecentProject[]>([]);

onMounted(() => {
    recentProjects.value = getRecentProjects();
});

function formatFeedDate(iso: string | null): string {
    return formatDateTimeChile(iso);
}

function metricCardClass(tone: Metric['tone']): string {
    const base =
        'flex flex-col justify-between rounded-xl border p-4 shadow-sm transition hover:shadow-md';
    switch (tone) {
        case 'primary':
            return `${base} border-[#003366]/20 bg-white`;
        case 'accent':
            return `${base} border-[#F1C400]/40 bg-[#FFFBF0]`;
        case 'warning':
            return `${base} border-amber-300/80 bg-amber-50/90`;
        default:
            return `${base} border-[#003366]/10 bg-[#f8fafc]`;
    }
}

function statusPillClass(tone: FeedItem['status_tone']): string {
    if (tone === 'warning') {
        return 'bg-[#F1C400]/25 text-[#003366] ring-1 ring-[#F1C400]/50';
    }
    return 'bg-[#003366]/10 text-[#003366]';
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Inicio',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-1 flex-col gap-8 p-4 md:p-6">
        <header class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <Sparkles class="h-6 w-6 text-[#F1C400]" aria-hidden="true" />
                <h1
                    class="text-2xl font-semibold tracking-tight text-[#003366]"
                >
                    {{ greeting.title }}
                </h1>
            </div>
            <p class="max-w-3xl text-sm leading-relaxed text-[#666666]">
                {{ greeting.subtitle }}
            </p>
            <p class="text-xs leading-relaxed text-[#666666]">
                Referencia de producto:
                <strong class="font-medium text-[#003366]">Monday.com</strong>
                usa
                <em>boards</em> (tableros),
                <em>grupos</em> (secciones) e
                <em>ítems</em> con columnas de estado, persona y fechas. Aquí
                traducimos esa idea a cartera PMO, tablero Kanban, backlog y
                “mi trabajo”, con datos reales según tu rol.
            </p>
            <p
                v-if="roleSlugs.length"
                class="text-xs font-medium text-[#666666]"
            >
                Roles:
                <span class="text-[#003366]">{{ roleSlugs.join(' · ') }}</span>
            </p>
        </header>

        <div
            class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,340px)]"
        >
            <section
                class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between border-b border-[#003366]/10 bg-[#f8fafc] px-4 py-2"
                >
                    <h2
                        class="text-[11px] font-semibold uppercase tracking-wide text-[#666]"
                    >
                        Visitado recientemente
                    </h2>
                    <span class="text-[10px] text-[#999]">En este navegador</span>
                </div>
                <ul
                    v-if="recentProjects.length"
                    class="divide-y divide-[#003366]/8 p-2"
                >
                    <li v-for="p in recentProjects" :key="p.id">
                        <Link
                            :href="
                                kanban.url({
                                    query: { project_id: p.id },
                                })
                            "
                            class="flex flex-col rounded-md px-2 py-2 text-sm transition hover:bg-[#f8fafc]"
                        >
                            <span class="font-medium text-[#003366]">{{
                                p.name
                            }}</span>
                            <span
                                v-if="p.code"
                                class="text-xs text-[#666]"
                                >{{ p.code }}</span
                            >
                        </Link>
                    </li>
                </ul>
                <p v-else class="px-4 py-6 text-sm text-[#666]">
                    Abre un proyecto desde Kanban, Tabla u otra vista: se
                    guardará aquí para acceso rápido.
                </p>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between border-b border-[#003366]/10 bg-[#f8fafc] px-4 py-2"
                >
                    <div class="flex items-center gap-2">
                        <Bell
                            class="h-4 w-4 text-[#003366]"
                            aria-hidden="true"
                        />
                        <h2
                            class="text-[11px] font-semibold uppercase tracking-wide text-[#666]"
                        >
                            Buzón de actividad
                        </h2>
                    </div>
                    <Link
                        :href="notificaciones()"
                        class="text-[11px] font-medium text-[#003366] hover:underline"
                    >
                        Ver todo
                    </Link>
                </div>
                <ul
                    v-if="props.notification_feed.length"
                    class="divide-y divide-[#003366]/8"
                >
                    <li
                        v-for="n in props.notification_feed"
                        :key="n.id"
                        :class="{ 'opacity-70': n.read_at }"
                    >
                        <Link
                            :href="n.href"
                            class="block px-4 py-3 text-sm transition hover:bg-[#f8fafc]"
                        >
                            <p class="text-[10px] text-[#888]">
                                {{ formatFeedDate(n.created_at) }}
                            </p>
                            <p
                                v-if="n.kind"
                                class="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-[#003366]/80"
                            >
                                {{ n.kind }}
                            </p>
                            <p class="mt-1 font-medium text-[#333]">
                                {{ n.title }}
                            </p>
                            <p
                                v-if="n.body"
                                class="line-clamp-2 text-xs text-[#666]"
                            >
                                {{ n.body }}
                            </p>
                        </Link>
                    </li>
                </ul>
                <p v-else class="px-4 py-6 text-sm text-[#666]">
                    Sin avisos recientes. Las notificaciones de tareas y
                    proyectos aparecerán aquí.
                </p>
            </section>
        </div>

        <section
            v-for="block in props.blocks"
            :key="block.id"
            class="space-y-4"
        >
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-[#003366]">
                    {{ block.title }}
                </h2>
                <p class="mt-1 text-xs text-[#666666]">{{ block.subtitle }}</p>
            </div>

            <div
                v-if="block.metrics.length"
                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
            >
                <Link
                    v-for="m in block.metrics"
                    :key="m.key"
                    :href="m.href"
                    :class="metricCardClass(m.tone)"
                >
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-[#666]">{{
                        m.label
                    }}</span>
                    <span class="mt-2 block text-lg font-semibold tabular-nums text-[#003366]">{{
                        m.value
                    }}</span>
                </Link>
            </div>

            <div
                v-if="block.extra?.projects_by_status && block.id === 'pmo'"
                class="flex flex-wrap items-center gap-2 rounded-lg border border-[#003366]/10 bg-white px-3 py-2 text-xs"
            >
                <span class="font-medium text-[#666]">Proyectos por estado:</span>
                <span
                    v-for="(count, status) in block.extra.projects_by_status"
                    :key="status"
                    class="rounded-full bg-[#f0f4f8] px-2.5 py-0.5 font-medium text-[#003366]"
                >
                    {{ status }}: {{ count }}
                </span>
            </div>

            <div
                v-if="block.items.length"
                class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-sm"
            >
                <div
                    class="border-b border-[#003366]/10 bg-[#f8fafc] px-4 py-2 text-[11px] font-semibold uppercase tracking-wide text-[#666]"
                >
                    Prioritario / reciente
                </div>
                <ul class="divide-y divide-[#003366]/8">
                    <li v-for="(row, idx) in block.items" :key="idx">
                        <Link
                            :href="row.href"
                            class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm transition hover:bg-[#f8fafc]"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-[#333]">
                                    {{ row.title }}
                                </p>
                                <p class="truncate text-xs text-[#666]">
                                    {{ row.meta }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize"
                                :class="statusPillClass(row.status_tone)"
                            >
                                {{ row.status_label }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>
        </section>

        <section id="modulos" class="scroll-mt-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#003366]">
                Mapa de módulos
            </h2>
            <p class="mt-1 text-xs text-[#666666]">
                Acceso rápido a todos los tableros del menú lateral.
            </p>
            <div class="mt-4 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="group in navGroups"
                    :key="group.label"
                    class="rounded-lg border border-[#003366]/12 bg-[#f8fafc] p-4 shadow-sm"
                >
                    <h3
                        class="border-b border-[#003366]/10 pb-2 text-xs font-semibold uppercase tracking-wide text-[#666]"
                    >
                        {{ group.label }}
                    </h3>
                    <ul class="mt-3 space-y-1">
                        <li v-for="item in group.items" :key="item.title">
                            <Link
                                :href="item.href"
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-medium text-[#003366] hover:bg-white hover:underline"
                            >
                                <component
                                    :is="item.icon"
                                    v-if="item.icon"
                                    class="h-4 w-4 shrink-0 opacity-80"
                                />
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <p class="text-center text-[11px] text-[#999]">
            <LayoutGrid class="mr-1 inline h-3.5 w-3.5 align-text-bottom opacity-70" />
            Tip: en Monday, el trabajo vive en
            <strong>boards</strong>; aquí equivalen a Tablero macro, Kanban,
            Backlog y vistas de coordinación.
        </p>
    </div>
</template>
