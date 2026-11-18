<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { t } from '@/i18n'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import LoadState from '@/components/LoadState.vue'
import FormFeedback from '@/components/FormFeedback.vue'
import TargetLanguage from '@/components/TargetLanguage.vue'
import PublicationSelection from '@/components/PublicationSelection.vue'
import PortfolioContent from '@/components/PortfolioContent.vue'
import SourceFreshness from '@/components/SourceFreshness.vue'
import OwnerAssistant from '@/components/OwnerAssistant.vue'
import { op, ApiError } from '@/lib/api'
import { sourceChoices, downloadMarkdown } from '@/lib/p1'
import { portfolioView } from '@/lib/portfolio'
import { currentUser } from '@/lib/session'
import { useAction, useDraftGuard } from '@/composables/useFormState'
import { confirmAction } from '@/composables/useConfirm'
import type { Experience, Preview, Selection } from '@/types/domain'
import type { ExportDocument, Opportunity, OwnerApplication } from '@/types/p1'
const route = useRoute(),
    router = useRouter(),
    row = ref<Opportunity | null>(null),
    rows = ref<Opportunity[]>([]),
    records = ref<Experience[]>([])
const form = reactive({
    title: '',
    direction: '',
    requirements: '',
    deadline: '',
    target_language: 'en',
    introduction: '',
    include_introduction: false,
    selection: {
        profile_fields: ['display_name', 'direction', 'biography'],
        items: [],
    } as Selection,
})
const loading = useAction(),
    action = useAction(),
    guard = useDraftGuard(() => form),
    preview = ref<Preview | null>(null),
    exported = ref<ExportDocument | null>(null),
    reviewed = ref(false)
