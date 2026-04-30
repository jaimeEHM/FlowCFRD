<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import { dashboard } from '@/routes';

type AreaUser = {
    id: number;
    name: string;
    cargo: string | null;
    email: string;
};

type AreaRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    coordinator_user_id: number | null;
    coordinator: AreaUser | null;
    users_count: number;
    users: AreaUser[];
};

type UserOption = {
    id: number;
    name: string;
    cargo: string | null;
    email: string;
    area_ids: number[];
};

const props = defineProps<{
    areas: AreaRow[];
    users: UserOption[];
    coordinators: UserOption[];
}>();

const createForm = useForm({
    name: '',
    description: '',
    is_active: true,
    coordinator_user_id: null as number | null,
});

const editingAreaId = ref<number | null>(null);
const editForm = useForm({
    name: '',
    description: '',
    is_active: true,
    coordinator_user_id: null as number | null,
});

const selectedAreaId = ref<number | null>(null);
const userSearch = ref('');
const userRoleFilter = ref('');
const selectedUserIds = ref<number[]>([]);

const selectedArea = computed(() =>
    props.areas.find((a) => a.id === selectedAreaId.value) ?? null,
);

const availableRoleHints = computed(() => {
    const set = new Set<string>();
    props.users.forEach((u) => {
        if (u.cargo && u.cargo.trim() !== '') {
            set.add(u.cargo.trim());
        }
    });
    return Array.from(set).sort((a, b) => a.localeCompare(b, 'es'));
});

const filteredUsers = computed(() => {
    const q = userSearch.value.trim().toLowerCase();
    const cargo = userRoleFilter.value.trim().toLowerCase();
    const currentAreaId = selectedAreaId.value;

    return props.users.filter((u) => {
        const isUnassigned = u.area_ids.length === 0;
        const isInCurrentArea = currentAreaId !== null && u.area_ids.includes(currentAreaId);
        if (!isUnassigned && !isInCurrentArea) {
            return false;
        }

        const matchesSearch =
            q === '' ||
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q);
        const matchesCargo =
            cargo === '' || (u.cargo ?? '').toLowerCase().includes(cargo);
        return matchesSearch && matchesCargo;
    });
});

