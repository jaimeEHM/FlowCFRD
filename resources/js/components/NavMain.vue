<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

withDefaults(
    defineProps<{
        items: NavItem[];
        groupLabel?: string;
    }>(),
    {
        groupLabel: 'Plataforma',
    },
);

const { isCurrentUrl } = useCurrentUrl();

const page = usePage();
const unreadNotificationsCount = computed(
    () => Number(page.props.unread_notifications_count ?? 0),
);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ groupLabel }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link
                        :href="item.href"
                        class="flex w-full min-w-0 items-center gap-2"
                    >
                        <component :is="item.icon" class="shrink-0" />
                        <span class="min-w-0 flex-1 truncate">{{
                            item.title
                        }}</span>
                        <span
                            v-if="
                                item.title === 'Notificaciones' &&
                                unreadNotificationsCount > 0
                            "
                            class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#003366] px-1 text-[10px] font-semibold text-white"
                            >{{
                                unreadNotificationsCount > 9
                                    ? '9+'
                                    : unreadNotificationsCount
                            }}</span
                        >
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
