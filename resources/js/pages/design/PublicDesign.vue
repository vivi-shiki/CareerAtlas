<script setup lang="ts">
import { t } from '@/i18n'
import { computed } from 'vue'
import { BookOpen, ExternalLink, Send } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import PortfolioContent from '@/components/PortfolioContent.vue'
import type { PortfolioView } from '@/types/portfolio'

const props = defineProps<{ portfolio: PortfolioView }>()
const selected = computed(() => {
    const portfolio = structuredClone(props.portfolio)
    portfolio.projects = portfolio.projects.filter((project) =>
        project.experiences.some((experience) => experience.title && experience.contribution),
    )
    for (const project of portfolio.projects)
        for (const experience of project.experiences) {
            experience.teamOutcome = ''
            experience.evidence = experience.evidence.slice(0, 1)
        }
    return portfolio
})
</script>

<template>
    <div class="public-page">
        <header class="public-header">
            <a href="/design/portfolio" class="brand"><BookOpen /><strong>CareerAtlas</strong></a>
            <nav :aria-label="t('Portfolio')">
                <a href="#projects">{{ t('Projects') }}</a
                ><a href="#skills">{{ t('Skills') }}</a>
            </nav>
        </header>
        <main id="main" class="public-main">
            <section class="public-profile">
                <p class="portfolio-eyebrow">{{ t('Public portfolio') }}</p>
                <h1>{{ portfolio.name }}</h1>
                <p class="profile-direction">{{ portfolio.direction }}</p>
                <p class="profile-bio">{{ portfolio.bio }}</p>
                <p class="published-date">{{ t('Published') }} {{ portfolio.publishedAt }}</p>
                <a class="mobile-ask-link" href="#ask">{{ t('Ask about this work') }}</a>
            </section>
            <div class="public-grid">
                <PortfolioContent :portfolio="selected" />
                <aside id="ask" class="question-panel">
                    <h2>{{ t('Ask about this work') }}</h2>
                    <p class="muted">{{ t('Answers use this published portfolio only.') }}</p>
                    <div v-if="selected.projects.length" class="example-conversation">
                        <p class="question-bubble">What did Alex build in Vue?</p>
                        <p class="answer">
                            Alex built the search interface and keyboard navigation for Atlas Notes,
                            and wrote component tests. <a href="#source">[1]</a>
                        </p>
                        <a id="source" href="#projects" class="citation-card"
                            ><strong>[1]</strong
                            ><span
                                ><b>Atlas Notes</b><span>Accessible project search</span
                                ><small>Published 17 Nov 2026</small></span
                            ><ExternalLink aria-hidden="true" /></a
                        ><Button variant="outline" class="suggested-question"
                            >What evidence supports this?</Button
                        >
                    </div>
                    <p v-else class="empty-inline">
                        There are no published experiences to answer from yet. You can still read
                        the profile.
                    </p>
                    <form class="question-form" @submit.prevent>
                        <label class="sr-only" for="question">Ask about a project or skill</label
                        ><Input
                            id="question"
                            placeholder="Ask about a project or skill..."
                        /><Button type="submit" size="icon" aria-label="Send question"
                            ><Send
                        /></Button>
                    </form>
                    <p class="ai-disclaimer">
                        AI-generated answers may need checking. Inspect the sources.
                    </p>
                </aside>
            </div>
        </main>
        <footer class="public-footer">
            <small class="fictional-note">Fictional demo data</small>
        </footer>
    </div>
</template>
