<script setup lang="ts">
import { t } from '@/i18n'
import { X } from 'lucide-vue-next'
defineProps<{ skills: string[]; editable?: boolean; interactive?: boolean }>()
defineEmits<{ remove: [skill: string]; select: [skill: string] }>()
</script>
<template>
    <div class="skill-chips">
        <template v-for="skill in skills" :key="skill"
            ><button v-if="interactive" class="skill-chip" @click="$emit('select', skill)">
                {{ skill }}</button
            ><span v-else class="skill-chip"
                >{{ skill
                }}<button
                    v-if="editable"
                    :aria-label="t('Remove {name}', { name: skill })"
                    @click="$emit('remove', skill)"
                >
                    <X /></button></span
        ></template>
        <slot />
    </div>
</template>
