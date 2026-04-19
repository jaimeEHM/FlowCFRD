<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { tableroMacro } from '@/routes/pmo';

export type MacroSegment =
    | 'cartera'
    | 'kpi'
    | 'gantt'
    | 'lista'
    | 'kanban'
    | 'carga';

const props = defineProps<{
    active: MacroSegment;
    visibleTabSegments?: {
        kpi: boolean;
        gantt: boolean;
        lista: boolean;
        kanban: boolean;
        carga: boolean;
    };
    /** Título del “tablero” (fila superior estilo Monday). */
    boardTitle?: string;
    /** Texto auxiliar bajo el título (segmento activo o ayuda breve). */
    boardSubtitle?: string;
    /** Si hay proyecto enfocado en el tablero, se mantiene en la query al cambiar de pestaña. */
    focusedProjectId?: number | null;
}>();

const title = computed(() => props.boardTitle ?? 'Tablero macro PMO');

function href(segment: MacroSegment): string {
    const q: Record<string, string | number> = {};
    if (props.focusedProjectId != null) {
        q.project_id = props.focusedProjectId;
    }
    if (segment !== 'cartera') {
        q.segment = segment;
    }
    return Object.keys(q).length > 0
        ? tableroMacro.url({ query: q })
        : tableroMacro.url();
}

function hrefLista(): string {
    const q: Record<string, string | number> = { segment: 'lista' };
    if (props.focusedProjectId != null) {
        q.project_id = props.focusedProjectId;
    }
    return tableroMacro.url({ query: q });
}

function tabLinkClass(segment: MacroSegment): string {
    const base =
        'relative inline-flex items-center whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium transition-colors';
    if (props.active === segment) {
        return `${base} -mb-px border-[#003366] text-[#003366]`;
    }
    return `${base} border-transparent text-slate-600 hover:border-[#003366]/25 hover:text-[#003366]`;
}

const showKpi = computed(
    () => props.visibleTabSegments?.kpi !== false,
);
const showGantt = computed(
    () => props.visibleTabSegments?.gantt !== false,
);
const showLista = computed(
    () => props.visibleTabSegments?.lista !== false,
);
const showKanban = computed(
    () => props.visibleTabSegments?.kanban !== false,
);
const showCarga = computed(
    () => props.visibleTabSegments?.carga !== false,
);
</script>

<template>
    <div
        class="mb-4 overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
    >
        <!-- Cabecera del tablero (equivalente al nombre de board en Monday) -->
        <div
            class="flex flex-col gap-1 border-b border-[#003366]/10 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="min-w-0">
                <h2
                    class="truncate text-lg font-semibold tracking-tight text-[#003366]"
                >
                    {{ title }}
                </h2>
                <p
                    v-if="boardSubtitle"
                    class="mt-0.5 text-xs leading-snug text-slate-600"
                >
                    {{ boardSubtitle }}
                </p>
            </div>
            <p
                class="shrink-0 text-[11px] text-slate-500"
            >
                UdeC · línea gráfica institucional
            </p>
        </div>

        <!-- Pestañas de vista (estructura Monday: fila horizontal + subrayado activo) -->
        <nav
            class="flex flex-wrap border-b border-[#003366]/12 bg-[#f8fafc] px-1"
            aria-label="Vistas del tablero macro"
        >
            <Link :class="tabLinkClass('cartera')" :href="href('cartera')">
                Tabla principal
            </Link>
            <Link
                v-if="showKpi"
                :class="tabLinkClass('kpi')"
                :href="href('kpi')"
            >
                Indicadores
            </Link>
            <Link
                v-if="showGantt"
                :class="tabLinkClass('gantt')"
                :href="href('gantt')"
            >
                Cronograma
            </Link>
            <Link
                v-if="showLista"
                :class="tabLinkClass('lista')"
                :href="hrefLista()"
            >
                Lista de tareas
            </Link>
            <Link
                v-if="showKanban"
                :class="tabLinkClass('kanban')"
                :href="href('kanban')"
            >
                Kanban
            </Link>
            <Link
                v-if="showCarga"
                :class="tabLinkClass('carga')"
                :href="href('carga')"
            >
                Carga equipo
            </Link>
        </nav>

        <!-- Barra de herramientas (búsqueda, filtros — slot) -->
        <div
            v-if="$slots.toolbar"
            class="flex flex-wrap items-center gap-2 border-b border-[#003366]/8 bg-white px-3 py-2"
        >
            <slot name="toolbar" />
        </div>
    </div>
</template>
