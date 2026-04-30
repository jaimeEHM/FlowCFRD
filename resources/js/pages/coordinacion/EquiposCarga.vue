<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { PauseCircle, Pencil, Repeat } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import WorkflowSection from '@/components/workflow/WorkflowSection.vue';
import UserAvatarInline from '@/components/workflow/UserAvatarInline.vue';
import { dashboard } from '@/routes';
import { equiposCarga } from '@/routes/coordinacion';

type AreaOption = {
    id: number;
    name: string;
};

type Row = {
    id: number;
    /** Nombre sin cargo (listado). */
    nombre: string;
    /** Nombre completo como en BD (incluye cargo si aplica). */
    name: string;
    cargo: string | null;
    email: string;
    avatar?: string | null;
    roles: string[];
    areas: string[];
    area_ids: number[];
    tareas_abiertas: number;
};

const props = defineProps<{
    users: Row[];
    area_options: AreaOption[];
}>();

const searchQuery = ref('');
const roleFilter = ref<string>('todos');
const areaFilter = ref<string>('todas');
const loadFilter = ref<string>('todas');

const editModalOpen = ref(false);
const reassignModalOpen = ref(false);
const suspendModalOpen = ref(false);
const selectedUserId = ref<number | null>(null);

const editForm = useForm({
    cargo: '',
    area_id: null as number | null,
});

const reassignForm = useForm({
    area_id: null as number | null,
});

const selectedUser = computed(() => props.users.find((u) => u.id === selectedUserId.value) ?? null);

const availableRoles = computed(() => {
    return Array.from(new Set(props.users.flatMap((u) => u.roles))).sort((a, b) => a.localeCompare(b, 'es'));
});

const filteredUsers = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();

    return props.users.filter((u) => {
        if (q !== '') {
            const haystack = `${u.nombre} ${u.email} ${u.cargo ?? ''}`.toLowerCase();
            if (!haystack.includes(q)) {
                return false;
            }
        }

        if (roleFilter.value !== 'todos' && !u.roles.includes(roleFilter.value)) {
            return false;
        }

        if (areaFilter.value !== 'todas' && !u.areas.includes(areaFilter.value)) {
            return false;
        }

        if (loadFilter.value === 'con-carga' && u.tareas_abiertas <= 0) {
            return false;
        }
        if (loadFilter.value === 'sin-carga' && u.tareas_abiertas > 0) {
            return false;
        }
        if (loadFilter.value === 'alta-carga' && u.tareas_abiertas < 5) {
            return false;
        }

        return true;
    });
});

function openEdit(user: Row): void {
    selectedUserId.value = user.id;
    editForm.cargo = user.cargo ?? '';
    editForm.area_id = user.area_ids[0] ?? null;
    editModalOpen.value = true;
}

