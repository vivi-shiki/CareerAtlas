<script setup lang="ts">
import { t } from '@/i18n'
import { computed } from 'vue'
import { Plus, Search, ArrowRight, Folder, BookOpen, Info } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import type { PortfolioView } from '@/types/portfolio'
const props = defineProps<{ portfolio: PortfolioView; screen: string; sparse: boolean }>()
const skills = computed(() => [
    ...new Set(props.portfolio.projects.flatMap((p) => p.experiences.flatMap((e) => e.skills))),
])
const active = computed(() =>
    props.screen === 'profile' ? 'Profile' : props.screen === 'skills' ? 'Skills' : 'Projects',
)
</script>
<template>
    <div v-if="screen === 'login'" class="auth-layout">
        <a class="brand" href="/design/projects"><BookOpen /><strong>CareerAtlas</strong></a>
        <main id="main" class="auth-form">
            <h1>{{ t('Your work, connected.') }}</h1>
            <p class="muted">{{ t('Sign in to your personal workspace.') }}</p>
            <form class="form-stack" @submit.prevent>
                <div class="field">
                    <label for="email">{{ t('Email') }}</label
                    ><Input
                        id="email"
                        type="email"
                        autocomplete="email"
                        placeholder="you@example.com"
                    />
                </div>
                <div class="field">
                    <label for="password">{{ t('Password') }}</label
                    ><Input id="password" type="password" autocomplete="current-password" />
                </div>
                <Button type="submit">{{ t('Sign in') }}</Button
                ><a href="/design/login" class="text-action"
                    >{{ t('Create an account') }} <ArrowRight
                /></a>
            </form>
            <p class="info-line">
                <Info />{{ t('Your records stay private until you choose to publish.') }}
            </p>
        </main>
    </div>
    <WorkspaceShell v-else :active="active" preview>
        <div class="workspace-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <strong>{{ active }}</strong>
            </nav>
            <template v-if="screen === 'profile'">
                <PageHeading
                    :title="t('Your profile')"
                    :description="t('Introduce yourself. Choose what to share when you publish.')"
                    :status="t('Private')"
                />
                <form class="profile-form form-stack" @submit.prevent>
                    <div class="field">
                        <label for="display-name"
                            >{{ t('Display name') }} <span class="required">*</span></label
                        ><Input id="display-name" :model-value="sparse ? '' : portfolio.name" />
                    </div>
                    <div class="field">
                        <label for="direction"
                            >{{ t('Career direction') }}
                            <span class="optional">{{ t('(optional)') }}</span></label
                        ><Input id="direction" :model-value="sparse ? '' : portfolio.direction" />
                    </div>
                    <div class="field">
                        <label for="biography"
                            >{{ t('Bio') }}
                            <span class="optional">{{ t('(optional)') }}</span></label
                        ><Textarea
                            id="biography"
                            :model-value="sparse ? '' : portfolio.bio"
                            :rows="4"
                        />
                    </div>
                    <div class="field">
                        <label for="language">{{ t('Preferred language') }}</label
                        ><select id="language">
                            <option>{{ t('English') }}</option>
                            <option>日本語</option>
                            <option>中文</option>
                        </select>
                    </div>
                    <section class="contact-section">
                        <h2>{{ t('Contact links') }}</h2>
                        <p class="muted">
                            {{ t('Optional. Included only when you select them for publication.') }}
                        </p>
                        <Button variant="outline"><Plus />{{ t('Add contact link') }}</Button>
                    </section>
                    <div class="actions">
                        <Button>{{ t('Save profile') }}</Button>
                    </div>
                </form>
            </template>
            <template v-else-if="screen === 'skills'">
                <PageHeading
                    :title="t('Skills connected to your work')"
                    :description="
                        t('Follow each skill back to the experiences and evidence behind it.')
                    "
                />
                <div class="list-toolbar">
                    <div class="search-field">
                        <Search /><Input
                            :aria-label="t('Search skills')"
                            :placeholder="t('Search skills')"
                        />
                    </div>
                    <Button><Plus />{{ t('Add skill') }}</Button>
                </div>
                <div v-if="sparse" class="empty-state">
                    <h2>{{ t('No skills yet') }}</h2>
                    <p>{{ t('Add a skill or connect one while recording an experience.') }}</p>
                    <Button variant="outline">Add your first skill</Button>
                </div>
                <ul v-else class="record-list">
                    <li v-for="skill in skills" :key="skill">
                        <div>
                            <h2>{{ skill }}</h2>
                            <p>
                                {{ portfolio.projects.length }}
                                {{ portfolio.projects.length === 1 ? 'project' : 'projects' }} ·
                                {{ portfolio.projects.length }}
                                {{ portfolio.projects.length === 1 ? 'experience' : 'experiences' }}
                            </p>
                        </div>
                        <a href="/design/editor" :aria-label="`View ${skill} experiences`"
                            ><ArrowRight
                        /></a>
                    </li>
                </ul>
            </template>
            <template v-else>
                <PageHeading
                    :title="t('Your projects')"
                    :description="
                        t('Keep project context, your contribution and evidence together.')
                    "
                />
                <div class="list-toolbar">
                    <div class="search-field">
                        <Search /><Input
                            :aria-label="t('Search projects')"
                            :placeholder="t('Search projects')"
                        />
                    </div>
                    <select :aria-label="t('Project type')">
                        <option>{{ t('All project types') }}</option>
                        <option>{{ t('Commercial') }}</option>
                        <option>{{ t('Personal') }}</option>
                        <option>{{ t('Learning') }}</option></select
                    ><select :aria-label="t('Project status')">
                        <option>{{ t('Active') }}</option>
                        <option>{{ t('Archived') }}</option></select
                    ><Button><Plus />{{ t('Create project') }}</Button>
                </div>
                <div v-if="sparse" class="empty-state">
                    <Folder />
                    <h2>{{ t('Start with a project') }}</h2>
                    <p>
                        {{
                            t(
                                'A personal or learning project is enough. You do not need a complete résumé.',
                            )
                        }}
                    </p>
                    <Button>{{ t('Create your first project') }}</Button>
                </div>
                <ul v-else class="record-list">
                    <li v-for="project in portfolio.projects" :key="project.id">
                        <div>
                            <a href="/design/editor"
                                ><h2>{{ project.title }}</h2></a
                            >
                            <p>{{ project.type }} · {{ project.role }}</p>
                            <p class="record-summary">{{ project.summary }}</p>
                            <small>{{ project.experiences.length }} experience · Private</small>
                        </div>
                        <a href="/design/editor" :aria-label="`Open ${project.title}`"
                            ><ArrowRight
                        /></a>
                    </li>
                </ul>
            </template>
            <p class="fictional-note list-fictional">Fictional demo data</p>
        </div>
    </WorkspaceShell>
</template>
