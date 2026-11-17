<script setup lang="ts">
import { t } from '@/i18n'
import { Link, MoreHorizontal } from 'lucide-vue-next'
import type { EvidenceView } from '@/types/portfolio'
defineProps<{ evidence: EvidenceView[]; editable?: boolean; compact?: boolean }>()
defineEmits<{ edit: [evidence: EvidenceView] }>()
</script>
<template>
    <p v-if="!evidence.length" class="empty-inline">
        {{ t('No evidence added. You can add a link or your own statement.') }}
    </p>
    <ul v-else class="evidence-list" :class="{ compact }">
        <li v-for="item in evidence" :key="item.id">
            <span class="evidence-icon"><Link aria-hidden="true" /></span>
            <div>
                <a
                    v-if="!editable && item.url"
                    :href="item.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    ><strong>{{ item.label }}</strong></a
                >
                <strong v-else>{{ item.label }}</strong>
                <span v-if="compact"> · {{ t(item.provenance) }}</span>
                <p v-else>{{ t(item.provenance) }}</p>
                <p v-if="compact">{{ t('Not independently verified.') }}</p>
            </div>
            <button
                v-if="editable"
                class="icon-button"
                :aria-label="t('Edit {name}', { name: item.label })"
                @click="$emit('edit', item)"
            >
                <MoreHorizontal />
            </button>
        </li>
    </ul>
</template>
