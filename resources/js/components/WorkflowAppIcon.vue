<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 64 64"
        fill="none"
        aria-hidden="true"
        focusable="false"
        :class="cn('shrink-0', props.class)"
        v-bind="$attrs"
    >
        <rect width="64" height="64" rx="14" fill="#003366" />
        <path
            d="M14 44V22l8 14 8-14 8 14 8-14v22"
            stroke="#F1C400"
            stroke-width="3.2"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <circle cx="14" cy="22" r="2.5" fill="#F1C400" />
        <circle cx="50" cy="22" r="2.5" fill="#F1C400" />
        <circle cx="32" cy="44" r="2.5" fill="#fff" opacity=".9" />
    </svg>
</template>
