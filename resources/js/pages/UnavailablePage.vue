<script setup lang="ts">
import { t } from '@/i18n'
import LanguageSelector from '@/components/LanguageSelector.vue'
import { RouterLink } from 'vue-router'
import { BookOpen } from 'lucide-vue-next'
</script>
<template>
    <div class="public-page">
        <header class="public-header">
            <RouterLink to="/" class="brand"><BookOpen /><strong>CareerAtlas</strong></RouterLink>
            <LanguageSelector />
        </header>
        <main id="main" class="public-main">
            <section class="empty-state">
                <h1>{{ t('Content unavailable') }}</h1>
                <p>{{ t('This content is not available.') }}</p>
                <a href="/" class="text-action">{{ t('Return to CareerAtlas') }}</a>
            </section>
        </main>
    </div>
</template>
