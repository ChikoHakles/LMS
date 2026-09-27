<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import type { BreadcrumbItemType } from '@/types';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();
</script>

<template>
    <AppHeader :breadcrumbs="breadcrumbs" show-sidebar-trigger />
</template>
