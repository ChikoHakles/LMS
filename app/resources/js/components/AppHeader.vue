<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    showSidebarTrigger?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    showSidebarTrigger: false,
});

const page = usePage<SharedData>();
const userName = computed(() => page.props.auth.user?.name ?? '');
</script>

<template>
    <header class="z-10 flex min-h-16 shrink-0 items-center gap-3 border-b border-border bg-card px-4 sm:px-6">
        <SidebarTrigger v-if="props.showSidebarTrigger" />

        <Link v-else class="shrink-0" :href="route('dashboard')" aria-label="Beranda Ruang">
            <AppLogo tone="surface" />
        </Link>

        <nav aria-label="Jejak navigasi" class="min-w-0 flex-1">
            <ol class="flex min-w-0 items-center gap-2 text-sm">
                <li class="shrink-0">
                    <Link :href="route('dashboard')" class="text-muted-foreground transition-colors hover:text-foreground focus-visible:rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">Ruang</Link>
                </li>
                <template v-for="(item, index) in props.breadcrumbs" :key="`${item.href}-${index}`">
                    <li aria-hidden="true" class="shrink-0 text-muted-foreground">/</li>
                    <li class="min-w-0 truncate">
                        <Link
                            v-if="index < props.breadcrumbs.length - 1"
                            :href="item.href"
                            class="truncate text-muted-foreground transition-colors hover:text-foreground focus-visible:rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            {{ item.title }}
                        </Link>
                        <span v-else aria-current="page" class="truncate font-semibold text-foreground">{{ item.title }}</span>
                    </li>
                </template>
                <li v-if="props.breadcrumbs.length === 0" aria-current="page" class="shrink-0 font-semibold text-foreground">Beranda</li>
            </ol>
        </nav>

        <div v-if="userName" class="hidden max-w-48 truncate text-sm font-medium text-foreground sm:block">{{ userName }}</div>
    </header>
</template>

