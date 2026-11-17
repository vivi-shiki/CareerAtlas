<script setup lang="ts">
import { t } from '@/i18n'
import type { ApiError } from '@/lib/api'
import { Button } from './ui/button'
defineProps<{ loading?: boolean; error?: ApiError | null }>()
defineEmits<{ retry: [] }>()
</script>
<template>
    <div v-if="loading" role="status" class="loading-state">{{ t('Loading your workspace…') }}</div>
    <section v-else-if="error?.status === 404" class="empty-state">
        <h1>{{ t('Content unavailable') }}</h1>
        <p>{{ t('This content is not available.') }}</p>
    </section>
    <div v-else-if="error" class="empty-state">
        <p role="alert">{{ t(error.message) }}</p>
        <Button variant="outline" @click="$emit('retry')">{{ t('Retry') }}</Button>
    </div>
    <slot v-else />
</template>
