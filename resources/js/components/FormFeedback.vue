<script setup lang="ts">
import { t } from '@/i18n'
import type { ApiError } from '@/lib/api'
defineProps<{ error?: ApiError | null; message?: string }>()
</script>
<template>
    <div v-if="error" role="alert" class="form-error">
        <p>{{ t(error.message) }}</p>
        <p v-if="error.status === 401">
            <a href="/login" target="_blank" rel="noopener noreferrer" class="text-action">{{
                t('Sign in again in a new tab')
            }}</a
            >{{ t('. Return here and retry to keep your input.') }}
        </p>
        <ul v-if="Object.keys(error.fields).length">
            <li v-for="(messages, key) in error.fields" :key="key">
                {{ messages.map((message) => t(message)).join(' ') }}
            </li>
        </ul>
        <slot />
    </div>
    <p v-if="message" role="status" class="success-message">{{ t(message) }}</p>
</template>
