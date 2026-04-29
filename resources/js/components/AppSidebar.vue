<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import PmoMacroSidebarProjects from '@/components/workflow/PmoMacroSidebarProjects.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { getWorkflowNavGroupsForUser } from '@/config/workflowNavigation';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const navGroups = computed(() =>
    getWorkflowNavGroupsForUser(
        (page.props.auth?.user?.role_slugs as string[] | undefined) ?? [],
    ),
);

/** Enlaces inferiores opcionales (p. ej. ayuda institucional). */
const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="min-h-0">
            <div class="shrink-0">
                <NavMain
                    v-for="group in navGroups"
                    :key="group.label"
                    :group-label="group.label"
                    :items="group.items"
                />
            </div>
            <PmoMacroSidebarProjects />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length > 0" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
