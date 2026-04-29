<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
    compactName?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    compactName: false,
});

const { getInitials } = useInitials();
const displayNameRef = ref<HTMLElement | null>(null);
const useFallbackCompactName = ref(false);
let resizeObserver: ResizeObserver | null = null;

// Compute whether we should show the avatar image
const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);
const avatarObjectPosition = computed(() => {
    const x = Number(props.user.avatar_position_x ?? 0);
    const y = Number(props.user.avatar_position_y ?? 0);

    return `${50 + x}% ${50 + y}%`;
});

const normalizedNameTokens = computed(() => {
    const baseName = props.user.name.split('—')[0]?.trim() ?? props.user.name.trim();

    return baseName.split(/\s+/).filter(Boolean);
});

const firstName = computed(() => normalizedNameTokens.value[0] ?? '');

const firstSurname = computed(() => {
    const parts = normalizedNameTokens.value;
    if (parts.length < 2) {
        return '';
    }

    // Regla de negocio: los dos apellidos van al final del nombre.
    // El primer apellido corresponde al penúltimo bloque.
    return parts[parts.length - 2] ?? '';
});

const compactNamePrimary = computed(() => {
    if (!firstName.value) {
        return props.user.name;
    }
    if (!firstSurname.value) {
        return firstName.value;
    }

    return `${firstName.value} ${firstSurname.value}`;
});

const compactNameFallback = computed(() => {
    if (!firstName.value) {
        return props.user.name;
    }
    if (!firstSurname.value) {
        return firstName.value;
    }

    return `${firstName.value} ${firstSurname.value[0]}.`;
});

const displayName = computed(() => {
    if (!props.compactName) {
        return props.user.name;
    }

    return useFallbackCompactName.value
        ? compactNameFallback.value
        : compactNamePrimary.value;
});

const updateOverflowState = () => {
    if (!props.compactName || displayNameRef.value === null) {
        useFallbackCompactName.value = false;

        return;
    }

    useFallbackCompactName.value =
        displayNameRef.value.scrollWidth > displayNameRef.value.clientWidth;
};

onMounted(async () => {
    await nextTick();
    updateOverflowState();

    if (typeof ResizeObserver !== 'undefined' && displayNameRef.value) {
        resizeObserver = new ResizeObserver(() => {
            updateOverflowState();
        });
        resizeObserver.observe(displayNameRef.value);
    }
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
});

watch(
    () => [props.user.name, props.compactName],
    async () => {
        await nextTick();
        updateOverflowState();
    },
);
</script>

<template>
    <Avatar class="h-10 w-10 shrink-0 overflow-hidden rounded-lg">
        <AvatarImage
            v-if="showAvatar"
            :src="user.avatar!"
            :alt="user.name"
            :style="{ objectPosition: avatarObjectPosition }"
        />
        <AvatarFallback class="rounded-lg text-black">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span ref="displayNameRef" class="truncate font-medium" :title="user.name">{{
            displayName
        }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{
            user.email
        }}</span>
    </div>
</template>
