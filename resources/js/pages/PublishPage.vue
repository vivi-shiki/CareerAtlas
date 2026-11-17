<script setup lang="ts">
import { t, formatDate } from '@/i18n'
import { computed, nextTick, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Check, Eye, Info, Plus } from 'lucide-vue-next'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import LoadState from '@/components/LoadState.vue'
import FormFeedback from '@/components/FormFeedback.vue'
import PortfolioContent from '@/components/PortfolioContent.vue'
import { Button } from '@/components/ui/button'
import { useAction } from '@/composables/useFormState'
import { confirmAction } from '@/composables/useConfirm'
import { op } from '@/lib/api'
import { currentUser } from '@/lib/session'
import { portfolioView } from '@/lib/portfolio'
import type {
    Experience,
    Project,
    PageResult,
    Publication,
    Selection,
    SelectionItem,
    Preview,
} from '@/types/domain'

const publications = ref<Publication[]>([]),
    experiences = ref<Experience[]>([]),
    projects = ref<Record<string, Project>>({})
const phase = ref<'list' | 'selection' | 'preview'>('selection'),
    editing = ref<Publication | null>(null)
const profileFields = ref(['display_name', 'direction', 'biography']),
    selected = ref<SelectionItem[]>([])
const preview = ref<Preview | null>(null),
    reviewed = ref<Selection | null>(null)
const loading = useAction(),
    action = useAction()
let requestKey = ''
const profileOptions = [
    { key: 'display_name', label: 'Display name' },
    { key: 'direction', label: 'Career direction' },
    { key: 'biography', label: 'Bio' },
    { key: 'preferred_language', label: 'Preferred language' },
    { key: 'contact_links', label: 'Contact links' },
]
const experienceOptions = [
    { key: 'title', label: 'Experience title' },
    { key: 'record_type', label: 'Record type' },
    { key: 'occurrence', label: 'Occurrence date' },
    { key: 'personal_action', label: 'My contribution' },
    { key: 'result', label: 'Result' },
    { key: 'validation', label: 'Validation' },
    { key: 'remaining_work', label: 'Remaining work' },
    { key: 'skills', label: 'Skills' },
    { key: 'evidence', label: 'Evidence labels and links' },
]
const projectOptions = [
    { key: 'title', label: 'Project title' },
    { key: 'type', label: 'Project type' },
    { key: 'role', label: 'Role' },
    { key: 'background', label: 'Background' },
    { key: 'team_outcome', label: 'Team outcome' },
    { key: 'occurrence', label: 'Project dates' },
]
const selectedIds = computed(() => selected.value.map((item) => item.experience_id))
const view = computed(() => (preview.value ? portfolioView(preview.value.portfolio) : null))

