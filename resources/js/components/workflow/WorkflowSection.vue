<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { NavItem } from '@/types';

type RelatedLink = {
    title: string;
    href: NavItem['href'];
};

defineProps<{
    contextLabel?: string;
    title: string;
    description?: string;
    /** Enlaces a pantallas del mismo flujo (p. ej. Proyectos ↔ Kanban). */
    relatedLinks?: RelatedLink[];
}>();
</script>

<template>
    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <p
            v-if="contextLabel"
            class="text-xs font-semibold uppercase tracking-wide text-[#003366]/80"
        >
            {{ contextLabel }}
        </p>
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[#003366]">
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="mt-2 max-w-3xl text-sm leading-relaxed text-[#666666]"
            >
                {{ description }}
            </p>
            <nav
                v-if="relatedLinks?.length"
                class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-[#003366]/10 pt-3 text-sm"
            >
                <span class="text-[#666]">Enlaces relacionados:</span>
                <Link
                    v-for="l in relatedLinks"
                    :key="l.title"
                    :href="l.href"
                    class="font-medium text-[#003366] underline-offset-2 hover:underline"
                >
                    {{ l.title }}
                </Link>
            </nav>
        </div>
        <div class="min-w-0 flex-1">
            <slot />
        </div>
    </div>
</template>

