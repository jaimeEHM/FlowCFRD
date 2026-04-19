<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    Columns3,
    GanttChartSquare,
    ListTodo,
    ScrollText,
    Table2,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    calendario,
    cronograma,
    kanban,
    minutas,
    tabla,
} from '@/routes/proyecto';
import { tableroMacro } from '@/routes/pmo';

type Project = {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    status: string;
    carta_inicio_at: string | null;
    starts_at: string | null;
    ends_at: string | null;
    jefe_proyecto: { id: number; name: string } | null;
    tasks_abiertas: number;
    tasks_total: number;
};

const props = defineProps<{
    project: Project;
    canEditPmo: boolean;
}>();

const emit = defineEmits<{
    'edit-pmo': [];
    'clear-selection': [];
}>();

function q() {
    return { query: { project_id: props.project.id } };
}

function statusLabel(status: string): string {
    return status.replace(/_/g, ' ');
}

function progressPercent(): number {
    if (props.project.tasks_total <= 0) {
        return 0;
    }
    const hechas = props.project.tasks_total - props.project.tasks_abiertas;
    return Math.round((hechas / props.project.tasks_total) * 100);
}
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-[#003366]/12 bg-white shadow-[0_1px_3px_rgba(0,51,102,0.08)]"
    >
        <div
            class="flex flex-col gap-3 border-b border-[#003366]/12 bg-[#f8fafc] px-4 py-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="min-w-0">
                <p
                    class="text-[11px] font-semibold uppercase tracking-wide text-[#003366]"
                >
                    Proyecto seleccionado
                </p>
                <h3
                    class="mt-1 truncate text-lg font-semibold text-[#003366]"
                >
                    {{ project.name }}
                </h3>
                <p class="mt-0.5 font-mono text-xs text-slate-600">
                    {{ project.code ?? 'Sin código' }}
                </p>
                <p
                    v-if="project.description"
                    class="mt-2 line-clamp-3 text-sm text-slate-700"
                >
                    {{ project.description }}
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="border-[#003366]/25 text-xs"
                    @click="emit('clear-selection')"
                >
                    <ArrowLeft class="mr-1 h-3.5 w-3.5" aria-hidden="true" />
                    Volver a la cartera
                </Button>
                <Button
                    v-if="canEditPmo"
                    type="button"
                    size="sm"
                    class="bg-[#003366] text-xs hover:bg-[#00264d]"
                    @click="emit('edit-pmo')"
                >
                    Editar datos PMO
                </Button>
            </div>
        </div>

        <div class="grid gap-4 p-4 lg:grid-cols-2">
            <div
                class="rounded-lg border border-[#003366]/10 bg-[#f8fafc] p-3 text-sm"
            >
                <p
                    class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"
                >
                    Resumen
                </p>
                <dl class="mt-2 space-y-1.5 text-xs">
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-600">Estado</dt>
                        <dd class="font-medium capitalize text-[#003366]">
                            {{ statusLabel(project.status) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-600">Jefe</dt>
                        <dd class="text-right font-medium text-slate-800">
                            {{ project.jefe_proyecto?.name ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-600">Inicio / fin</dt>
                        <dd class="tabular-nums text-slate-800">
                            {{ project.starts_at ?? '—' }} →
                            {{ project.ends_at ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-600">Carta inicio</dt>
                        <dd class="tabular-nums text-slate-800">
                            {{ project.carta_inicio_at ?? '—' }}
                        </dd>
                    </div>
                </dl>
                <div class="mt-3">
                    <p
                        class="text-[10px] font-semibold uppercase text-slate-500"
                    >
                        Avance tareas
                    </p>
                    <div
                        class="mt-1 h-2 w-full overflow-hidden rounded-full bg-slate-200"
                    >
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[#003366] to-[#1e5a8e]"
                            :style="{ width: `${progressPercent()}%` }"
                        />
                    </div>
                    <p class="mt-1 text-xs tabular-nums text-slate-600">
                        {{ project.tasks_total - project.tasks_abiertas }}/{{
                            project.tasks_total
                        }}
                        · {{ progressPercent() }}%
                    </p>
                </div>
            </div>

            <div>
                <p
                    class="text-[10px] font-semibold uppercase tracking-wide text-[#003366]"
                >
                    Ir al trabajo del proyecto
                </p>
                <p class="mt-0.5 text-xs text-slate-600">
                    Mismas rutas que el espacio de proyecto, con este proyecto
                    ya elegido.
                </p>
                <nav
                    class="mt-3 flex flex-col gap-1.5"
                    aria-label="Accesos al proyecto"
                >
                    <Link
                        :href="kanban.url(q())"
                        class="inline-flex items-center gap-2 rounded-md border border-[#003366]/12 bg-white px-3 py-2 text-sm font-medium text-[#003366] transition hover:bg-[#003366]/5"
                    >
                        <Columns3 class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Kanban
                    </Link>
                    <Link
                        :href="tabla.url(q())"
                        class="inline-flex items-center gap-2 rounded-md border border-[#003366]/12 bg-white px-3 py-2 text-sm font-medium text-[#003366] transition hover:bg-[#003366]/5"
                    >
                        <Table2 class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Lista de tareas
                    </Link>
                    <Link
                        :href="
                            tableroMacro.url({
                                query: {
                                    segment: 'lista',
                                    project_id: project.id,
                                },
                            })
                        "
                        class="inline-flex items-center gap-2 rounded-md border border-[#003366]/12 bg-white px-3 py-2 text-sm font-medium text-[#003366] transition hover:bg-[#003366]/5"
                    >
                        <ListTodo class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Lista en cartera PMO
                    </Link>
                    <Link
                        :href="cronograma.url(q())"
                        class="inline-flex items-center gap-2 rounded-md border border-[#003366]/12 bg-white px-3 py-2 text-sm font-medium text-[#003366] transition hover:bg-[#003366]/5"
                    >
                        <GanttChartSquare
                            class="h-4 w-4 shrink-0"
                            aria-hidden="true"
                        />
                        Cronograma
                    </Link>
                    <Link
                        :href="calendario.url(q())"
                        class="inline-flex items-center gap-2 rounded-md border border-[#003366]/12 bg-white px-3 py-2 text-sm font-medium text-[#003366] transition hover:bg-[#003366]/5"
                    >
                        <CalendarDays
                            class="h-4 w-4 shrink-0"
                            aria-hidden="true"
                        />
                        Calendario
                    </Link>
                    <Link
                        :href="minutas.url(q())"
                        class="inline-flex items-center gap-2 rounded-md border border-[#003366]/12 bg-white px-3 py-2 text-sm font-medium text-[#003366] transition hover:bg-[#003366]/5"
                    >
                        <ScrollText class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Minutas
                    </Link>
                </nav>
                <p class="mt-3 text-[11px] text-slate-500">
                    También puedes volver al
                    <Link
                        :href="tableroMacro.url()"
                        class="font-medium text-[#003366] underline underline-offset-2"
                    >
                        tablero sin proyecto
                    </Link>
                    para ver la tabla de cartera.
                </p>
            </div>
        </div>
    </div>
</template>
