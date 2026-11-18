<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { t } from '@/i18n'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import LoadState from '@/components/LoadState.vue'
import FormFeedback from '@/components/FormFeedback.vue'
import TargetLanguage from '@/components/TargetLanguage.vue'
import OccurrenceFields from '@/components/OccurrenceFields.vue'
import OwnerAssistant from '@/components/OwnerAssistant.vue'
import { op } from '@/lib/api'
import { projectChoices } from '@/lib/p1'
import { currentUser } from '@/lib/session'
import { useAction, useDraftGuard } from '@/composables/useFormState'
import { confirmAction } from '@/composables/useConfirm'
import type { Project, Experience } from '@/types/domain'
import type { Capture, CaptureDraft, OwnerApplication } from '@/types/p1'
const route = useRoute(),
    router = useRouter(),
    row = ref<Capture | null>(null),
    projects = ref<Project[]>([]),
    captures = ref<Capture[]>([])
const form = reactive({
    project_id: '',
    notes: '',
    target_language: 'en',
    draft: {
        title: '',
        personal_action: '',
        result: '',
        validation: '',
        remaining_work: '',
        record_type: null,
        occurrence: null,
    } as CaptureDraft,
})
const skills = ref<string[]>([]),
    candidates = ref<{ id: string; title: string; version: number; reason: string }[]>([])
const loading = useAction(),
    action = useAction(),
    guard = useDraftGuard(() => form)
