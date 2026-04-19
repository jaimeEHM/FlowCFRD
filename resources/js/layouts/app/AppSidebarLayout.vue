<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import WorkflowRealtimeBridge from '@/components/workflow/WorkflowRealtimeBridge.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const workflowRealtimeEnabled = computed(
    () => page.props.workflowRealtimeEnabled === true,
);
const authUserId = computed(
    () => page.props.auth?.user?.id as number | undefined,
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
        <WorkflowRealtimeBridge
            v-if="workflowRealtimeEnabled && authUserId"
            :user-id="authUserId"
        />
    </AppShell>
</template>
