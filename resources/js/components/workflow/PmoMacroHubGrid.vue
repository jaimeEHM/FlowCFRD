<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Bell,
    CheckCircle2,
    ClipboardList,
    FileText,
    FolderOpen,
    Kanban,
    LayoutGrid,
    ListTodo,
    Network,
    Plug,
    ScrollText,
    Share2,
    Users,
    Zap,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';

export type HubLink = {
    group: string;
    title: string;
    href: string;
    icon: string;
};

const props = defineProps<{
    links: HubLink[];
}>();

const iconMap: Record<string, LucideIcon> = {
    LayoutGrid,
    FolderOpen,
    Users,
    ListTodo,
    CheckCircle2,
    Kanban,
    FileText,
    ClipboardList,
    Zap,
    Share2,
    Network,
    ScrollText,
    Bell,
    Plug,
};

const grouped = computed(() => {
    const m = new Map<string, HubLink[]>();
    for (const l of props.links) {
        const g = l.group;
        if (!m.has(g)) {
            m.set(g, []);
        }
        m.get(g)!.push(l);
    }
    return [...m.entries()];
});

function iconFor(name: string): LucideIcon {
    return iconMap[name] ?? LayoutGrid;
}
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
    >
        <div
            class="border-b border-[#003366]/15 bg-[#f1f5f9] px-4 py-3 shadow-sm"
        >
            <p
                class="text-xs font-semibold uppercase tracking-wide text-[#003366]"
            >
                Accesos rápidos (mismo criterio que el menú lateral)
            </p>
            <p class="mt-0.5 text-xs text-slate-600">
                Filtrado por tu rol; el PMO puede ajustar qué rol ve cada
                acceso y las pestañas KPI / Gantt.
            </p>
        </div>
        <div class="space-y-6 p-4">
            <div
                v-for="[group, items] in grouped"
                :key="group"
                class="space-y-2"
            >
                <p
                    class="text-[11px] font-semibold uppercase tracking-wide text-[#003366]/85"
                >
                    {{ group }}
                </p>
                <div
                    class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <Link
                        v-for="item in items"
                        :key="item.title + item.href"
                        :href="item.href"
                        class="flex items-center gap-2 rounded-lg border border-[#003366]/10 bg-white px-3 py-2.5 text-sm font-medium text-[#003366] shadow-sm transition hover:border-[#003366]/25 hover:bg-[#f8fafc]"
                    >
                        <component
                            :is="iconFor(item.icon)"
                            class="h-4 w-4 shrink-0 text-[#003366]/90"
                            aria-hidden="true"
                        />
                        <span class="min-w-0 truncate">{{ item.title }}</span>
                    </Link>
                </div>
            </div>
            <p
                v-if="links.length === 0"
                class="text-center text-sm text-slate-500"
            >
                No hay accesos visibles para tu usuario.
            </p>
        </div>
    </div>
</template>
