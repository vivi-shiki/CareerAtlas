<script setup lang="ts">
import { t } from '@/i18n'
import type { Experience } from '@/types/domain'
const props = defineProps<{ modelValue: string[]; records: Experience[]; disabled?: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: string[]] }>()
function toggle(id: string, checked: boolean) {
    emit(
        'update:modelValue',
        checked ? [...props.modelValue, id] : props.modelValue.filter((value) => value !== id),
    )
}
</script>
<template>
    <fieldset :disabled="disabled">
        <legend>{{ t('Choose confirmed sources') }}</legend>
        <p v-if="!records.length">{{ t('Confirm an experience before choosing sources.') }}</p>
        <label v-for="record in records" :key="record.id" class="p1-check"
            ><input
                type="checkbox"
                :checked="modelValue.includes(record.id)"
                @change="toggle(record.id, ($event.target as HTMLInputElement).checked)"
            /><span>{{ record.title || t('Untitled experience') }}</span></label
        >
    </fieldset>
</template>
