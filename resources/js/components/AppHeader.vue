<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Layers, Menu } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import WorkflowAppIcon from '@/components/WorkflowAppIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getWorkflowNavGroupsForUser } from '@/config/workflowNavigation';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const roleSlugs = computed(
    () => (page.props.auth?.user?.role_slugs as string[] | undefined) ?? [],
);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

const activeItemStyles = 'bg-neutral-100 text-neutral-900';

const mainNavItems: NavItem[] = [
    {
        title: 'Inicio',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

/** Mismo árbol que el sidebar (menú móvil / layout header), filtrado por rol. */
const workflowNavGroups = computed(() =>
    getWorkflowNavGroupsForUser(roleSlugs.value),
);
const avatarObjectPosition = computed(() => {
    const x = Number(auth.value?.user?.avatar_position_x ?? 0);
    const y = Number(auth.value?.user?.avatar_position_y ?? 0);

    return `${50 + x}% ${50 + y}%`;
});

const accesosModulosHref = `${dashboard.url()}#modulos`;
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only"
                                >Menú de navegación</SheetTitle
                            >
                            <SheetHeader class="flex justify-start text-left">
                                <WorkflowAppIcon class="size-9" />
                            </SheetHeader>
                            <nav class="-mx-3 space-y-6 py-4">
                                <div
                                    v-for="group in workflowNavGroups"
                                    :key="group.label"
                                >
                                    <p
                                        class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wide text-[#666]"
                                    >
                                        {{ group.label }}
                                    </p>
                                    <div class="space-y-0.5">
                                        <Link
                                            v-for="item in group.items"
                                            :key="`${group.label}-${item.title}`"
                                            :href="item.href"
                                            class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                            :class="
                                                whenCurrentUrl(
                                                    item.href,
                                                    activeItemStyles,
                                                )
                                            "
                                        >
                                            <component
                                                v-if="item.icon"
                                                :is="item.icon"
                                                class="h-5 w-5 shrink-0"
                                            />
                                            {{ item.title }}
                                        </Link>
                                    </div>
                                </div>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="dashboard()" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-10 flex h-full items-stretch">
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-2"
                        >
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        whenCurrentUrl(
                                            item.href,
                                            activeItemStyles,
                                        ),
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                    :href="item.href"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentUrl(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-[#003366]"
                                ></div>
                            </NavigationMenuItem>
                            <NavigationMenuItem
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                    :href="accesosModulosHref"
                                >
                                    <Layers class="mr-2 h-4 w-4" />
                                    Módulos
                                </Link>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center">
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-11 shrink-0 rounded-full p-0.5 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-10 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                        :style="{ objectPosition: avatarObjectPosition }"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