function createArea(): void {
    if (!createForm.name.trim()) {
        return;
    }
    createForm.post('/sistema/areas', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function startEdit(area: AreaRow): void {
    editingAreaId.value = area.id;
    editForm.name = area.name;
    editForm.description = area.description ?? '';
    editForm.is_active = area.is_active;
    editForm.coordinator_user_id = area.coordinator_user_id;
}

function saveEdit(areaId: number): void {
    editForm.patch(`/sistema/areas/${areaId}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingAreaId.value = null;
        },
    });
}

function removeArea(areaId: number): void {
    router.delete(`/sistema/areas/${areaId}`, { preserveScroll: true });
}

function openAssign(area: AreaRow): void {
    selectedAreaId.value = area.id;
    selectedUserIds.value = area.users.map((u) => u.id);
}

function toggleUser(userId: number, checked: boolean): void {
    const set = new Set(selectedUserIds.value);
    if (checked) {
        set.add(userId);
    } else {
        set.delete(userId);
    }
    selectedUserIds.value = Array.from(set);
}

function saveAssignments(): void {
    if (!selectedArea.value) {
        return;
    }
    router.put(
        `/sistema/areas/${selectedArea.value.id}/usuarios`,
        { user_ids: selectedUserIds.value },
        { preserveScroll: true },
    );
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Áreas', href: '/sistema/areas' },
        ],
    },
});
</script>

<template>
    <Head title="Áreas" />

    <WorkflowSection
        context-label="Sistema y cumplimiento"
        title="Mantenedor de áreas"
        description="Crea áreas operativas y asigna personas por especialidad/cargo para reutilizar en módulos PMO, Coordinación y Proyecto."
    >
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="space-y-3 rounded-xl border border-[#003366]/12 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#003366]">Nueva área</p>
                <div class="space-y-1">
                    <Label for="area-name">Nombre</Label>
                    <Input id="area-name" v-model="createForm.name" placeholder="Ej: Diseño instruccional" />
                </div>
                <div class="space-y-1">
                    <Label for="area-desc">Descripción</Label>
                    <Input id="area-desc" v-model="createForm.description" placeholder="Opcional" />
                </div>
                <div class="space-y-1">
                    <Label for="area-coordinator">Coordinador a cargo</Label>
                    <select
                        id="area-coordinator"
                        v-model="createForm.coordinator_user_id"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option :value="null">Sin coordinador</option>
                        <option v-for="c in coordinators" :key="c.id" :value="c.id">
                            {{ c.name }}{{ c.cargo ? ` · ${c.cargo}` : '' }}
                        </option>
                    </select>
                </div>
                <Button class="bg-[#003366] hover:bg-[#00264d]" :disabled="createForm.processing" @click="createArea">
                    Crear área
                </Button>
            </div>

            <div class="space-y-3 rounded-xl border border-[#003366]/12 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#003366]">Asignación de personas</p>
                <div class="space-y-1">
                    <Label>Área seleccionada</Label>
                    <select
                        v-model="selectedAreaId"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        @change="
                            selectedUserIds =
                                props.areas.find((a) => a.id === Number(selectedAreaId))?.users.map((u) => u.id) ?? []
                        "
                    >
                        <option :value="null">Selecciona un área</option>
                        <option v-for="a in props.areas" :key="a.id" :value="a.id">
                            {{ a.name }} ({{ a.users_count }})
                        </option>
                    </select>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <Input v-model="userSearch" placeholder="Buscar persona..." />
                    <Input v-model="userRoleFilter" list="cargo-hints" placeholder="Filtrar por cargo/especialidad" />
                    <datalist id="cargo-hints">
                        <option v-for="cargo in availableRoleHints" :key="cargo" :value="cargo" />
                    </datalist>
                </div>
                <div class="max-h-56 space-y-1 overflow-y-auto rounded-md border border-input p-2">
                    <label
                        v-for="u in filteredUsers"
                        :key="`area-u-${u.id}`"
                        class="flex items-center gap-2 text-xs text-slate-700"
                    >
                        <input
                            type="checkbox"
                            class="rounded border-input"
                            :checked="selectedUserIds.includes(u.id)"
                            :disabled="!selectedAreaId"
                            @change="toggleUser(u.id, ($event.target as HTMLInputElement).checked)"
                        />
                        <span>{{ u.name }}</span>
                        <span class="text-slate-500">· {{ u.cargo ?? 'Sin cargo' }}</span>
                    </label>
                </div>
                <Button
                    class="bg-[#003366] hover:bg-[#00264d]"
                    :disabled="!selectedAreaId"
                    @click="saveAssignments"
                >
                    Guardar asignaciones
                </Button>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto rounded-xl border border-[#003366]/12 bg-white shadow-sm">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b border-[#003366]/12 bg-[#f8fafc] text-xs font-semibold uppercase text-[#003366]">
                    <tr>
                        <th class="px-4 py-3">Área</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Coordinador</th>
                        <th class="px-4 py-3">Personas</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#003366]/8">
                    <tr v-for="a in areas" :key="a.id">
                        <td class="px-4 py-3">
                            <template v-if="editingAreaId === a.id">
                                <Input v-model="editForm.name" />
                            </template>
                            <template v-else>{{ a.name }}</template>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ a.slug }}</td>
                        <td class="px-4 py-3">
                            <template v-if="editingAreaId === a.id">
                                <select v-model="editForm.is_active" class="h-8 rounded-md border border-input px-2 text-xs">
                                    <option :value="true">Activa</option>
                                    <option :value="false">Inactiva</option>
                                </select>
                            </template>
                            <template v-else>{{ a.is_active ? 'Activa' : 'Inactiva' }}</template>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">
                            <template v-if="editingAreaId === a.id">
                                <select
                                    v-model="editForm.coordinator_user_id"
                                    class="h-8 rounded-md border border-input px-2 text-xs"
                                >
                                    <option :value="null">Sin coordinador</option>
                                    <option v-for="c in coordinators" :key="c.id" :value="c.id">
                                        {{ c.name }}
                                    </option>
                                </select>
                            </template>
                            <template v-else>
                                {{ a.coordinator?.name ?? '—' }}
                            </template>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ a.users_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-2">
                                <Button
                                    v-if="editingAreaId !== a.id"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="startEdit(a)"
                                >
                                    Editar
                                </Button>
                                <Button
                                    v-else
                                    type="button"
                                    size="sm"
                                    class="bg-[#003366] hover:bg-[#00264d]"
                                    @click="saveEdit(a.id)"
                                >
                                    Guardar
                                </Button>
                                <Button type="button" variant="outline" size="sm" @click="openAssign(a)">
                                    Personas
                                </Button>
                                <Button type="button" variant="outline" size="sm" @click="removeArea(a.id)">
                                    Eliminar
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </WorkflowSection>
</template>