function submitEdit(): void {
    if (!selectedUser.value) {
        return;
    }
    editForm.patch(`/coordinacion/equipos-carga/${selectedUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editModalOpen.value = false;
        },
    });
}

function openSuspend(user: Row): void {
    selectedUserId.value = user.id;
    suspendModalOpen.value = true;
}

function submitSuspend(): void {
    if (!selectedUser.value) {
        return;
    }
    router.patch(`/coordinacion/equipos-carga/${selectedUser.value.id}/suspender-areas`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            suspendModalOpen.value = false;
        },
    });
}

function openReassign(user: Row): void {
    selectedUserId.value = user.id;
    reassignForm.area_id = user.area_ids[0] ?? null;
    reassignModalOpen.value = true;
}

function submitReassign(): void {
    if (!selectedUser.value || reassignForm.area_id === null) {
        return;
    }
    reassignForm.put(
        `/coordinacion/equipos-carga/${selectedUser.value.id}/reasignar-area`,
        {
            preserveScroll: true,
            onSuccess: () => {
                reassignModalOpen.value = false;
            },
        },
    );
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Equipos y carga', href: equiposCarga() },
        ],
    },
});
</script>

<template>
    <Head title="Equipos y carga" />

    <WorkflowSection
        context-label="Coordinación — personas y backlog"
        title="Equipos y distribución de carga"
        description="Usuarios del sistema con cargo CFRD, roles, áreas y acciones de gestión operativa."
    >
        <div class="mb-4 rounded-lg border border-[#003366]/15 bg-white p-4 shadow-sm">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-[#003366]">Filtros de búsqueda</p>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="space-y-1">
                    <Label for="filtro-q">Nombre, correo o cargo</Label>
                    <Input id="filtro-q" v-model="searchQuery" placeholder="Buscar persona..." />
                </div>
                <div class="space-y-1">
                    <Label for="filtro-rol">Rol</Label>
                    <select
                        id="filtro-rol"
                        v-model="roleFilter"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="todos">Todos</option>
                        <option v-for="role in availableRoles" :key="role" :value="role">{{ role }}</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <Label for="filtro-area">Área</Label>
                    <select
                        id="filtro-area"
                        v-model="areaFilter"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="todas">Todas</option>
                        <option v-for="area in area_options" :key="`filtro-${area.id}`" :value="area.name">{{ area.name }}</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <Label for="filtro-carga">Carga</Label>
                    <select
                        id="filtro-carga"
                        v-model="loadFilter"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="todas">Todas</option>
                        <option value="con-carga">Con tareas abiertas</option>
                        <option value="sin-carga">Sin tareas abiertas</option>
                        <option value="alta-carga">Alta carga (5+)</option>
                    </select>
                </div>
            </div>
        </div>

        <div
            class="overflow-x-auto rounded-lg border border-[#003366]/15 bg-white shadow-sm"
        >
            <table class="w-full min-w-[940px] text-left text-sm">
                <thead
                    class="border-b border-[#003366]/15 bg-[#f8fafc] text-xs font-semibold uppercase text-[#003366]"
                >
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Cargo</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Roles</th>
                        <th class="px-4 py-3">Áreas</th>
                        <th class="px-4 py-3 text-right">Tareas abiertas</th>
                        <th class="px-4 py-3 text-center">Opciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#003366]/10">
                    <tr v-for="u in filteredUsers" :key="u.id">
                        <td class="px-4 py-3 font-medium">
                            <UserAvatarInline
                                :name="u.nombre"
                                :avatar="u.avatar"
                                size="sm"
                            />
                        </td>
                        <td class="max-w-[220px] px-4 py-3 text-[#333]">
                            {{ u.cargo ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-[#666]">{{ u.email }}</td>
                        <td class="px-4 py-3 text-xs text-[#666]">
                            {{ u.roles.join(', ') }}
                        </td>
                        <td class="px-4 py-3 text-xs text-[#666]">
                            {{ u.areas.length > 0 ? u.areas.join(', ') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ u.tareas_abiertas }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-md border border-[#003366]/20 p-1.5 text-[#003366] hover:bg-[#003366]/10"
                                    title="Editar persona"
                                    @click="openEdit(u)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md border border-[#d21428]/30 p-1.5 text-[#d21428] hover:bg-[#d21428]/10"
                                    title="Suspender asignación de áreas"
                                    @click="openSuspend(u)"
                                >
                                    <PauseCircle class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md border border-[#e69b0a]/30 p-1.5 text-[#a16207] hover:bg-[#e69b0a]/10"
                                    title="Reasignar área"
                                    @click="openReassign(u)"
                                >
                                    <Repeat class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="filteredUsers.length === 0">
                        <td colspan="8" class="px-4 py-8 text-center text-[#6b7280]">
                            No hay resultados para los filtros seleccionados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog v-model:open="editModalOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Editar persona</DialogTitle>
                    <DialogDescription>
                        {{ selectedUser ? `Actualiza cargo y área de ${selectedUser.nombre}.` : 'Selecciona una persona.' }}
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                    <div class="space-y-1">
                        <Label for="modal-persona-cargo">Cargo</Label>
                        <Input id="modal-persona-cargo" v-model="editForm.cargo" placeholder="Cargo o especialidad" />
                    </div>
                    <div class="space-y-1">
                        <Label for="modal-persona-area">Área asignada</Label>
                        <select
                            id="modal-persona-area"
                            v-model="editForm.area_id"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option :value="null">Sin área</option>
                            <option v-for="area in area_options" :key="`edit-${area.id}`" :value="area.id">{{ area.name }}</option>
                        </select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="editModalOpen = false">Cancelar</Button>
                    <Button class="bg-[#003366] hover:bg-[#00264d]" :disabled="editForm.processing" @click="submitEdit">
                        Guardar cambios
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="reassignModalOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reasignar área</DialogTitle>
                    <DialogDescription>
                        {{ selectedUser ? `Selecciona la nueva área de ${selectedUser.nombre}.` : 'Selecciona una persona.' }}
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-1">
                    <Label for="modal-reasignar-area">Nueva área</Label>
                    <select
                        id="modal-reasignar-area"
                        v-model="reassignForm.area_id"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option :value="null">Selecciona un área</option>
                        <option v-for="area in area_options" :key="`reassign-${area.id}`" :value="area.id">{{ area.name }}</option>
                    </select>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="reassignModalOpen = false">Cancelar</Button>
                    <Button
                        class="bg-[#003366] hover:bg-[#00264d]"
                        :disabled="reassignForm.processing || reassignForm.area_id === null"
                        @click="submitReassign"
                    >
                        Confirmar reasignación
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="suspendModalOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Suspender áreas de persona</DialogTitle>
                    <DialogDescription>
                        {{ selectedUser ? `Se eliminará la asignación de áreas de ${selectedUser.nombre}.` : 'Selecciona una persona.' }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="suspendModalOpen = false">Cancelar</Button>
                    <Button variant="destructive" @click="submitSuspend">Suspender</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </WorkflowSection>
</template>
