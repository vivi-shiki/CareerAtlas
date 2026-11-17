<script setup lang="ts">
import { locale, setLocale, t } from '@/i18n'
</script>
<template>
    <label class="language-selector">
        <span class="sr-only">{{ t('Interface language') }}</span>
        <select
            :value="locale"
            :aria-label="t('Interface language')"
            @change="setLocale(($event.target as HTMLSelectElement).value)"
        >
            <option value="en" lang="en">English</option>
            <option value="zh-CN" lang="zh-CN">中文</option>
            <option value="ja" lang="ja">日本語</option>
        </select>
    </label>
</template>
