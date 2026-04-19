<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';

const props = withDefaults(
    defineProps<{
        name: string;
        avatar?: string | null;
        /** xs = 24px, sm = 28px, md = 40px */
        size?: 'xs' | 'sm' | 'md';
        showName?: boolean;
    }>(),
    {
        size: 'sm',
        showName: true,
    },
);

const sizeClass = computed(() => {
    if (props.size === 'xs') {
        return 'h-6 w-6';
    }
    if (props.size === 'md') {
        return 'h-10 w-10';
    }

    return 'h-7 w-7';
});

const fallbackClass = computed(() =>
    props.size === 'xs' ? 'text-[10px]' : 'text-[11px]',
);
</script>

<template>
    <div class="flex min-w-0 items-center gap-2">
        <Avatar :class="sizeClass" class="shrink-0">
            <AvatarImage
                v-if="avatar && avatar !== ''"
                :src="avatar"
                :alt="name"
            />
            <AvatarFallback :class="fallbackClass">{{
                getInitials(name)
            }}</AvatarFallback>
        </Avatar>
        <span v-if="showName" class="min-w-0 truncate text-inherit">{{
            name
        }}</span>
    </div>
</template>
