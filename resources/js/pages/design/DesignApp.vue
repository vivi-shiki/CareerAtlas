<script setup lang="ts">
import { portfolioFixture, type Density } from '@/fixtures/portfolio'
import ExperienceDesign from '@/pages/design/ExperienceDesign.vue'
import PublicationDesign from '@/pages/design/PublicationDesign.vue'
import PublicDesign from '@/pages/design/PublicDesign.vue'
import WorkspaceDesign from '@/pages/design/WorkspaceDesign.vue'

const path = window.location.pathname
const requestedDensity = new URLSearchParams(window.location.search).get('density')
const density: Density =
    requestedDensity === 'sparse' || requestedDensity === 'dense' ? requestedDensity : 'normal'
const portfolio = portfolioFixture(density)
const titles: Record<string, string> = {
    editor: 'Experience',
    publication: 'Publication preview',
    portfolio: 'Alex Morgan',
    projects: 'Projects',
    skills: 'Skills',
    profile: 'Profile',
    login: 'Sign in',
}
document.title = `${titles[path.split('/')[2] || ''] || 'Workspace'} · CareerAtlas`
</script>

<template>
    <PublicationDesign v-if="path === '/design/publication'" :portfolio="portfolio" />
    <PublicDesign v-else-if="path === '/design/portfolio'" :portfolio="portfolio" />
    <WorkspaceDesign
        v-else-if="['projects', 'skills', 'profile', 'login'].includes(path.split('/')[2] || '')"
        :screen="path.split('/')[2]!"
        :portfolio="portfolio"
        :sparse="density === 'sparse'"
    />
    <ExperienceDesign v-else :portfolio="portfolio" />
</template>
