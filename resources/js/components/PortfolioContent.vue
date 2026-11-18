<script setup lang="ts">
import { t } from '@/i18n'
import { ExternalLink, Info, Link } from 'lucide-vue-next'
import type { PortfolioView } from '@/types/portfolio'
import SkillChips from './SkillChips.vue'
import EvidenceList from './EvidenceList.vue'

withDefaults(
    defineProps<{
        portfolio: PortfolioView
        compact?: boolean
        complete?: boolean
        interactive?: boolean
        showProjectLink?: boolean
    }>(),
    { compact: false, complete: false, interactive: false, showProjectLink: true },
)
defineEmits<{ project: [id: string]; skill: [name: string] }>()
</script>

<template>
    <section class="portfolio-content" :class="{ 'portfolio-compact': compact }">
        <header v-if="compact" class="preview-profile">
            <h2 v-if="portfolio.name">{{ portfolio.name }}</h2>
            <p v-if="portfolio.direction" class="profile-direction">{{ portfolio.direction }}</p>
            <p v-if="portfolio.bio" class="profile-bio">{{ portfolio.bio }}</p>
            <ul v-if="portfolio.contacts?.length" class="contact-links">
                <li v-for="contact in portfolio.contacts" :key="contact.url">
                    <a :href="contact.url" target="_blank" rel="noopener noreferrer">{{
                        contact.label
                    }}</a>
                </li>
            </ul>
            <p v-if="complete && portfolio.preferredLanguage" class="muted">
                {{ t('Preferred language:') }} {{ portfolio.preferredLanguage }}
            </p>
        </header>
        <h2 v-if="!compact" id="projects">{{ t('Selected work') }}</h2>
        <p v-if="portfolio.introduction" class="profile-bio">{{ portfolio.introduction }}</p>
        <p v-if="!portfolio.projects.length" class="empty-inline">
            {{ t('No published work is available.') }}
        </p>
        <article
            v-for="(project, index) in portfolio.projects"
            :key="`${project.id}-${index}`"
            class="portfolio-project"
        >
            <h3>{{ project.title }}</h3>
            <p class="project-meta">
                {{ project.type
                }}<template v-if="!complete && project.experiences[0]?.occurred">
                    · {{ project.experiences[0].occurred }}</template
                >
            </p>
            <p v-if="complete && project.role" class="muted">{{ t('Role:') }} {{ project.role }}</p>
            <p v-if="complete && project.occurred" class="muted">
                {{ t('Project dates:') }} {{ project.occurred }}
            </p>
            <p v-if="(!compact || complete) && project.summary" class="project-summary">
                {{ project.summary }}
            </p>
            <section
                v-for="experience in project.experiences"
                :key="experience.id"
                class="published-experience"
            >
                <template v-if="complete && experience.title"
                    ><h4 class="experience-title">{{ experience.title }}</h4>
                    <p v-if="experience.recordType" class="record-type">
                        {{ experience.recordType }}
                    </p></template
                >
                <p v-if="complete && experience.occurred" class="muted">
                    {{ experience.occurred }}
                </p>
                <template v-if="experience.contribution"
                    ><h4>{{ t('My contribution') }}</h4>
                    <p class="prose-value">{{ experience.contribution }}</p></template
                >
                <template v-if="experience.teamOutcome"
                    ><h4>{{ t('Team outcome') }}</h4>
                    <p class="prose-value">{{ experience.teamOutcome }}</p></template
                >
                <template v-if="complete"
                    ><template
                        v-for="field in [
                            { label: t('Result'), value: experience.result },
                            { label: t('Validation'), value: experience.validation },
                            { label: t('Remaining work'), value: experience.remainingWork },
                        ]"
                        :key="field.label"
                        ><template v-if="field.value"
                            ><h4>{{ field.label }}</h4>
                            <p class="prose-value">{{ field.value }}</p></template
                        ></template
                    ></template
                >
                <template v-if="experience.skills.length"
                    ><h4>{{ t('Skills') }}</h4>
                    <SkillChips
                        :skills="experience.skills"
                        :interactive="interactive"
                        @select="$emit('skill', $event)"
                /></template>
                <template v-if="compact && experience.evidence.length">
                    <ul class="preview-evidence">
                        <li v-for="evidence in experience.evidence" :key="evidence.id">
                            <Link aria-hidden="true" /><span
                                ><a
                                    v-if="complete && evidence.url"
                                    :href="evidence.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    >{{ evidence.label }}</a
                                ><template v-else>{{ evidence.label }}</template> ·
                                {{ t(evidence.provenance) }}
                                <p v-if="complete && evidence.statement" class="evidence-statement">
                                    {{ evidence.statement }}
                                </p></span
                            >
                        </li>
                    </ul>
                    <p class="info-line">
                        <Info aria-hidden="true" />{{ t('Not independently verified') }}
                    </p>
                </template>
                <template v-if="!compact">
                    <button
                        v-if="showProjectLink"
                        class="text-action"
                        @click="$emit('project', project.id)"
                    >
                        {{ t('Read project') }} <ExternalLink aria-hidden="true" />
                    </button>
                    <section class="published-evidence">
                        <h3>{{ t('Evidence') }}</h3>
                        <EvidenceList :evidence="experience.evidence" compact /><template
                            v-if="complete"
                            ><p
                                v-for="evidence in experience.evidence.filter(
                                    (item) => item.statement,
                                )"
                                :key="evidence.id"
                                class="evidence-statement"
                            >
                                <strong>{{ evidence.label }}</strong
                                >: {{ evidence.statement }}
                            </p></template
                        >
                    </section>
                </template>
            </section>
        </article>
    </section>
</template>
