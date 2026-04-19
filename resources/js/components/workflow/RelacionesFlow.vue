<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Maximize2 } from 'lucide-vue-next';
import type { Edge, Node } from '@vue-flow/core';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import RelacionesFlowPane from '@/components/workflow/RelacionesFlowPane.vue';
import {
    computeSpiderLayout,
    LAYOUT_NODE_H,
    LAYOUT_NODE_W,
} from '@/components/workflow/relacionesSpiderLayout';

type EdgeRow = {
    user_id: number;
    project_id: number;
    user_name: string;
    project_name: string;
    tareas_vinculadas: number;
};

const props = defineProps<{
    edges: EdgeRow[];
}>();

const modalOpen = ref(false);
/** Fuerza remount del VueFlow del modal para aplicar fit-view al abrir. */
const modalFlowKey = ref(0);

watch(modalOpen, (open) => {
    if (open) {
        modalFlowKey.value += 1;
    }
});

const LAYOUT_W = 1000;
const LAYOUT_H = 620;
const LAYOUT_PAD = 44;

const nodes = computed<Node[]>(() => {
    const userIds = [...new Set(props.edges.map((e) => e.user_id))];
    const projectIds = [...new Set(props.edges.map((e) => e.project_id))];

    const { users: userXY, projects: projXY } = computeSpiderLayout(
        userIds,
        projectIds,
        props.edges,
        {
            canvasW: LAYOUT_W,
            canvasH: LAYOUT_H,
            padding: LAYOUT_PAD,
            nodeW: LAYOUT_NODE_W,
            nodeH: LAYOUT_NODE_H,
        },
    );

    const ns: Node[] = [];
    for (const uid of userIds) {
        const row = props.edges.find((e) => e.user_id === uid);
        const pos = userXY.get(uid) ?? { x: LAYOUT_PAD, y: LAYOUT_PAD };
        ns.push({
            id: `u-${uid}`,
            position: pos,
            data: {
                label: row?.user_name ?? `Usuario ${uid}`,
            },
            style: {
                background: 'linear-gradient(145deg, #e8f2ff 0%, #f0f7ff 100%)',
                border: '1px solid #003366',
                borderRadius: '10px',
                padding: '8px 10px',
                fontSize: '11px',
                fontWeight: '600',
                color: '#003366',
                minWidth: '132px',
                width: `${LAYOUT_NODE_W}px`,
                maxWidth: `${LAYOUT_NODE_W}px`,
                minHeight: `${LAYOUT_NODE_H}px`,
                boxSizing: 'border-box',
                boxShadow: '0 2px 10px rgba(0, 51, 102, 0.12)',
            },
        });
    }
    for (const pid of projectIds) {
        const row = props.edges.find((e) => e.project_id === pid);
        const pos = projXY.get(pid) ?? {
            x: LAYOUT_W / 2 - LAYOUT_NODE_W / 2,
            y: LAYOUT_H / 2 - LAYOUT_NODE_H / 2,
        };
        ns.push({
            id: `p-${pid}`,
            position: pos,
            data: {
                label: row?.project_name ?? `Proyecto ${pid}`,
            },
            style: {
                background: 'linear-gradient(145deg, #ffffff 0%, #f1f5f9 100%)',
                border: '1px solid #003366',
                borderRadius: '10px',
                padding: '8px 10px',
                fontSize: '11px',
                fontWeight: '600',
                color: '#003366',
                minWidth: '132px',
                width: `${LAYOUT_NODE_W}px`,
                maxWidth: `${LAYOUT_NODE_W}px`,
                minHeight: `${LAYOUT_NODE_H}px`,
                boxSizing: 'border-box',
                boxShadow: '0 2px 10px rgba(0, 51, 102, 0.1)',
            },
        });
    }
    return ns;
});

const edgesFlow = computed<Edge[]>(() =>
    props.edges.map((e) => ({
        id: `e-${e.user_id}-${e.project_id}`,
        source: `u-${e.user_id}`,
        target: `p-${e.project_id}`,
        type: 'simplebezier',
        label: String(e.tareas_vinculadas),
        animated: true,
        style: {
            stroke: '#1e4976',
            strokeWidth: 1.35,
            strokeOpacity: 0.85,
        },
        labelStyle: { fill: '#003366', fontWeight: 600, fontSize: 10 },
        labelBgStyle: { fill: '#ffffff', fillOpacity: 0.92 },
    })),
);
</script>

<template>
    <div class="space-y-2">
        <div class="relative">
            <Button
                type="button"
                variant="outline"
                size="sm"
                class="absolute top-2 right-2 z-20 gap-1.5 border-[#003366]/25 bg-white/95 text-[#003366] shadow-sm backdrop-blur-sm hover:bg-[#f0f7ff]"
                @click="modalOpen = true"
            >
                <Maximize2 class="size-4" aria-hidden="true" />
                Pantalla completa
            </Button>

            <RelacionesFlowPane
                :nodes="nodes"
                :edges="edgesFlow"
                wrap-class="h-[min(680px,78vh)]"
            />
        </div>

        <Dialog v-model:open="modalOpen">
            <DialogContent
                class="flex h-[99vh] max-h-[99vh] w-[99vw] max-w-[99vw] flex-col gap-2 overflow-hidden rounded-lg p-3 sm:max-w-[99vw]"
            >
                <DialogHeader class="shrink-0 space-y-0 py-0 pr-10">
                    <DialogTitle class="text-base font-semibold text-[#003366]">
                        Mapa de relaciones (vista ampliada)
                    </DialogTitle>
                </DialogHeader>
                <div class="min-h-0 flex-1">
                    <RelacionesFlowPane
                        v-if="modalOpen"
                        :key="modalFlowKey"
                        :nodes="nodes"
                        :edges="edgesFlow"
                        wrap-class="h-[calc(99vh-4.25rem)]"
                    />
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
