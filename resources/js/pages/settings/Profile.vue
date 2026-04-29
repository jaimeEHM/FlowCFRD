<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/composables/useInitials';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Ajustes del perfil',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();
const avatarPositionX = ref(Number(user.value.avatar_position_x ?? 0));
const avatarPositionY = ref(Number(user.value.avatar_position_y ?? 0));
const avatarObjectPosition = computed(
    () => `${50 + avatarPositionX.value}% ${50 + avatarPositionY.value}%`,
);
</script>

<template>
    <Head title="Ajustes del perfil" />

    <h1 class="sr-only">Ajustes del perfil</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Información del perfil"
            description="Actualiza tu nombre y correo electrónico"
        />

        <Form
            :action="ProfileController.update.url()"
            method="patch"
            enctype="multipart/form-data"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="avatar">Foto de perfil</Label>
                <div class="flex items-center gap-4 rounded-lg border border-border/60 p-3">
                    <Avatar class="h-14 w-14 shrink-0 rounded-lg">
                        <AvatarImage
                            v-if="user.avatar"
                            :src="user.avatar"
                            :alt="`Foto de ${user.name}`"
                            :style="{ objectPosition: avatarObjectPosition }"
                        />
                        <AvatarFallback class="rounded-lg text-black">
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1 space-y-2">
                        <Input
                            id="avatar"
                            type="file"
                            name="avatar"
                            accept="image/png,image/jpeg,image/webp,image/gif"
                        />
                        <p class="text-xs text-muted-foreground">
                            Formatos: JPG, PNG, WEBP o GIF. Maximo 2MB.
                        </p>
                        <div class="grid gap-2">
                            <Label for="avatar_position_x" class="text-xs"
                                >Posicion horizontal</Label
                            >
                            <input
                                id="avatar_position_x"
                                v-model.number="avatarPositionX"
                                type="range"
                                name="avatar_position_x"
                                min="-50"
                                max="50"
                                step="1"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="avatar_position_y" class="text-xs"
                                >Posicion vertical</Label
                            >
                            <input
                                id="avatar_position_y"
                                v-model.number="avatarPositionY"
                                type="range"
                                name="avatar_position_y"
                                min="-50"
                                max="50"
                                step="1"
                            />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-muted-foreground">
                            <input
                                type="checkbox"
                                name="avatar_remove"
                                value="1"
                                class="h-4 w-4 rounded border-border"
                            />
                            Quitar foto actual
                        </label>
                    </div>
                </div>
                <InputError class="mt-1" :message="errors.avatar" />
                <InputError class="mt-1" :message="errors.avatar_position_x" />
                <InputError class="mt-1" :message="errors.avatar_position_y" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Nombre</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Nombre completo"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Correo electrónico</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Correo electrónico"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Tu correo electrónico no está verificado.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                    >
                        Haz clic aquí para reenviar el correo de verificación.
                    </Link>
                </p>

                <div
                    v-if="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    Se ha enviado un nuevo enlace de verificación a tu correo
                    electrónico.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Guardar</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
