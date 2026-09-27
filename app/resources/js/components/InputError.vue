<script setup lang="ts">
const props = defineProps<{
    message?: string;
}>();
</script>

<template>
    <p v-if="props.message" class="text-sm text-destructive" role="alert" aria-live="polite">
        {{ props.message }}
    </p>
</template>