async function allExperiences() {
    const records: Experience[] = []
    let page = 1
    while (true) {
        const result = await op<PageResult<Experience>>('list_experiences', { page, per_page: 100 })
        records.push(...result.items)
        if (records.length >= result.total) break
        page++
    }
    return records.filter((record) => record.status === 'confirmed')
}
async function load() {
    const result = await loading.run(async () => {
        const [publications, experiences] = await Promise.all([
            op<Publication[]>('list_publications'),
            allExperiences(),
        ])
        const projectIds = [...new Set(experiences.map((experience) => experience.project_id))]
        const projects = await Promise.all(
            projectIds.map((id) => op<Project>('get_project', { id })),
        )
        return { publications, experiences, projects }
    })
    if (result) {
        publications.value = result.publications
        experiences.value = result.experiences
        projects.value = Object.fromEntries(result.projects.map((project) => [project.id, project]))
        phase.value = result.publications.length ? 'list' : 'selection'
    }
}
function choose(experience: Experience, checked: boolean) {
    if (checked && selected.value.length < 100)
        selected.value.push({
            experience_id: experience.id,
            fields: ['title', 'record_type', 'occurrence', 'personal_action', 'skills', 'evidence'],
            project_fields: ['title', 'type', 'role', 'background'],
        })
    else selected.value = selected.value.filter((item) => item.experience_id !== experience.id)
}
function start(publication?: Publication) {
    editing.value = publication || null
    preview.value = null
    reviewed.value = null
    action.error.value = null
    action.message.value = ''
    requestKey = ''
    if (publication) {
        profileFields.value = [...publication.selection.profile_fields]
        selected.value = JSON.parse(
            JSON.stringify(
                publication.selection.items.filter((item) =>
                    experiences.value.some((experience) => experience.id === item.experience_id),
                ),
            ),
        )
    } else {
        profileFields.value = ['display_name', 'direction', 'biography']
        selected.value = []
    }
    phase.value = 'selection'
}
async function review() {
    const input: Selection = {
        profile_fields: [...profileFields.value],
        items: JSON.parse(JSON.stringify(selected.value)),
        ...(editing.value ? { publication_id: editing.value.id } : {}),
    }
    const result = await action.run(() => op<Preview>('preview_publication', input))
    if (result) {
        preview.value = result
        reviewed.value = input
        requestKey = crypto.randomUUID()
        phase.value = 'preview'
        await nextTick()
        document.getElementById('preview-heading')?.focus()
    }
}
async function back() {
    phase.value = 'selection'
    action.error.value = null
    await nextTick()
    document.getElementById('selection-heading')?.focus()
}
async function publish() {
    if (!preview.value || !reviewed.value) return
    const input = {
        ...reviewed.value,
        preview_digest: preview.value.digest,
        idempotency_key: requestKey,
        ...(editing.value ? { expected_version: editing.value.version } : {}),
    }
    const result = await action.run(
        () =>
            op<Publication>(editing.value ? 'republish_publication' : 'publish_publication', input),
        'Published',
    )
    if (result) {
        publications.value = [result, ...publications.value.filter((item) => item.id !== result.id)]
        phase.value = 'list'
        preview.value = null
        reviewed.value = null
        editing.value = null
    }
}
async function revoke(publication: Publication) {
    if (
        !(await confirmAction({
            title: 'Revoke publication?',
            message:
                'Future online access to this page, its sources and its questions will stop. Copies already viewed cannot be recalled.',
            confirm: 'Revoke publication',
            destructive: true,
        }))
    )
        return
    const result = await action.run(
        () =>
            op<Publication>('revoke_publication', {
                id: publication.id,
                expected_version: publication.version,
            }),
        'Publication revoked.',
    )
    if (result)
        publications.value = publications.value.map((item) =>
            item.id === result.id ? result : item,
        )
}
onMounted(load)
</script>

