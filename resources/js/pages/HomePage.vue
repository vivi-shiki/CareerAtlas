<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
    ArrowDown,
    ArrowRight,
    BookOpen,
    Check,
    MessageCircle,
    Share2,
    ShieldCheck,
} from 'lucide-vue-next'
import LanguageSelector from '@/components/LanguageSelector.vue'
import { locale, t } from '@/i18n'
import '@/../css/homepage.css'

const views = [
    {
        id: 'record',
        label: 'Record your work',
        alt: 'Illustrative workspace showing a contribution, skills and linked evidence.',
    },
    {
        id: 'prepare',
        label: 'Prepare for interviews',
        alt: 'Illustrative interview preparation with a source-linked answer and text practice.',
    },
    {
        id: 'share',
        label: 'Share selected work',
        alt: 'Illustrative public portfolio with selected work and a source-cited answer.',
    },
] as const
const selected = ref(0)
const view = computed(() => views[selected.value]!)
const imageSource = computed(() => `/images/homepage/${view.value.id}-${locale.value}.jpg`)
const imageFailed = ref(false)
const scenarios = [
    {
        icon: BookOpen,
        title: 'Keep the details that matter',
        body: 'Capture project notes, connect skills and evidence, and review AI drafts before confirming.',
    },
    {
        icon: MessageCircle,
        title: 'Prepare with your own experience',
        body: 'Turn selected sources into interview answers. Practice one question at a time and keep useful revisions.',
    },
    {
        icon: Share2,
        title: 'Share the right story',
        body: 'Choose fields for each opportunity, publish an independent portfolio, and let visitors explore cited answers.',
    },
]
function selectTab(index: number) {
    selected.value = index
    imageFailed.value = false
}
function moveTab(event: KeyboardEvent, index: number) {
    const keys = ['ArrowLeft', 'ArrowRight', 'Home', 'End']
    if (!keys.includes(event.key)) return
    event.preventDefault()
    const next =
        event.key === 'Home'
            ? 0
            : event.key === 'End'
              ? views.length - 1
              : (index + (event.key === 'ArrowRight' ? 1 : -1) + views.length) % views.length
    selectTab(next)
    document.getElementById(`workflow-tab-${views[next]!.id}`)?.focus()
}
</script>

<template>
    <div class="home-page">
        <header class="home-header">
            <div class="home-header-inner">
                <RouterLink to="/" class="home-brand"
                    ><BookOpen aria-hidden="true" /><strong>CareerAtlas</strong></RouterLink
                >
                <nav class="home-nav" :aria-label="t('Product navigation')">
                    <a href="#product">{{ t('Product') }}</a>
                    <a href="#use-cases">{{ t('Use cases') }}</a>
                    <a :href="`/docs/${locale}/index.html`">{{ t('Documentation') }}</a>
                </nav>
                <div class="home-header-actions">
                    <LanguageSelector /><RouterLink
                        to="/login"
                        class="home-button home-button-outline home-sign-in"
                        >{{ t('Sign in') }}</RouterLink
                    >
                </div>
            </div>
        </header>

        <main id="main">
            <section class="home-hero home-container" aria-labelledby="home-title">
                <h1 id="home-title">
                    {{ t('Your experience.') }}{{ ' '
                    }}<span>{{ t('A story backed by evidence.') }}</span>
                </h1>
                <p>
                    {{
                        t(
                            'Connect your projects, contributions and skills. Prepare for your next opportunity with a record you can trust.',
                        )
                    }}
                </p>
                <div class="home-hero-actions">
                    <RouterLink to="/login" class="home-button home-button-primary"
                        >{{ t('Sign in to get started') }}<ArrowRight aria-hidden="true"
                    /></RouterLink>
                    <a href="#product" class="home-button home-button-outline"
                        >{{ t('Explore the workflow') }}<ArrowDown aria-hidden="true"
                    /></a>
                </div>
            </section>

            <section
                id="product"
                class="home-product home-container"
                :aria-label="t('Product walkthrough')"
            >
                <div class="home-tabs" role="tablist" :aria-label="t('Product walkthrough')">
                    <button
                        v-for="(item, index) in views"
                        :id="`workflow-tab-${item.id}`"
                        :key="item.id"
                        type="button"
                        role="tab"
                        :aria-selected="selected === index"
                        :tabindex="selected === index ? 0 : -1"
                        aria-controls="workflow-preview"
                        @click="selectTab(index)"
                        @keydown="moveTab($event, index)"
                    >
                        {{ t(item.label) }}
                    </button>
                </div>
                <div
                    id="workflow-preview"
                    role="tabpanel"
                    :aria-labelledby="`workflow-tab-${view.id}`"
                    tabindex="0"
                >
                    <figure class="home-product-figure">
                        <a
                            :href="imageSource"
                            target="_blank"
                            rel="noopener"
                            :aria-label="t('Open full-size product preview')"
                            ><img
                                :key="imageSource"
                                :src="imageSource"
                                :alt="t(view.alt)"
                                width="1200"
                                height="560"
                                fetchpriority="high"
                                @error="imageFailed = true"
                                @load="imageFailed = false"
                        /></a>
                        <p v-if="imageFailed" class="home-image-fallback">{{ t(view.alt) }}</p>
                        <figcaption>
                            {{
                                t(
                                    'Illustrative example. Your records stay private until you publish.',
                                )
                            }}
                        </figcaption>
                    </figure>
                </div>
            </section>

            <div class="home-continuation home-container">
                <section id="use-cases" class="home-use-cases" aria-labelledby="use-cases-title">
                    <h2 id="use-cases-title">{{ t('One record. Many next steps.') }}</h2>
                    <p class="home-section-intro">
                        {{ t('Build on what you have already done.') }}
                    </p>
                    <div class="home-scenarios">
                        <article v-for="scenario in scenarios" :key="scenario.title">
                            <component :is="scenario.icon" aria-hidden="true" />
                            <h3>{{ t(scenario.title) }}</h3>
                            <p>{{ t(scenario.body) }}</p>
                        </article>
                    </div>
                </section>

                <section id="your-data" class="home-privacy" aria-labelledby="privacy-title">
                    <ShieldCheck aria-hidden="true" />
                    <div>
                        <h2 id="privacy-title">{{ t('Your experience. Your decision.') }}</h2>
                        <p>
                            {{
                                t(
                                    'Private by default. AI suggests; you confirm. Publish only what you choose, and revoke access when you need to.',
                                )
                            }}
                        </p>
                        <ul>
                            <li
                                v-for="label in [
                                    'Review before publishing',
                                    'Source-linked answers',
                                    'Independent opportunity pages',
                                ]"
                                :key="label"
                            >
                                <Check aria-hidden="true" />{{ t(label) }}
                            </li>
                        </ul>
                    </div>
                </section>

                <section class="home-closing" aria-labelledby="closing-title">
                    <h2 id="closing-title">{{ t('Start with one experience.') }}</h2>
                    <p>{{ t('You do not need a finished resume to begin.') }}</p>
                    <RouterLink to="/login" class="home-button home-button-primary"
                        >{{ t('Sign in to get started') }}<ArrowRight aria-hidden="true"
                    /></RouterLink>
                </section>
            </div>
        </main>
        <footer class="home-footer home-container">
            <RouterLink to="/" class="home-brand"
                ><BookOpen aria-hidden="true" /><strong>CareerAtlas</strong></RouterLink
            >
            <span class="home-languages"
                ><span lang="en">English</span> · <span lang="zh-CN">中文</span> ·
                <span lang="ja">日本語</span></span
            >
            <p>{{ t('Built around your evidence.') }}</p>
        </footer>
    </div>
</template>
