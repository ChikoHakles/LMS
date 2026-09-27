<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
    tone?: 'sidebar' | 'popover';
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    tone: 'popover',
});

const { getInitials } = useInitials();
const avatarUrl = computed(() => props.user.avatar?.trim() ?? '');
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="avatarUrl !== ''" :src="avatarUrl" :alt="user.name" />
        <AvatarFallback class="rounded-lg bg-secondary text-secondary-foreground">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium" :class="props.tone === 'sidebar' ? 'text-sidebar-foreground' : 'text-popover-foreground'">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
    </div>
</template>
