<script setup lang="ts">
import { t } from '@/i18n'
defineProps<{ modelValue: string; disabled?: boolean }>()
defineEmits<{ 'update:modelValue': [value: string] }>()
</script>
<template>
    <label
        >{{ t('Target language')
        }}<select
            :value="modelValue"
            :disabled="disabled"
            @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option value="en">English</option>
            <option value="zh">中文</option>
            <option value="ja">日本語</option>
        </select></label
    >
</template>
