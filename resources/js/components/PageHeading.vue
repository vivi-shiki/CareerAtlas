<script setup lang="ts">
defineProps<{ title: string; description?: string; status?: string }>()
</script>
<template>
    <div class="page-heading">
        <div>
            <h1>{{ title }}</h1>
            <p v-if="description">{{ description }}</p>
        </div>
        <span v-if="status" class="status-badge"
            ><span aria-hidden="true" class="status-dot"></span>{{ status }}</span
        >
    </div>
</template>
