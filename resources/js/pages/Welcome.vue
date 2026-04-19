<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';

const page = usePage();
const appVersion = computed(() => String(page.props.appVersion ?? '0.0.0'));
</script>

<template>
    <Head title="Inicio">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap"
            rel="stylesheet"
        />
    </Head>
    <div
        class="min-h-screen bg-[#f5f6f8] font-[DM_Sans,ui-sans-serif,system-ui,sans-serif] text-[#1a1a1a]"
    >
        <header
            class="border-b border-white/10 bg-[#003366] text-white shadow-md"
        >
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6"
            >
                <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                    <img
                        src="/images/branding/cfrd-logo-light.svg"
                        alt="Centro de Formación y Recursos Didácticos — Universidad de Concepción"
                        class="h-9 w-auto sm:h-10"
                    />
                    <img
                        src="/images/branding/udec-105-anos.svg"
                        alt="Universidad de Concepción"
                        class="hidden h-8 w-auto opacity-95 sm:block md:h-9"
                    />
                </div>
                <nav class="flex items-center gap-2 text-sm font-medium sm:gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md bg-white/10 px-4 py-2 transition hover:bg-white/20"
                    >
                        Panel
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="rounded-md px-4 py-2 text-white/95 transition hover:bg-white/10"
                    >
                        Iniciar sesión
                    </Link>
                </nav>
            </div>
        </header>

        <section
            class="relative overflow-hidden bg-[#163558] bg-cover bg-center text-white"
            style="
                background-image: linear-gradient(
                        105deg,
                        rgba(0, 51, 102, 0.92) 0%,
                        rgba(22, 53, 88, 0.88) 45%,
                        rgba(22, 53, 88, 0.55) 100%
                    ),
                    url('/images/branding/cfrd-hero-home.jpg');
            "
        >
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:py-24">
                <p
                    class="mb-3 text-sm font-medium uppercase tracking-wide text-[#F1C400]"
                >
                    CFRD · Gestión y talento institucional
                </p>
                <h1
                    class="mb-4 max-w-3xl text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl"
                >
                    Workflow
                </h1>
                <p class="mb-6 max-w-2xl text-lg leading-relaxed text-white/90">
                    Plataforma para coordinar proyectos, cargas y avances con
                    vistas por rol (PMO, Coordinación, Jefatura y Colaborador
                    en escritorio), integrando habilidades, analítica y vínculo
                    con el ecosistema de mejora continua y el LRS institucional
                    CFRD.
                </p>
                <p class="mb-8 max-w-2xl text-base leading-relaxed text-white/80">
                    Visión: mejorar de forma
                    <strong class="font-semibold text-white">transversal y funcional</strong>
                    los procesos de planificación, seguimiento y reconocimiento
                    del quehacer, con trazabilidad y datos para la decisión.
                </p>
                <div class="flex flex-wrap gap-3">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login()"
                        class="inline-flex items-center justify-center rounded-md bg-[#009ee2] px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-[#0086c2]"
                    >
                        Entrar al sistema
                    </Link>
                    <a
                        href="https://cfrd.udec.cl/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center rounded-md border-2 border-white/40 bg-transparent px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
                    >
                        Sitio CFRD
                    </a>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
            <h2
                class="mb-8 text-center text-xl font-semibold text-[#003366] sm:text-2xl"
            >
                Qué habilita Workflow
            </h2>
            <ul
                class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <li
                    class="rounded-lg border border-[#e2e5ea] bg-white p-6 shadow-sm"
                >
                    <h3 class="mb-2 font-semibold text-[#003366]">
                        Operación y roles
                    </h3>
                    <p class="text-sm leading-relaxed text-[#666666]">
                        Tableros estilo Monday, Gantt, Kanban y app desktop para
                        ejecutar tareas con reglas claras de validación y
                        auditoría.
                    </p>
                </li>
                <li
                    class="rounded-lg border border-[#e2e5ea] bg-white p-6 shadow-sm"
                >
                    <h3 class="mb-2 font-semibold text-[#003366]">
                        Talento y LRS
                    </h3>
                    <p class="text-sm leading-relaxed text-[#666666]">
                        Skills con validación 360° y registro de actividades
                        alineado al banco de verbos CFRD (xAPI).
                    </p>
                </li>
                <li
                    class="rounded-lg border border-[#e2e5ea] bg-white p-6 shadow-sm sm:col-span-2 lg:col-span-1"
                >
                    <h3 class="mb-2 font-semibold text-[#003366]">
                        Mejora continua
                    </h3>
                    <p class="text-sm leading-relaxed text-[#666666]">
                        Alertas de sobrecarga, KPIs y trazabilidad para apoyar
                        decisiones y la calidad de los procesos en el tiempo.
                    </p>
                </li>
            </ul>
        </section>

        <footer class="border-t border-white/10 bg-[#003366] text-white">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-8 text-sm text-white/85 sm:flex-row sm:items-center sm:justify-between sm:px-6"
            >
                <div>
                    <p class="font-medium text-white">
                        Workflow · CFRD · Universidad de Concepción
                    </p>
                    <p class="mt-1 text-xs text-white/70">
                        Versión {{ appVersion }} — actualizar junto con
                        <code class="rounded bg-white/10 px-1">composer.json</code>
                        y
                        <code class="rounded bg-white/10 px-1">CHANGELOG</code>.
                    </p>
                </div>
                <div class="flex flex-wrap gap-4 text-xs">
                    <a
                        href="https://cfrd.udec.cl/"
                        class="underline-offset-2 hover:underline"
                        target="_blank"
                        rel="noopener noreferrer"
                        >cfrd.udec.cl</a
                    >
                    <a
                        href="https://www.udec.cl/"
                        class="underline-offset-2 hover:underline"
                        target="_blank"
                        rel="noopener noreferrer"
                        >udec.cl</a
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
