<script setup lang="ts">
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import '@vue-flow/controls/dist/style.css';
import { VueFlow, type Edge, type Node } from '@vue-flow/core';
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';

defineProps<{
    nodes: Node[];
    edges: Edge[];
    /** Clases Tailwind para la altura del lienzo (ej. h-[min(680px,78vh)]). */
    wrapClass: string;
}>();
</script>

<template>
    <div
        :class="[
            'vue-flow-wrap spider-flow min-h-0 w-full rounded-lg border border-[#003366]/15 bg-gradient-to-b from-[#f8fafc] to-white',
            wrapClass,
        ]"
    >
        <VueFlow
            :nodes="nodes"
            :edges="edges"
            :default-viewport="{ zoom: 0.78, x: 8, y: 6 }"
            :min-zoom="0.18"
            :max-zoom="2.05"
            fit-view-on-init
            :nodes-draggable="true"
        >
            <Background pattern-color="#cbd5e1" :gap="20" :size="1.2" />
            <Controls />
        </VueFlow>
    </div>
</template>

<style scoped>
.spider-flow :deep(.vue-flow__edge-path) {
    stroke-linecap: round;
    filter: drop-shadow(0 0 0.5px rgba(30, 73, 118, 0.35));
}

.spider-flow :deep(.vue-flow__edge-text) {
    font-variant-numeric: tabular-nums;
}
</style>