<template>
    <WorkspaceShell active="Publish" :name="currentUser?.name"
        ><div class="workspace-page publication-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <strong>{{ t('Publish') }}</strong
                ><span>/</span><span>{{ t('Portfolio') }}</span>
            </nav>
            <LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><PageHeading
                    :title="
                        phase === 'list'
                            ? t('Your published portfolio')
                            : t('Review what becomes public')
                    "
                    :description="t('Only selected, confirmed content will be shared.')"
                    :status="
                        phase === 'list'
                            ? undefined
                            : editing
                              ? t('Reviewing changes')
                              : t('Not published')
                    "
                /><FormFeedback :error="action.error.value" :message="action.message.value" />
                <template v-if="phase === 'list'"
                    ><div class="actions">
                        <Button @click="start()"><Plus />{{ t('Create publication') }}</Button>
                    </div>
                    <ul class="record-list publication-list">
                        <li v-for="publication in publications" :key="publication.id">
                            <div>
                                <h2>
                                    {{
                                        publication.state === 'published'
                                            ? t('Published')
                                            : t('Revoked')
                                    }}
                                </h2>
                                <p>
                                    {{ t('Version') }} {{ publication.version }} ·
                                    {{ formatDate(publication.published_at) }}
                                </p>
                                <p v-if="publication.has_unpublished_changes" class="pending-state">
                                    {{ t('Unpublished changes') }}
                                </p>
                                <RouterLink
                                    v-if="publication.state === 'published'"
                                    :to="`/p/${publication.slug}`"
                                    class="text-action"
                                    >{{ t('Open public portfolio') }}</RouterLink
                                >
                            </div>
                            <div class="actions">
                                <Button
                                    variant="outline"
                                    :disabled="action.busy.value"
                                    @click="start(publication)"
                                    >{{ t('Review changes') }}</Button
                                ><Button
                                    v-if="publication.state === 'published'"
                                    variant="outline"
                                    :disabled="action.busy.value"
                                    @click="revoke(publication)"
                                    >{{ t('Revoke publication') }}</Button
                                >
                            </div>
                        </li>
                    </ul></template
                >
                <template v-else
                    ><ol class="publication-steps" :aria-label="t('Publication steps')">
                        <li
                            :class="{ complete: phase === 'preview' }"
                            :aria-current="phase === 'selection' ? 'step' : undefined"
                        >
                            <span class="step-circle"
                                ><Check v-if="phase === 'preview'" /><template v-else
                                    >1</template
                                ></span
                            ><span>{{ t('Select content') }}</span>
                        </li>
                        <li :aria-current="phase === 'preview' ? 'step' : undefined">
                            <span class="step-circle">2</span><strong>{{ t('Preview') }}</strong>
                        </li>
                        <li>
                            <span class="step-circle">3</span><span>{{ t('Publish') }}</span>
                        </li>
                    </ol>
                    <div v-if="!experiences.length" class="empty-state">
                        <h2>{{ t('Confirm an experience before publishing.') }}</h2>
                        <p>{{ t('Drafts stay private until you review and confirm them.') }}</p>
                        <RouterLink to="/projects" class="text-action">{{
                            t('Go to projects')
                        }}</RouterLink>
                    </div>
                    <div
                        v-else
                        class="publication-grid live-publication-grid"
                        :class="{
                            'mobile-review': phase === 'preview',
                            'selection-only': phase === 'selection',
                        }"
                    >
                        <section
                            class="publication-selection"
                            :class="{ 'selection-readonly': phase === 'preview' }"
                        >
                            <h2 id="selection-heading" tabindex="-1">{{ t('Public content') }}</h2>
                            <fieldset :disabled="phase === 'preview' || action.busy.value">
                                <legend class="sr-only">{{ t('Profile fields') }}</legend>
                                <label
                                    v-for="field in profileOptions.filter(
                                        (field) =>
                                            phase !== 'preview' ||
                                            profileFields.includes(field.key) ||
                                            field.key === 'contact_links',
                                    )"
                                    :key="field.key"
                                    class="check-row"
                                    ><input
                                        v-model="profileFields"
                                        type="checkbox"
                                        :value="field.key"
                                    />{{ t(field.label) }}</label
                                >
                            </fieldset>
                            <div class="selection-records">
                                <div
                                    v-for="experience in experiences.filter(
                                        (experience) =>
                                            phase !== 'preview' ||
                                            selectedIds.includes(experience.id),
                                    )"
                                    :key="experience.id"
                                    class="selection-record"
                                >
                                    <label class="check-row"
                                        ><input
                                            type="checkbox"
                                            :aria-label="
                                                t('Include {title}', { title: experience.title })
                                            "
                                            :checked="selectedIds.includes(experience.id)"
                                            :disabled="
                                                phase === 'preview' ||
                                                action.busy.value ||
                                                (!selectedIds.includes(experience.id) &&
                                                    selected.length >= 100)
                                            "
                                            @change="
                                                choose(
                                                    experience,
                                                    ($event.target as HTMLInputElement).checked,
                                                )
                                            "
                                        /><strong>{{ experience.title }}</strong></label
                                    >
                                    <p class="selection-project-label">
                                        {{ projects[experience.project_id]?.title }}
                                    </p>
                                    <template
                                        v-for="item in selected.filter(
                                            (item) => item.experience_id === experience.id,
                                        )"
                                        :key="item.experience_id"
                                        ><fieldset
                                            class="selection-tree"
                                            :disabled="phase === 'preview' || action.busy.value"
                                        >
                                            <legend>{{ t('Experience fields') }}</legend>
                                            <label
                                                v-for="field in experienceOptions.filter(
                                                    (field) =>
                                                        phase !== 'preview' ||
                                                        item.fields.includes(field.key),
                                                )"
                                                :key="field.key"
                                                class="check-row"
                                                ><input
                                                    v-model="item.fields"
                                                    type="checkbox"
                                                    :value="field.key"
                                                />{{ t(field.label) }}</label
                                            >
                                        </fieldset>
                                        <fieldset
                                            v-if="phase === 'selection'"
                                            class="selection-tree"
                                            :disabled="action.busy.value"
                                        >
                                            <legend>{{ t('Project context') }}</legend>
                                            <label
                                                v-for="field in projectOptions"
                                                :key="field.key"
                                                class="check-row"
                                                ><input
                                                    v-model="item.project_fields"
                                                    type="checkbox"
                                                    :value="field.key"
                                                />{{ t(field.label) }}</label
                                            >
                                        </fieldset>
                                        <p
                                            v-else-if="item.project_fields.length"
                                            class="selected-context"
                                        >
                                            <strong>{{ t('Selected project context') }}</strong
                                            >{{
                                                projectOptions
                                                    .filter((field) =>
                                                        item.project_fields.includes(field.key),
                                                    )
                                                    .map((field) => t(field.label))
                                                    .join(' · ')
                                            }}
                                        </p></template
                                    >
                                </div>
                            </div>
                            <div class="selection-summary">
                                <strong>{{
                                    t(
                                        selected.length === 1
                                            ? '{count} confirmed experience selected'
                                            : '{count} confirmed experiences selected',
                                        { count: selected.length },
                                    )
                                }}</strong>
                                <p class="info-line">
                                    <Info />{{ t('Private notes are never included.') }}
                                </p>
                            </div>
                            <Button
                                v-if="phase === 'selection'"
                                class="review-selected"
                                :disabled="!selected.length || action.busy.value"
                                @click="review"
                                >{{ t('Review selected content') }}</Button
                            >
                        </section>
                        <section v-if="phase === 'preview' && view" class="visitor-preview">
                            <header>
                                <h2 id="preview-heading" tabindex="-1">
                                    {{ t('Visitor preview') }}
                                </h2>
                                <span class="preview-link"><Eye />{{ t('Public view') }}</span>
                            </header>
                            <div class="preview-frame">
                                <PortfolioContent :portfolio="view" compact complete />
                            </div>
                        </section>
                        <aside v-else class="selection-guidance">
                            <h2>{{ t('Choose your public story') }}</h2>
                            <p>
                                {{
                                    t(
                                        'Choose confirmed experiences and the exact fields you want visitors to see. Review the full visitor view before publishing.',
                                    )
                                }}
                            </p>
                            <p class="privacy-notice">
                                <Info />{{
                                    t(
                                        'Nothing becomes public until you publish the reviewed snapshot.',
                                    )
                                }}
                            </p>
                        </aside>
                    </div>
                    <footer v-if="phase === 'preview'" class="publication-footer mobile-review">
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
                            <Button variant="outline" :disabled="action.busy.value" @click="back">{{
                                t('Back to selection')
                            }}</Button
                            ><Button :disabled="action.busy.value" @click="publish">{{
                                t('Publish snapshot')
                            }}</Button>
                        </div>
                    </footer>
                </template>
            </LoadState>
        </div></WorkspaceShell
    >
</template>