const view = computed(() => (preview.value ? portfolioView(preview.value.portfolio) : null))
let publicationKey = ''
watch(
    form,
    () => {
        preview.value = null
        publicationKey = ''
    },
    { deep: true },
)
function apply(value: Opportunity) {
    row.value = value
    Object.assign(form, {
        title: value.title,
        direction: value.direction,
        requirements: value.requirements,
        deadline: value.deadline || '',
        target_language: value.target_language,
        introduction: value.introduction,
        include_introduction: value.include_introduction,
        selection: JSON.parse(JSON.stringify(value.selection)),
    })
    guard.markSaved()
}
async function load() {
    const data = await loading.run(async () => ({
        records: await sourceChoices(),
        rows: await op<Opportunity[]>('list_opportunities'),
        row: route.params.id
            ? await op<Opportunity>('get_opportunity', { id: route.params.id })
            : null,
    }))
    if (data) {
        records.value = data.records
        rows.value = data.rows
        if (data.row) apply(data.row)
        else guard.markSaved()
    }
}
async function save() {
    const input = { ...form, deadline: form.deadline || null }
    const value = await action.run(
        () =>
            op<Opportunity>(
                row.value ? 'update_opportunity' : 'create_opportunity',
                row.value
                    ? { ...input, id: row.value.id, expected_version: row.value.version }
                    : input,
            ),
        'Saved',
    )
    if (value) {
        apply(value)
        exported.value = null
        if (!route.params.id) await router.replace(`/opportunities/${value.id}`)
    }
}
async function introduction() {
    const value = await action.run(
        () =>
            op<Opportunity>('confirm_introduction', {
                id: row.value!.id,
                expected_version: row.value!.version,
            }),
        'Saved',
    )
    if (value) apply(value)
}
async function review() {
    const value = await action.run(() => op<Preview>('preview_opportunity', { id: row.value!.id }))
    if (value) {
        preview.value = value
        publicationKey = crypto.randomUUID()
    }
}
async function publish() {
    const value = await action.run(
        () =>
            op<Opportunity>('publish_opportunity', {
                id: row.value!.id,
                expected_version: row.value!.version,
                preview_digest: preview.value!.digest,
                idempotency_key: publicationKey,
            }),
        'Published',
    )
    if (value) {
        apply(value)
        preview.value = null
        exported.value = null
    }
}
async function lifecycle(name: string) {
    if (
        name === 'revoke' &&
        !(await confirmAction({
            title: 'Revoke this page?',
            message: 'The public page and its assistant will become unavailable.',
            confirm: 'Revoke',
            cancel: 'Cancel',
            destructive: true,
        }))
    )
        return
    const value = await action.run(() =>
        op<Opportunity>(`${name}_opportunity`, {
            id: row.value!.id,
            expected_version: row.value!.version,
        }),
    )
    if (value) {
        apply(value)
        preview.value = null
        exported.value = null
    }
}
async function exportFile(edit = false) {
    const value = await action.run(() =>
        op<ExportDocument>('export_opportunity', { id: row.value!.id }),
    )
    if (value) {
        exported.value = value
        reviewed.value = false
        if (!edit) downloadMarkdown(value.filename, value.markdown)
    }
}
async function downloadTranslated() {
    const value = await action.run(() =>
        op<ExportDocument>('export_opportunity', {
            id: row.value!.id,
            source_digest: exported.value!.source_digest,
            reviewed: reviewed.value,
            translations: exported.value!.fields.map((field) => ({
                key: field.key,
                text: field.text,
            })),
        }),
    )
    if (value) downloadMarkdown(value.filename, value.markdown)
}
onMounted(load)
async function appliedProposal(value: OwnerApplication) {
    if (value.output.translations) {
        await action.run(async () => {
            const document = await op<ExportDocument>('export_opportunity', { id: row.value!.id })
            if (document.source_digest !== value.export_digest)
                throw new ApiError(409, 'Review the current export before downloading.')
            const translations = new Map(
                value.output.translations!.map((field) => [field.key, field.text]),
            )
            document.fields.forEach((field) => {
                field.text = translations.get(field.key) ?? field.text
            })
            exported.value = document
            reviewed.value = false
        })
        return
    }
    if (value.output.mapping) {
        const ids = new Set(value.output.mapping.flatMap((mapping) => mapping.source_ids))
        form.selection.items = form.selection.items.filter((item) => ids.has(item.experience_id))
        return
    }
    apply(value.subject as Opportunity)
}
</script>
<template>
    <WorkspaceShell active="Opportunities" :name="currentUser?.name"
        ><div class="workspace-page p1-page">
            <PageHeading
                :title="t('Shape a portfolio for this opportunity')"
                :description="t('Each opportunity has its own reviewed public snapshot.')"
                :status="t('Private brief')"
            />
            <LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><div class="p1-grid">
                    <div>
                        <section class="p1-card">
                            <h2>{{ t('Opportunity brief') }}</h2>
                            <FormFeedback
                                :error="action.error.value"
                                :message="action.message.value"
                            /><SourceFreshness :changes="row?.source_changes" />
                            <form @submit.prevent="save">
                                <fieldset :disabled="action.busy.value">
                                    <label
                                        >{{ t('Title')
                                        }}<input
                                            v-model="form.title"
                                            required
                                            name="title"
                                            maxlength="200" /></label
                                    ><label
                                        >{{ t('Direction')
                                        }}<input
                                            v-model="form.direction"
                                            required
                                            name="direction"
                                            maxlength="200" /></label
                                    ><label
                                        >{{ t('Private requirements')
                                        }}<textarea v-model="form.requirements" rows="4" /></label
                                    ><label
                                        >{{ t('Deadline (optional)')
                                        }}<input v-model="form.deadline" type="date" /></label
                                    ><TargetLanguage v-model="form.target_language" />
                                    <h2>{{ t('Selected experiences') }}</h2>
                                    <PublicationSelection
                                        v-model="form.selection"
                                        :records="records"
                                    /><label
                                        >{{ t('Introduction')
                                        }}<textarea v-model="form.introduction" rows="5" /></label
                                    ><label class="p1-check"
                                        ><input
                                            v-model="form.include_introduction"
                                            type="checkbox"
                                        />{{
                                            t('Include reviewed introduction in public page')
                                        }}</label
                                    ><button type="submit" class="p1-primary">
                                        {{ t('Save opportunity') }}
                                    </button>
                                </fieldset>
                            </form>
                            <OwnerAssistant
                                v-if="row"
                                :subject="row"
                                purpose="mapping"
                                kind="opportunity"
                                :disabled="action.busy.value || guard.dirty.value"
                                @applied="appliedProposal"
                            />
                            <OwnerAssistant
                                v-if="row"
                                :subject="row"
                                purpose="introduction"
                                kind="opportunity"
                                :disabled="action.busy.value || guard.dirty.value"
                                @applied="appliedProposal"
                            />
                            <template v-if="row"
                                ><button
                                    :disabled="
                                        action.busy.value ||
                                        guard.dirty.value ||
                                        !form.introduction.trim()
                                    "
                                    @click="introduction"
                                >
                                    {{ t('Confirm introduction') }}
                                </button>
                                <p v-if="row.introduction_confirmed">
                                    {{ t('Introduction confirmed') }}
                                </p>
                                <button
                                    :disabled="
                                        action.busy.value ||
                                        guard.dirty.value ||
                                        !form.selection.items.length
                                    "
                                    @click="review"
                                >
                                    {{ t('Review public preview') }}</button
                                ><button
                                    :disabled="action.busy.value || guard.dirty.value"
                                    @click="lifecycle(row.archived_at ? 'restore' : 'archive')"
                                >
                                    {{
                                        row.archived_at
                                            ? t('Restore opportunity')
                                            : t('Archive opportunity')
                                    }}
                                </button></template
                            >
                        </section>
                        <section v-if="view && preview && row" class="p1-card">
                            <h2>{{ t('Public preview') }}</h2>
                            <div data-testid="opportunity-preview">
                                <PortfolioContent
                                    :portfolio="view"
                                    compact
                                    complete
                                    :show-project-link="false"
                                />
                            </div>
                            <button
                                class="p1-primary"
                                :disabled="action.busy.value || !!row.archived_at"
                                @click="publish"
                            >
                                {{ t('Publish this opportunity') }}
                            </button>
                            <p>{{ t('Publishing replaces only this opportunity page.') }}</p>
                        </section>
                        <section v-if="row?.publication?.state === 'published'" class="p1-card">
                            <h2>{{ t('Published page') }}</h2>
                            <a
                                class="text-action"
                                :href="`/p/${row.publication.slug}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                >{{ t('Open public page') }}</a
                            >
                            <p>
                                {{
                                    t(
                                        'Exports use the current published snapshot and exclude private brief fields.',
                                    )
                                }}
                            </p>
                            <button :disabled="action.busy.value" @click="exportFile()">
                                {{ t('Download Markdown') }}</button
                            ><button :disabled="action.busy.value" @click="exportFile(true)">
                                {{ t('Review export translation') }}</button
                            ><button
                                :disabled="action.busy.value || guard.dirty.value"
                                @click="lifecycle('revoke')"
                            >
                                {{ t('Revoke this page') }}
                            </button>
                            <OwnerAssistant
                                :subject="row"
                                purpose="export"
                                kind="opportunity"
                                :disabled="action.busy.value || guard.dirty.value"
                                @applied="appliedProposal"
                            />
                            <template v-if="exported"
                                ><p>
                                    {{
                                        t(
                                            'Original wording is preserved until you review translated fields.',
                                        )
                                    }}
                                </p>
                                <label v-for="field in exported.fields" :key="field.key"
                                    >{{ t(field.label)
                                    }}<textarea
                                        v-model="field.text"
                                        rows="3"
                                        @input="reviewed = false"
                                    /></label
                                ><label class="p1-check"
                                    ><input v-model="reviewed" type="checkbox" />{{
                                        t('I reviewed the translated fields against the sources.')
                                    }}</label
                                ><button
                                    :disabled="action.busy.value || !reviewed"
                                    @click="downloadTranslated"
                                >
                                    {{ t('Download reviewed translation') }}
                                </button></template
                            >
                        </section>
                    </div>
                    <aside class="p1-card p1-context">
                        <h2>{{ t('Saved opportunities') }}</h2>
                        <RouterLink class="text-action" to="/opportunities">{{
                            t('New opportunity')
                        }}</RouterLink>
                        <ul>
                            <li v-for="item in rows" :key="item.id">
                                <RouterLink :to="`/opportunities/${item.id}`">{{
                                    item.title
                                }}</RouterLink
                                ><span v-if="item.archived_at"> · {{ t('Archived') }}</span>
                            </li>
                        </ul>
                        <p>
                            {{
                                t(
                                    'Private requirements and deadlines are never included in the public page.',
                                )
                            }}
                        </p>
                    </aside>
                </div></LoadState
            >
        </div></WorkspaceShell
    >
</template>
