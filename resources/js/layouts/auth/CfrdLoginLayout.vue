<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import WorkflowAppIcon from '@/components/WorkflowAppIcon.vue';
import { home } from '@/routes';

const page = usePage();
const cfrdDomain = computed(() => String(page.props.cfrdDomain ?? 'cfrd.cl'));
</script>

<template>
    <div
        class="h-svh min-h-0 overflow-hidden bg-[#f5f6f8] font-[DM_Sans,ui-sans-serif,system-ui,sans-serif] text-[#1a1a1a]"
    >
        <div
            class="flex h-full min-h-0 items-center justify-center p-2 sm:p-3 md:p-4"
        >
            <div
                class="flex h-full max-h-full w-full max-w-4xl min-h-0 flex-col overflow-hidden rounded-2xl border border-[#e0e4e8] bg-white shadow-xl lg:max-h-[min(100%,42rem)] lg:flex-row xl:max-w-5xl"
            >
                <aside
                    class="flex shrink-0 flex-col justify-between gap-3 bg-[#003366] px-4 py-3 text-white sm:px-5 sm:py-4 lg:w-[40%] lg:min-w-[min(100%,17rem)] lg:max-w-xs lg:py-5"
                >
                    <div class="min-w-0">
                        <WorkflowAppIcon
                            class="mb-2 h-11 w-11 sm:mb-3 sm:h-12 sm:w-12"
                        />
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wider text-[#F1C400]"
                        >
                            Acceso institucional
                        </p>
                        <h1
                            class="mt-0.5 text-xl font-bold leading-tight tracking-tight sm:text-2xl"
                        >
                            Workflow
                        </h1>
                        <p
                            class="mt-1.5 text-[11px] leading-snug text-white/90 sm:text-xs"
                        >
                            Cuentas Google
                            <span
                                class="whitespace-nowrap font-semibold text-[#F1C400]"
                                >@{{ cfrdDomain }}</span
                            >
                        </p>
                    </div>
                    <div
                        class="flex flex-wrap items-center gap-2 border-t border-white/15 pt-2 sm:gap-3 sm:pt-3"
                    >
                        <img
                            src="/images/branding/cfrd-logo-light.svg"
                            alt="CFRD"
                            class="h-5 w-auto sm:h-6"
                        />
                        <img
                            src="/images/branding/udec-105-anos.svg"
                            alt="UdeC"
                            class="hidden h-4 w-auto opacity-95 sm:block sm:h-5"
                        />
                    </div>
                    <Link
                        :href="home()"
                        class="text-[11px] font-medium text-white/75 underline-offset-2 hover:text-white hover:underline"
                    >
                        Volver al inicio
                    </Link>
                </aside>

                <section
                    class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden bg-white"
                >
                    <div
                        class="flex min-h-0 flex-1 flex-col justify-center overflow-hidden px-4 py-3 sm:px-6 sm:py-4 lg:px-8 lg:py-5"
                    >
                        <slot />
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
