<script setup lang="ts">
defineProps<{
    contextLabel?: string;
    title: string;
    description?: string;
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
        </div>
        <div class="min-w-0 flex-1">
            <slot />
        </div>
    </div>
</template>

