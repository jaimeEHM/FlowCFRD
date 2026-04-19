<script setup lang="ts">
import Gantt from 'frappe-gantt';
import 'frappe-gantt-style';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
    withDefaults,
} from 'vue';

export type GanttProjectRow = {
    id: number;
    name: string;
    starts_at: string | null;
    ends_at: string | null;
};

const props = withDefaults(
    defineProps<{
        projects: GanttProjectRow[];
        /** Prefijo de id en frappe-gantt (`p` = iniciativa, `t` = tarea). */
        rowIdPrefix?: 'p' | 't';
        emptyHint?: string;
    }>(),
    {
        rowIdPrefix: 't',
        emptyHint:
            'No hay tareas con fechas derivables (fecha límite o alta). Añade tareas o define fecha límite para ver barras.',
    },
);

const host = ref<HTMLDivElement | null>(null);
let chart: InstanceType<typeof Gantt> | null = null;

const ganttTasks = computed(() => buildTaskList());

function toYmd(value: string | null): string | null {
    if (!value) {
        return null;
    }
    return value.slice(0, 10);
}

function addDaysYmd(ymd: string, days: number): string {
    const d = new Date(ymd + 'T12:00:00');
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

function buildTaskList(): {
    id: string;
    name: string;
    start: string;
    end: string;
    progress: number;
}[] {
    return props.projects
        .map((p) => {
            const start = toYmd(p.starts_at);
            if (!start) {
                return null;
            }
            const endRaw = toYmd(p.ends_at);
            let end = endRaw && endRaw >= start ? endRaw : addDaysYmd(start, 7);
            if (end <= start) {
                end = addDaysYmd(start, 1);
            }

            return {
                id: `${props.rowIdPrefix}-${p.id}`,
                name: p.name,
                start,
                end,
                progress: 0,
            };
        })
        .filter((t): t is NonNullable<typeof t> => t !== null);
}

/** Evita la animación SMIL de ancho (0→100%) que frappe-gantt aplica al dibujar barras. */
function stripGanttBarAnimations(container: HTMLElement): void {
    container.querySelectorAll('animate').forEach((anim) => {
        const parent = anim.parentElement;
        const attr = anim.getAttribute('attributeName');
        const to = anim.getAttribute('to');
        if (parent && attr && to !== null) {
            parent.setAttribute(attr, to);
        }
        anim.remove();
    });
}

/**
 * frappe-gantt llama a scrollTo({ behavior: 'smooth' }) al aplicar scroll_to: 'today'.
 * Eso provoca el scroll animado molesto; forzamos instantáneo solo en el contenedor del Gantt.
 */
function withInstantGanttScroll<T>(fn: () => T): T {
    const orig = Element.prototype.scrollTo;
    Element.prototype.scrollTo = function (
        this: Element,
        arg1?: number | ScrollToOptions,
        arg2?: number,
    ) {
        const isGanttContainer =
            this instanceof HTMLElement &&
            this.classList.contains('gantt-container');
        if (
            isGanttContainer &&
            typeof arg1 === 'object' &&
            arg1 !== null &&
            'behavior' in arg1 &&
            arg1.behavior === 'smooth'
        ) {
            return orig.call(this, { ...arg1, behavior: 'auto' });
        }
        if (typeof arg1 === 'number') {
            return orig.call(this, arg1, arg2 ?? 0);
        }
        return orig.call(this, arg1 as ScrollToOptions);
    };
    try {
        return fn();
    } finally {
        Element.prototype.scrollTo = orig;
    }
}

function mountGantt(): void {
    if (!host.value) {
        return;
    }
    host.value.innerHTML = '';
    chart = null;

    const tasks = ganttTasks.value;
    if (tasks.length === 0) {
        return;
    }

    chart = withInstantGanttScroll(
        () =>
            new Gantt(host.value as HTMLElement, tasks, {
                view_mode: 'Month',
                bar_height: 24,
                language: 'en',
                scroll_to: 'today',
                today_button: false,
            }),
    );
    stripGanttBarAnimations(host.value);
}

onMounted(() => {
    mountGantt();
});

watch(
    () => props.projects,
    () => {
        void nextTick(() => {
            mountGantt();
        });
    },
    { deep: true },
);

watch(
    () => props.rowIdPrefix,
    () => {
        void nextTick(() => {
            mountGantt();
        });
    },
);

onBeforeUnmount(() => {
    if (host.value) {
        host.value.innerHTML = '';
    }
    chart = null;
});
</script>

<template>
    <div>
        <div
            v-if="ganttTasks.length === 0"
            class="rounded-xl border border-dashed border-[#003366]/25 bg-[#f8fafc] px-4 py-12 text-center text-sm text-slate-600"
        >
            {{ props.emptyHint }}
        </div>
        <div
            v-else
            ref="host"
            class="project-gantt-host min-h-[280px] overflow-x-auto rounded-xl border border-[#003366]/12 bg-white p-3 shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
        />
    </div>
</template>

<style scoped>
/* Tema alineado al tablero PMO (UdeC #003366, fondos claros). Variables de frappe-gantt v1. */
.project-gantt-host {
    --g-header-background: #f1f5f9;
    --g-row-color: #ffffff;
    --g-tick-color: #f1f5f9;
    --g-tick-color-thick: #e2e8f0;
    --g-border-color: #cbd5e1;
    --g-row-border-color: rgba(0, 51, 102, 0.18);
    --g-text-dark: #1e293b;
    --g-text-muted: #64748b;
    --g-text-light: #ffffff;
    --g-bar-color: #e8f0f8;
    --g-bar-border: rgba(0, 51, 102, 0.35);
    --g-progress-color: #1e5a8e;
    --g-today-highlight: #003366;
    --g-actions-background: #f1f5f9;
    --g-arrow-color: #475569;
    --g-handle-color: #64748b;
    --g-weekend-highlight-color: #f8fafc;
    --g-popup-actions: #e2e8f0;
}

.project-gantt-host :deep(.gantt-container) {
    font-family: inherit;
    font-size: 12px;
    border-radius: 0.5rem;
}

.project-gantt-host :deep(.gantt .bar-label) {
    font-family: inherit;
    font-size: 12px;
}

.project-gantt-host :deep(.gantt-container .side-header) {
    display: none;
}

.project-gantt-host :deep(.grid-header) {
    fill: #f1f5f9;
}

.project-gantt-host :deep(.grid-row) {
    fill: #ffffff;
}
</style>
