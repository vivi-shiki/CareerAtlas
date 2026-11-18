<script setup lang="ts">
import { t } from '@/i18n'
import type { SourceChange } from '@/types/p1'
defineProps<{ changes?: SourceChange[] }>()
</script>
<template>
    <p
        v-if="changes?.some((change) => change.state !== 'current')"
        class="p1-warning"
        role="status"
    >
        {{ t('Sources have changed. Review them before confirming.') }}
    </p>
</template>
