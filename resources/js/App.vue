<script setup lang="ts">
import { t } from '@/i18n'
import { RouterView, useRoute } from 'vue-router'
import { watchEffect } from 'vue'
import ConfirmationDialog from '@/components/ConfirmationDialog.vue'
const route = useRoute()
watchEffect(() => {
    const key = route.path.split('/')[1] || 'projects'
    const titles: Record<string, string> = {
        login: 'Sign in',
        register: 'Create account',
        projects: 'Projects',
        experiences: 'Experience',
        profile: 'Profile',
        skills: 'Skills',
        publish: 'Publish',
        p: 'Public portfolio',
        design: 'Preview',
    }
    document.title = `${t(titles[key] || 'Content unavailable')} · CareerAtlas`
})
</script>
<template>
    <a href="#main" class="skip-link">{{ t('Skip to content') }}</a
    ><RouterView :key="route.fullPath" /><ConfirmationDialog />
</template>
