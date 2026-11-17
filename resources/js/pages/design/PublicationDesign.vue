<script setup lang="ts">
import { t } from '@/i18n'
import { computed, nextTick, ref } from 'vue'
import { Check, Eye, Info } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import PortfolioContent from '@/components/PortfolioContent.vue'
import type { PortfolioView } from '@/types/portfolio'

const props = defineProps<{ portfolio: PortfolioView }>()
const mobileReview = ref(false)
async function showReview(review: boolean) {
    mobileReview.value = review
    await nextTick()
    if (window.matchMedia('(max-width: 767px)').matches) {
        document.getElementById(review ? 'preview-heading' : 'selection-heading')?.focus()
    }
}
const fields = ref({
    name: true,
    bio: true,
    project: true,
    experience: true,
    contribution: true,
    team: false,
    skills: true,
    evidence: true,
    contact: false,
})
const selected = computed(() => {
    const portfolio = structuredClone(props.portfolio)
    portfolio.projects = portfolio.projects.filter((project) =>
        project.experiences.some((experience) => experience.title && experience.contribution),
    )
    if (!fields.value.name) portfolio.name = ''
    if (!fields.value.bio) {
        portfolio.bio = ''
        portfolio.direction = ''
    }
    if (!fields.value.project || !fields.value.experience) portfolio.projects = []
    for (const project of portfolio.projects)
        for (const experience of project.experiences) {
            if (!fields.value.contribution) experience.contribution = ''
            if (!fields.value.team) experience.teamOutcome = ''
            if (!fields.value.skills) experience.skills = []
            experience.evidence = fields.value.evidence ? experience.evidence.slice(0, 1) : []
        }
    return portfolio
})
</script>

<template>
    <WorkspaceShell active="Publish" preview>
        <div class="workspace-page publication-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <span>{{ t('Publish') }}</span
                ><span>/</span><strong>{{ t('Portfolio') }}</strong>
            </nav>
            <PageHeading
                :title="t('Review what becomes public')"
                :description="t('Only selected, confirmed content will be shared.')"
                :status="t('Not published')"
            />
            <ol class="publication-steps" :aria-label="t('Publication steps')">
                <li class="complete">
                    <span class="step-circle"><Check /></span><span>1 Select content</span>
                </li>
                <li aria-current="step">
                    <span class="step-circle">2</span><strong>{{ t('Preview') }}</strong>
                </li>
                <li>
                    <span class="step-circle">3</span><span>{{ t('Publish') }}</span>
                </li>
            </ol>
            <div class="publication-grid" :class="{ 'mobile-review': mobileReview }">
                <section class="publication-selection">
                    <h2 id="selection-heading" tabindex="-1">{{ t('Public content') }}</h2>
                    <label class="check-row"
                        ><Checkbox v-model="fields.name" />{{ t('Display name') }}</label
                    >
                    <label class="check-row"><Checkbox v-model="fields.bio" />{{ t('Bio') }}</label>
                    <label class="check-row"
                        ><Checkbox v-model="fields.project" />{{
                            portfolio.projects[0]?.title
                        }}</label
                    >
                    <div class="selection-tree">
                        <label class="check-row"
                            ><Checkbox v-model="fields.experience" />{{
                                portfolio.projects[0]?.experiences[0]?.title ||
                                'No confirmed experience'
                            }}</label
                        >
                        <label class="check-row"
                            ><Checkbox v-model="fields.contribution" />{{
                                t('My contribution')
                            }}</label
                        >
                        <label class="check-row"
                            ><Checkbox v-model="fields.team" />{{ t('Team outcome') }}</label
                        >
                        <label class="check-row"
                            ><Checkbox v-model="fields.skills" />{{ t('Skills') }}</label
                        >
                        <label class="check-row"
                            ><Checkbox v-model="fields.evidence" />{{
                                t('Evidence labels and links')
                            }}</label
                        >
                    </div>
                    <label class="check-row"
                        ><Checkbox v-model="fields.contact" />{{ t('Contact links') }}</label
                    >
                    <div class="selection-summary">
                        <strong
                            >{{ selected.projects.length }} {{ t('confirmed') }}
                            {{ selected.projects.length === 1 ? 'experience' : 'experiences' }}
                            {{ t('selected') }}</strong
                        >
                        <p class="info-line">
                            <Info />{{ t('Private notes are never included.') }}
                        </p>
                    </div>
                    <Button class="mobile-review-next" @click="showReview(true)">{{
                        t('Review selected content')
                    }}</Button>
                </section>
                <section class="visitor-preview">
                    <header>
                        <h2 id="preview-heading" tabindex="-1">{{ t('Visitor preview') }}</h2>
                        <a href="/design/portfolio" class="preview-link"
                            ><Eye />{{ t('Public view') }}</a
                        >
                    </header>
                    <div class="preview-frame">
                        <PortfolioContent :portfolio="selected" compact />
                    </div>
                </section>
            </div>
            <footer class="publication-footer" :class="{ 'mobile-review': mobileReview }">
                <div>
                    <p class="info-line">
                        <Info /><span>{{
                            t(
                                'Publishing creates a snapshot. Later edits stay private until you publish again.',
                            )
                        }}</span>
                    </p>
                    <p class="revocation-note">
                        {{ t('You can revoke future online access after publishing.') }}
                    </p>
                </div>
                <div class="actions">
                    <Button variant="outline" @click="showReview(false)">{{
                        t('Back to selection')
                    }}</Button
                    ><Button>{{ t('Publish snapshot') }}</Button>
                </div>
            </footer>
            <p class="fictional-note">Fictional demo data</p>
        </div>
    </WorkspaceShell>
</template>
