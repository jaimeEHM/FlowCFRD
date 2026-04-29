<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import CfrdLoginLayout from '@/layouts/auth/CfrdLoginLayout.vue';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: CfrdLoginLayout,
});

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    googleOAuthConfigured: boolean;
    googleClientId: string;
    googleAuthUrl: string;
    googleTokenUrl: string;
    cfrdDomain: string;
    devPasswordLoginEmails: string[];
}>();

declare global {
    interface Window {
        google?: {
            accounts?: {
                id?: {
                    initialize: (config: {
                        client_id: string;
                        callback: (response: { credential?: string }) => void;
                    }) => void;
                    renderButton: (
                        parent: HTMLElement,
                        options: Record<string, unknown>,
                    ) => void;
                };
            };
        };
    }
}

const googleButtonRef = ref<HTMLElement | null>(null);
const googleIdTokenRef = ref<HTMLInputElement | null>(null);
const googleFormRef = ref<HTMLFormElement | null>(null);
const googleGisRendered = ref(false);
const scriptId = 'google-gsi-client';
const csrfToken =
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

const submitGoogleToken = (credential: string) => {
    if (!googleIdTokenRef.value || !googleFormRef.value) {
        return;
    }
    googleIdTokenRef.value.value = credential;
    googleFormRef.value.submit();
};

const initGoogleButton = () => {
    const clientId = props.googleClientId;
    if (!clientId || !googleButtonRef.value || !window.google?.accounts?.id) {
        return;
    }

    googleButtonRef.value.innerHTML = '';
    window.google.accounts.id.initialize({
        client_id: clientId,
        callback: (response) => {
            const credential = response.credential ?? '';
            if (credential !== '') {
                submitGoogleToken(credential);
            }
        },
    });
    window.google.accounts.id.renderButton(googleButtonRef.value, {
        type: 'standard',
        theme: 'outline',
        size: 'large',
        shape: 'pill',
        text: 'continue_with',
        width: 320,
    });
    googleGisRendered.value = true;
};

onMounted(() => {
    if (!props.googleOAuthConfigured || !props.googleClientId) {
        return;
    }

    const existingScript = document.getElementById(scriptId) as HTMLScriptElement | null;
    if (existingScript) {
        if (window.google?.accounts?.id) {
            initGoogleButton();
        } else {
            existingScript.addEventListener('load', initGoogleButton, { once: true });
        }

        return;
    }

    const script = document.createElement('script');
    script.id = scriptId;
    script.src = 'https://accounts.google.com/gsi/client';
    script.async = true;
    script.defer = true;
    script.addEventListener('load', initGoogleButton, { once: true });
    document.head.appendChild(script);
});

onBeforeUnmount(() => {
    const existingScript = document.getElementById(scriptId) as HTMLScriptElement | null;
    existingScript?.removeEventListener('load', initGoogleButton);
});
</script>

<template>
    <Head title="Iniciar sesión">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="flex w-full min-w-0 flex-col gap-3 lg:max-w-md lg:justify-center">
        <p class="text-xs leading-snug text-[#666666] lg:hidden">
            Acceso con Google recomendado. Cuentas
            <span class="font-semibold text-[#003366]">@{{ cfrdDomain }}</span
            >.
        </p>

        <div
            v-if="status"
            class="rounded-md border border-green-200 bg-green-50 px-2 py-1.5 text-center text-xs text-green-800"
        >
            {{ status }}
        </div>

        <div class="space-y-2">
            <template v-if="googleOAuthConfigured">
                <a
                    v-if="!googleGisRendered"
                    :href="googleAuthUrl"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-[#dadce0] bg-white px-3 py-2 text-sm font-medium text-[#3c4043] shadow-sm transition hover:bg-[#f8f9fa] focus-visible:ring-2 focus-visible:ring-[#003366] focus-visible:outline-none"
                >
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            fill="#4285F4"
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        />
                        <path
                            fill="#34A853"
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        />
                        <path
                            fill="#FBBC05"
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        />
                        <path
                            fill="#EA4335"
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        />
                    </svg>
                    Continuar con Google
                </a>
                <div
                    v-show="googleGisRendered"
                    ref="googleButtonRef"
                    class="flex w-full justify-center"
                    data-test="google-gis-button"
                />
                <a
                    :href="googleAuthUrl"
                    class="text-center text-[11px] text-[#666666] underline-offset-2 hover:underline"
                >
                    Si el botón de Google no carga, usa este acceso alternativo
                </a>
            </template>
            <form
                ref="googleFormRef"
                :action="googleTokenUrl"
                method="post"
                class="hidden"
            >
                <input type="hidden" name="_token" :value="csrfToken" />
                <input ref="googleIdTokenRef" type="hidden" name="id_token" />
            </form>
            <p
                v-if="!googleOAuthConfigured"
                class="rounded-md border border-amber-200 bg-amber-50 px-2 py-1.5 text-center text-[10px] leading-snug text-amber-900"
            >
                Configura GOOGLE_* en .env para habilitar el botón de Google.
            </p>
        </div>

        <div class="relative py-1">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-[#e0e4e8]" />
            </div>
            <div class="relative flex justify-center">
                <span
                    class="bg-white px-2 text-[10px] font-medium text-[#666666]"
                    >Desarrollo</span
                >
            </div>
        </div>

        <div
            class="rounded-md border border-dashed border-[#F1C400] bg-[#fffdf5] px-2 py-1.5 text-[10px] leading-tight text-[#666666]"
        >
            <span class="font-semibold text-[#003366]">Solo admins · contraseña</span>
            —
            <span class="text-[#003366]">{{ devPasswordLoginEmails.join(', ') }}</span>
            ; resto con Google cuando OAuth esté activo.
        </div>

        <Form
            :action="store.url()"
            method="post"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-3"
        >
            <div class="grid gap-3">
                <div class="grid gap-1">
                    <Label for="email" class="text-xs text-[#003366]"
                        >Correo</Label
                    >
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        class="h-9 text-sm"
                        :placeholder="`nombre@${cfrdDomain}`"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-1">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="password" class="text-xs text-[#003366]"
                            >Contraseña</Label
                        >
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-[11px]"
                            :tabindex="5"
                        >
                            ¿Olvidaste?
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        class="h-9 text-sm"
                        placeholder="••••••••"
                    />
                    <InputError :message="errors.password" />
                </div>

                <Label
                    for="remember"
                    class="flex items-center gap-2 text-[11px] text-[#666666]"
                >
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    Recordarme
                </Label>

                <Button
                    type="submit"
                    class="h-9 w-full bg-[#003366] text-sm text-white hover:bg-[#00264d]"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Entrar con contraseña
                </Button>
            </div>
        </Form>
    </div>
</template>
