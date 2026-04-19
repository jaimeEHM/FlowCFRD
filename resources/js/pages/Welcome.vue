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
                    vistas web por rol (PMO, Coordinación, Jefatura y
                    Colaborador), integrando habilidades, analítica y vínculo con
                    el ecosistema de mejora continua y el LRS institucional
                    CFRD. En la versión actual el tablero macro PMO integra Kanban,
                    carga de equipo, KPI por proyecto, Gantt por tareas y lista
                    de cartera; workspace por proyecto (tabla, cronograma,
                    calendario); Kanban colaborativo con grupos; notificaciones
                    en tiempo real (Reverb + Echo) y API REST con Sanctum.
                </p>
                <p class="mb-8 max-w-2xl text-base leading-relaxed text-white/80">
                    <span class="text-white/90">Visión:</span> mejorar de forma
                    <strong class="font-semibold text-white">transversal y funcional</strong>
                    los procesos de planificación, seguimiento y reconocimiento
                    del quehacer, con trazabilidad y datos para la decisión
                    (incl. cliente escritorio y automatización LRS en la hoja de
                    ruta).
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

        <section
            class="border-b border-[#e2e5ea] bg-white/80 py-10 backdrop-blur-sm"
        >
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <h2
                    class="mb-4 text-center text-lg font-semibold text-[#003366] sm:text-xl"
                >
                    Estado del producto (v{{ appVersion }})
                </h2>
                <p
                    class="mx-auto mb-6 max-w-3xl text-center text-sm leading-relaxed text-[#555555]"
                >
                    Alineado a
                    <code
                        class="rounded bg-[#f0f4f8] px-1.5 py-0.5 text-xs text-[#003366]"
                        >doc/documentacion/</code
                    >
                    y al CHANGELOG: lo siguiente está operativo en web; el resto
                    sigue en la hoja de ruta.
                </p>
                <ul
                    class="mx-auto grid max-w-3xl list-inside list-disc gap-2 text-sm text-[#444444] sm:columns-2 sm:gap-x-8"
                >
                    <li class="break-inside-avoid">
                        Módulos Inertia por rol, RBAC (Spatie) y auditoría de
                        acciones
                    </li>
                    <li class="break-inside-avoid">
                        Proyectos y tareas; Kanban por proyecto (grupos,
                        colaboradores, arrastre); backlog, validación de
                        urgentes y minutas
                    </li>
                    <li class="break-inside-avoid">
                        PMO: tablero macro con pestañas (indicadores, Gantt por
                        tareas, lista cartera, Kanban, carga equipo) y edición
                        tipo Monday en cartera
                    </li>
                    <li class="break-inside-avoid">
                        Matriz de skills y mapa colaboradores–proyectos (grafo
                        interactivo)
                    </li>
                    <li class="break-inside-avoid">
                        API REST
                        <code class="text-xs text-[#003366]">/api/v1</code>
                        con Laravel Sanctum (tokens Bearer)
                    </li>
                    <li class="break-inside-avoid">
                        Seed demo: equipo CFRD y cartera de proyectos; OAuth
                        Google y login desarrollo
                    </li>
                    <li class="break-inside-avoid text-[#666666]">
                        Próximo foco: cliente escritorio (Echo opcional), envío
                        LRS xAPI, analítica de sobrecarga
                    </li>
                </ul>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
            <h2
                class="mb-8 text-center text-xl font-semibold text-[#003366] sm:text-2xl"
            >
                Pilares (visión y evolución)
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
                        <strong class="font-medium text-[#333333]">Hoy:</strong>
                        workspace por proyecto (tabla, cronograma, calendario,
                        Kanban), tablero macro PMO con segmentos, API
                        <code class="text-xs text-[#003366]">/api/v1</code>.
                        <span class="mt-1 block text-[#888888]">
                            <strong class="font-medium text-[#555555]"
                                >Hoja de ruta:</strong
                            >
                            aplicación escritorio Electron con tiempo real
                            opcional.
                        </span>
                    </p>
                </li>
                <li
                    class="rounded-lg border border-[#e2e5ea] bg-white p-6 shadow-sm"
                >
                    <h3 class="mb-2 font-semibold text-[#003366]">
                        Talento y LRS
                    </h3>
                    <p class="text-sm leading-relaxed text-[#666666]">
                        <strong class="font-medium text-[#333333]">Hoy:</strong>
                        skills con validaciones en flujo, mapa de relaciones,
                        pantalla LRS (configuración).
                        <span class="mt-1 block text-[#888888]">
                            <strong class="font-medium text-[#555555]"
                                >Hoja de ruta:</strong
                            >
                            cierre 360° completo y envío de statements xAPI al
                            LRS institucional.
                        </span>
                    </p>
                </li>
                <li
                    class="rounded-lg border border-[#e2e5ea] bg-white p-6 shadow-sm sm:col-span-2 lg:col-span-1"
                >
                    <h3 class="mb-2 font-semibold text-[#003366]">
                        Mejora continua
                    </h3>
                    <p class="text-sm leading-relaxed text-[#666666]">
                        <strong class="font-medium text-[#333333]">Hoy:</strong>
                        KPI y Gantt por tareas en tablero macro, mapa de calor de
                        carga, notificaciones persistidas y push vía Reverb,
                        recargas Inertia selectivas, auditoría.
                        <span class="mt-1 block text-[#888888]">
                            <strong class="font-medium text-[#555555]"
                                >Hoja de ruta:</strong
                            >
                            alertas de sobrecarga y analítica más profunda.
                        </span>
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
                        Versión {{ appVersion }} (fuente:
                        <code class="rounded bg-white/10 px-1">composer.json</code>
                        vía Inertia). Documentación viva y notas de release:
                        <code class="rounded bg-white/10 px-1"
                            >doc/documentacion/</code
                        >
                        y
                        <code class="rounded bg-white/10 px-1"
                            >doc/project/CHANGELOG.md</code
                        >.
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