function apply(value: Capture) {
    row.value = value
    Object.assign(form, {
        project_id: value.project_id,
        notes: value.notes,
        target_language: value.target_language,
        draft: { ...structuredClone(value.draft) },
    })
    guard.markSaved()
}
function appliedProposal(value: OwnerApplication) {
    apply(value.subject as Capture)
    skills.value = []
    candidates.value = []
}
async function load() {
    const data = await loading.run(async () => ({
        projects: await projectChoices(),
        captures: await op<Capture[]>('list_captures'),
        row: route.params.id ? await op<Capture>('get_capture', { id: route.params.id }) : null,
    }))
    if (data) {
        projects.value = data.projects
        captures.value = data.captures
        if (data.row) apply(data.row)
        else guard.markSaved()
    }
}
async function save() {
    const input = { notes: form.notes, target_language: form.target_language, draft: form.draft }
    const saved = await action.run(
        () =>
            op<Capture>(
                row.value ? 'update_capture' : 'create_capture',
                row.value
                    ? { ...input, id: row.value.id, expected_version: row.value.version }
                    : { ...input, project_id: form.project_id },
            ),
        'Saved',
    )
    if (saved) {
        apply(saved)
        candidates.value = []
        if (!route.params.id) await router.replace(`/capture/${saved.id}`)
    }
}
async function duplicates() {
    const data = await action.run(() =>
        op<typeof candidates.value>('find_capture_duplicates', { id: row.value!.id }),
    )
    if (data) candidates.value = data
}
async function applyDraft(candidate?: { id: string; version: number }) {
    if (
        candidate &&
        !(await confirmAction({
            title: 'Merge into this experience?',
            message:
                'This saves a new private draft. Blank suggestions do not erase existing facts.',
            confirm: 'Merge',
            cancel: 'Cancel',
        }))
    )
        return
    const result = await action.run(() =>
        op<Experience>('apply_capture', {
            id: row.value!.id,
            expected_version: row.value!.version,
            skill_names: skills.value,
            ...(candidate ? { merge_id: candidate.id, merge_version: candidate.version } : {}),
        }),
    )
    if (result) {
        row.value!.applied_experience_id = result.id
        guard.markSaved()
    }
}
onMounted(load)
</script>
<template>
    <WorkspaceShell active="Capture" :name="currentUser?.name"
        ><div class="workspace-page p1-page">
            <PageHeading
                :title="t('Turn notes into an experience')"
                :description="
                    t('Missing facts remain blank. Review suggestions before adding them.')
                "
                :status="t('Private draft')"
            />
            <LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><div class="p1-grid">
                    <section class="p1-card">
                        <OwnerAssistant
                            v-if="row && !row.applied_experience_id"
                            :subject="row"
                            purpose="capture"
                            kind="capture"
                            :disabled="action.busy.value || guard.dirty.value"
                            @applied="appliedProposal"
                        />
                        <div v-if="row?.questions.length" class="p1-source">
                            <h3>{{ t('Optional follow-up questions') }}</h3>
                            <p v-for="question in row.questions" :key="question">{{ question }}</p>
                            <p>
                                {{
                                    t(
                                        'Add missing facts to your notes or source records, save, then request another draft.',
                                    )
                                }}
                            </p>
                        </div>
                        <FormFeedback :error="action.error.value" :message="action.message.value" />
                        <form @submit.prevent="save">
                            <fieldset :disabled="action.busy.value || !!row?.applied_experience_id">
                                <label
                                    >{{ t('Project')
                                    }}<select
                                        v-model="form.project_id"
                                        required
                                        :disabled="!!row"
                                        name="project_id"
                                    >
                                        <option value="" disabled>
                                            {{ t('Choose a project') }}
                                        </option>
                                        <option
                                            v-for="project in projects"
                                            :key="project.id"
                                            :value="project.id"
                                        >
                                            {{ project.title }}
                                        </option>
                                    </select></label
                                >
                                <label
                                    >{{ t('Notes')
                                    }}<textarea
                                        v-model="form.notes"
                                        name="notes"
                                        required
                                        maxlength="16000"
                                        rows="6"
                                    />
                                </label>
                                <TargetLanguage v-model="form.target_language" />
                                <h2>{{ t('Review the draft') }}</h2>
                                <label
                                    >{{ t('Title')
                                    }}<input
                                        v-model="form.draft.title"
                                        name="title"
                                        maxlength="200"
                                /></label>
                                <label
                                    >{{ t('Record type')
                                    }}<select v-model="form.draft.record_type">
                                        <option :value="null">{{ t('Not specified') }}</option>
                                        <option value="work_contribution">
                                            {{ t('Work contribution') }}
                                        </option>
                                        <option value="learning">{{ t('Learning') }}</option>
                                        <option value="problem_solving">
                                            {{ t('Problem solving') }}
                                        </option>
                                    </select></label
                                >
                                <OccurrenceFields
                                    :model-value="form.draft.occurrence || null"
                                    @update:model-value="form.draft.occurrence = $event"
                                />
                                <label
                                    >{{ t('Personal action')
                                    }}<textarea v-model="form.draft.personal_action" rows="4" />
                                </label>
                                <label
                                    >{{ t('Result')
                                    }}<textarea v-model="form.draft.result" rows="2" />
                                </label>
                                <label
                                    >{{ t('Validation')
                                    }}<textarea v-model="form.draft.validation" rows="2" />
                                </label>
                                <label
                                    >{{ t('Remaining work')
                                    }}<textarea v-model="form.draft.remaining_work" rows="2" />
                                </label>
                                <button class="p1-primary" type="submit">
                                    {{ t('Save notes') }}
                                </button>
                            </fieldset>
                        </form>
                        <template v-if="row && !row.applied_experience_id"
                            ><h3>{{ t('Choose skills to add') }}</h3>
                            <p v-if="!row.draft.skill_suggestions?.length">
                                {{ t('No skill suggestions yet.') }}
                            </p>
                            <label
                                v-for="skill in row.draft.skill_suggestions"
                                :key="skill"
                                class="p1-check"
                                ><input v-model="skills" type="checkbox" :value="skill" />{{
                                    skill
                                }}</label
                            >
                            <button
                                :disabled="action.busy.value || guard.dirty.value"
                                @click="duplicates"
                            >
                                {{ t('Review duplicates') }}
                            </button>
                            <div
                                v-for="candidate in candidates"
                                :key="candidate.id"
                                class="p1-source"
                            >
                                <strong>{{ candidate.title }}</strong>
                                <p>{{ t(candidate.reason) }}</p>
                                <button
                                    :disabled="action.busy.value || guard.dirty.value"
                                    @click="applyDraft(candidate)"
                                >
                                    {{ t('Merge into this experience') }}
                                </button>
                            </div>
                            <button
                                :disabled="action.busy.value || guard.dirty.value"
                                @click="applyDraft()"
                            >
                                {{ t('Add as new experience') }}
                            </button>
                            <p>
                                {{ t('Applying creates a private draft. Confirm it separately.') }}
                            </p></template
                        >
                        <RouterLink
                            v-if="row?.applied_experience_id"
                            class="text-action"
                            :to="`/experiences/${row.applied_experience_id}`"
                            >{{ t('Open experience') }}</RouterLink
                        >
                    </section>
                    <aside class="p1-card p1-context">
                        <h2>{{ t('Saved captures') }}</h2>
                        <RouterLink class="text-action" to="/capture">{{
                            t('New capture')
                        }}</RouterLink>
                        <ul>
                            <li v-for="capture in captures" :key="capture.id">
                                <RouterLink :to="`/capture/${capture.id}`">{{
                                    capture.draft.title || capture.notes.slice(0, 80)
                                }}</RouterLink>
                            </li>
                        </ul>
                        <p>
                            {{
                                t(
                                    'Team outcomes and your personal actions are separate. AI suggestions need your review.',
                                )
                            }}
                        </p>
                    </aside>
                </div></LoadState
            >
        </div></WorkspaceShell
    >
</template>
