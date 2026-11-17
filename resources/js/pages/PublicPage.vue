<script setup lang="ts">
import LanguageSelector from '@/components/LanguageSelector.vue'
import { t, skillCounts } from '@/i18n'
import { computed, onMounted, ref, watchEffect } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { BookOpen } from 'lucide-vue-next'
import AgentPanel, { type Citation } from '@/components/AgentPanel.vue'
import PortfolioContent from '@/components/PortfolioContent.vue'
import AppDialog from '@/components/AppDialog.vue'
import LoadState from '@/components/LoadState.vue'
import { useAction } from '@/composables/useFormState'
import { ApiError, request } from '@/lib/api'
import { portfolioView } from '@/lib/portfolio'
import type { PublicPortfolio, PublicItem, PublicSkill, Project } from '@/types/domain'
const slug = useRoute().params.slug as string
const data = ref<PublicPortfolio | null>(null),
    loading = useAction(),
    sourceLoading = useAction()
const sourceOpen = ref(false),
    sourceTitle = ref(''),
    source = ref<PublicPortfolio | null>(null),
    counts = ref(''),
    sourceCounts = ref<{ projects: number; experiences: number } | null>(null),
    sourceFallback = ref('Published source')
const view = computed(() => (data.value ? portfolioView(data.value) : null))
const sourceView = computed(() => (source.value ? portfolioView(source.value) : null))
watchEffect(() => {
    const fallback = t('Public portfolio')
    document.title = `${data.value?.profile.display_name || fallback} · CareerAtlas`
})
async function load() {
    const result = await loading.run(() => request<PublicPortfolio>(`/api/public/${slug}`))
    if (result) {
        data.value = result
    }
}
async function openProject(id: string) {
    source.value = null
    counts.value = ''
    sourceCounts.value = null
    sourceTitle.value = ''
    sourceFallback.value = 'Published project'
    sourceLoading.error.value = null
    sourceOpen.value = true
    const result = await sourceLoading.run(() =>
        request<{
            project: Partial<Project> & { id: string }
            items: PublicItem[]
            skills: PublicSkill[]
        }>(`/api/public/${slug}/projects/${id}`),
    )
    if (result) {
        sourceTitle.value = result.project.title || ''
        source.value = { profile: {}, items: result.items, skills: result.skills }
    }
}
async function openSkill(name: string) {
    const skill = data.value?.skills.find((skill) => skill.name === name)
    if (!skill) return
    source.value = null
    counts.value = ''
    sourceCounts.value = null
    sourceTitle.value = ''
    sourceFallback.value = 'Published skill'
    sourceLoading.error.value = null
    sourceOpen.value = true
    const result = await sourceLoading.run(() =>
        request<PublicSkill & { experiences: PublicItem[] }>(
            `/api/public/${slug}/skills/${skill.id}`,
        ),
    )
    if (result) {
        sourceTitle.value = result.name
        sourceCounts.value = {
            projects: result.project_count,
            experiences: result.experience_count,
        }
        source.value = { profile: {}, items: result.experiences, skills: [result] }
    }
}
async function openCitation(citation: Citation) {
    source.value = null
    counts.value = ''
    sourceCounts.value = null
    sourceTitle.value = ''
    sourceFallback.value = 'Published source'
    sourceLoading.error.value = null
    sourceOpen.value = true
    if (citation.experience_id) {
        const item = await sourceLoading.run(() =>
            request<PublicItem>(`/api/public/${slug}/experiences/${citation.experience_id}`),
        )
        const currentField = citation.field
            .split('.')
            .reduce<unknown>(
                (value, key) =>
                    value && typeof value === 'object'
                        ? (value as Record<string, unknown>)[key]
                        : undefined,
                item,
            )
        if (
            item &&
            (item.record_version_id !== citation.version ||
                !String(currentField ?? '').includes(citation.excerpt))
        ) {
            sourceLoading.error.value = new ApiError(
                409,
                'Published content changed. Ask again for current sources.',
            )
        } else if (item) {
            sourceTitle.value = item.title || ''
            sourceFallback.value = 'Published experience'
            source.value = { profile: {}, items: [item], skills: [] }
        }
    } else {
        const current = await sourceLoading.run(() =>
            request<PublicPortfolio>(`/api/public/${slug}`),
        )
        if (current && current.version !== citation.version) {
            sourceLoading.error.value = new ApiError(
                409,
                'Published content changed. Ask again for current sources.',
            )
        } else if (current) {
            sourceTitle.value = ''
            sourceFallback.value = 'Published profile'
            const currentField = citation.field
                .split('.')
                .reduce<unknown>(
                    (value, key) =>
                        value && typeof value === 'object'
                            ? (value as Record<string, unknown>)[key]
                            : undefined,
                    current.profile,
                )
            if (String(currentField ?? '').includes(citation.excerpt))
                counts.value = citation.excerpt
            else
                sourceLoading.error.value = new ApiError(
                    409,
                    'Published content changed. Ask again for current sources.',
                )
        }
    }
}
onMounted(load)
</script>
<template>
    <div class="public-page">
        <header class="public-header">
            <RouterLink to="/" class="brand"><BookOpen /><strong>CareerAtlas</strong></RouterLink>
            <nav v-if="data" :aria-label="t('Portfolio')">
                <a href="#projects">{{ t('Projects') }}</a
                ><a v-if="data.skills.length" href="#skills">{{ t('Skills') }}</a
                ><a href="#ask">{{ t('Ask') }}</a>
            </nav>
            <LanguageSelector />
        </header>
        <main id="main" class="public-main">
            <LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><template v-if="view"
                    ><section class="public-profile">
                        <p class="portfolio-eyebrow">{{ t('Public portfolio') }}</p>
                        <h1>{{ view.name || t('Public portfolio') }}</h1>
                        <p v-if="view.direction" class="profile-direction">{{ view.direction }}</p>
                        <p v-if="view.bio" class="profile-bio prose-value">{{ view.bio }}</p>
                        <ul v-if="view.contacts?.length" class="contact-links">
                            <li v-for="contact in view.contacts" :key="contact.url">
                                <a :href="contact.url" target="_blank" rel="noopener noreferrer">{{
                                    contact.label
                                }}</a>
                            </li>
                        </ul>
                        <p v-if="view.preferredLanguage" class="muted">
                            {{ t('Preferred language:') }} {{ view.preferredLanguage }}
                        </p>
                        <p class="published-date">{{ t('Published') }} {{ view.publishedAt }}</p>
                    </section>
                    <div class="public-grid">
                        <div id="skills">
                            <PortfolioContent
                                :portfolio="view"
                                complete
                                interactive
                                @project="openProject"
                                @skill="openSkill"
                            />
                        </div>
                        <AgentPanel :slug="slug" @source="openCitation" /></div></template
            ></LoadState>
        </main>
        <AppDialog v-model:open="sourceOpen" :title="sourceTitle || t(sourceFallback)" wide
            ><LoadState
                :loading="sourceLoading.busy.value"
                :error="sourceLoading.error.value"
                @retry="sourceOpen = false"
                ><p v-if="sourceCounts" class="source-counts">
                    {{ skillCounts(sourceCounts.projects, sourceCounts.experiences) }}
                </p>
                <p v-else-if="counts" class="source-counts">{{ counts }}</p>
                <PortfolioContent
                    v-if="sourceView"
                    :portfolio="sourceView"
                    complete
                    :show-project-link="false" /></LoadState
        ></AppDialog>
    </div>
</template>
