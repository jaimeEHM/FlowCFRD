<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { Button } from '@/components/ui/button';

export type VisibilityRow = {
    key: string;
    label: string;
    kind: 'hub' | 'segment';
    default_roles: string[];
    effective_roles: string[];
};

const props = defineProps<{
    matrix: VisibilityRow[];
    roleOptions: string[];
}>();

const form = useForm<{ overrides: Record<string, string[]> }>({
    overrides: {},
});

watch(
    () => props.matrix,
    (m) => {
        const o: Record<string, string[]> = {};
        for (const row of m) {
            o[row.key] = [...row.effective_roles];
        }
        form.overrides = o;
    },
    { immediate: true, deep: true },
);

function toggleRole(rowKey: string, role: string): void {
    if (role === 'admin') {
        return;
    }
    const cur = [...(form.overrides[rowKey] ?? [])];
    const i = cur.indexOf(role);
    if (i === -1) {
        cur.push(role);
    } else {
        cur.splice(i, 1);
    }
    form.overrides[rowKey] = cur;
}

function hasRole(rowKey: string, role: string): boolean {
    if (role === 'admin') {
        return true;
    }
    return (form.overrides[rowKey] ?? []).includes(role);
}

function restoreDefaults(row: VisibilityRow): void {
    form.overrides[row.key] = [...row.default_roles];
}

function submit(): void {
    form.post('/pmo/tablero-macro/visibilidad', {
        preserveScroll: true,
    });
}
</script>

<template>
    <details
        class="overflow-hidden rounded-xl border border-amber-200/80 bg-amber-50/40 shadow-[0_1px_3px_rgba(0,51,102,0.06)]"
    >
        <summary
            class="cursor-pointer list-none border-b border-amber-200/60 bg-amber-100/50 px-4 py-3 text-sm font-semibold text-amber-950 outline-none marker:content-none [&::-webkit-details-marker]:hidden"
        >
            <span class="inline-flex items-center gap-2">
                Gestión de visibilidad (PMO)
                <span
                    class="rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-normal uppercase tracking-wide text-amber-900"
                >
                    roles por acceso
                </span>
            </span>
        </summary>
        <div class="space-y-4 p-4">
            <p class="text-xs text-amber-950/80">
                Reglas base por rol (como el menú lateral). El rol
                <strong>admin</strong> siempre ve todo. El resto se ajusta con las
                casillas. «Restaurar» vuelve al valor por defecto del catálogo.
            </p>
            <div class="overflow-x-auto rounded-lg border border-[#003366]/10 bg-white">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead
                        class="border-b border-[#003366]/12 bg-[#f1f5f9] text-[10px] font-semibold uppercase tracking-wide text-[#003366]"
                    >
                        <tr>
                            <th class="px-3 py-2">Ítem</th>
                            <th
                                v-for="r in roleOptions"
                                :key="r"
                                class="px-1 py-2 text-center"
                            >
                                {{ r }}
                            </th>
                            <th class="px-3 py-2 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#003366]/8 text-[#1e293b]">
                        <tr v-for="row in matrix" :key="row.key">
                            <td class="max-w-[14rem] px-3 py-2 align-top">
                                <p class="font-medium leading-snug text-[#003366]">
                                    {{ row.label }}
                                </p>
                                <p class="text-[10px] text-slate-500">
                                    {{ row.kind === 'segment' ? 'Pestaña' : 'Enlace' }}
                                </p>
                            </td>
                            <td
                                v-for="r in roleOptions"
                                :key="row.key + r"
                                class="px-1 py-2 text-center align-middle"
                            >
                                <label
                                    class="inline-flex cursor-pointer items-center justify-center"
                                    :class="{
                                        'cursor-not-allowed opacity-60': r === 'admin',
                                    }"
                                >
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-input text-[#003366]"
                                        :checked="hasRole(row.key, r)"
                                        :disabled="r === 'admin'"
                                        @change="toggleRole(row.key, r)"
                                    />
                                    <span class="sr-only">{{ r }}</span>
                                </label>
                            </td>
                            <td class="px-3 py-2 text-right align-middle">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    class="text-xs"
                                    @click="restoreDefaults(row)"
                                >
                                    Restaurar
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    class="bg-[#003366] hover:bg-[#003366]/90"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Guardar visibilidad
                </Button>
            </div>
        </div>
    </details>
</template>
