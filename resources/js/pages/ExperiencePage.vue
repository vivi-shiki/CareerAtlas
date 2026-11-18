<script setup lang="ts">
import { t } from '@/i18n'
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { Info, ChevronDown } from 'lucide-vue-next'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import LoadState from '@/components/LoadState.vue'
import FormFeedback from '@/components/FormFeedback.vue'
import OccurrenceFields from '@/components/OccurrenceFields.vue'
import EvidenceEditor from '@/components/EvidenceEditor.vue'
import ExperienceSkills from '@/components/ExperienceSkills.vue'
import RevisionHistory from '@/components/RevisionHistory.vue'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import { op, ApiError } from '@/lib/api'
import { currentUser } from '@/lib/session'
import { useAction, useDraftGuard } from '@/composables/useFormState'
import { confirmAction } from '@/composables/useConfirm'
import type { Experience, Project, Occurrence } from '@/types/domain'
const route = useRoute(),
    router = useRouter(),
    id = route.params.id as string | undefined
const record = ref<Experience | null>(null),
    project = ref<Project | null>(null),
    ready = ref(false),
    details = ref(false)
const loading = useAction(),
    saving = useAction()
const form = reactive({
    title: '',
    record_type: 'work_contribution',
    occurrence: null as Occurrence | null,
    personal_action: '',
    result: '',
    validation: '',
    remaining_work: '',
    private_notes: '',
})
const { dirty, markSaved, discard } = useDraftGuard(() => form)
const status = computed(() =>
    record.value?.archived_at
        ? 'Archived · Private'
        : record.value?.status === 'confirmed' && !dirty.value
          ? 'Confirmed · Private'
          : 'Draft · Private',
)
function apply(value: Experience) {
    record.value = value
    Object.assign(form, {
        title: value.title || '',
        record_type: value.record_type || 'work_contribution',
        occurrence: value.occurrence,
        personal_action: value.personal_action || '',
        result: value.result || '',
        validation: value.validation || '',
        remaining_work: value.remaining_work || '',
        private_notes: value.private_notes || '',
    })
    markSaved()
}
async function load() {
    const result = await loading.run(async () => {
        const experience = id ? await op<Experience>('get_experience', { id }) : null
        const project = await op<Project>('get_project', {
            id: experience?.project_id || route.params.projectId,
        })
        return { experience, project }
    })
    if (result) {
        project.value = result.project
        if (result.experience) apply(result.experience)
        else markSaved()
        ready.value = true
    }
}
async function persistDraft(): Promise<Experience> {
    if (record.value && !dirty.value) return record.value
    const input = { ...form, occurrence: form.occurrence?.start ? form.occurrence : null }
    const result = await op<Experience>(record.value ? 'update_experience' : 'create_experience', {
        ...input,
        ...(record.value
            ? { id: record.value.id, expected_version: record.value.version }
            : { project_id: project.value!.id }),
    })
    apply(result)
    return result
}
async function save(confirm = false) {
    const result = await saving.run(
        async () => {
            if (confirm) {
                const missing: Record<string, string[]> = {}
                if (!form.title.trim()) missing.title = ['Add a title.']
                if (!form.record_type) missing.record_type = ['Choose a record type.']
                if (!form.occurrence?.start) missing.occurrence = ['Add an occurrence date.']
                if (!form.personal_action.trim())
                    missing.personal_action = ['Describe your personal action in My contribution.']
                if (Object.keys(missing).length)
                    throw new ApiError(
                        422,
                        'Review the highlighted fields.',
                        'validation_error',
                        missing,
                    )
            }
            const saved = await persistDraft()
            if (!confirm) return saved
            return op<Experience>('confirm_experience', {
                id: saved.id,
                expected_version: saved.version,
            })
        },
        confirm ? 'Experience confirmed. It remains private.' : 'Draft saved.',
    )
    if (result) {
        apply(result)
        if (!id) await router.replace(`/experiences/${result.id}`)
    }
}
async function reloadSaved() {
    if (await discard()) {
        saving.error.value = null
        await load()
    }
}
async function lifecycle(action: 'archive' | 'restore' | 'delete' | 'revoke') {
    if (!record.value) return
    if (
        action === 'delete' &&
        !(await confirmAction({
            title: 'Delete experience?',
            message:
                'Dependent published content will become unavailable. This is different from archiving.',
            confirm: 'Delete experience',
            destructive: true,
        }))
    )
        return
    if (
        action === 'revoke' &&
        !(await confirmAction({
            title: 'Withdraw public access?',
            message:
                'Existing publications will stop showing this experience and its evidence. The private record is retained.',
            confirm: 'Withdraw access',
            destructive: true,
        }))
    )
        return
    if (dirty.value && !(await discard())) return
    const result = await saving.run(
        () =>
            op<Experience>(
                action === 'revoke' ? 'revoke_experience_access' : `${action}_experience`,
                { id: record.value!.id, expected_version: record.value!.version },
            ),
        action === 'revoke'
            ? 'Public access withdrawn.'
            : action === 'restore'
              ? 'Experience restored.'
              : 'Experience archived.',
    )
    if (result) {
        markSaved()
        if (action === 'delete') await router.push(`/projects/${project.value!.id}`)
        else apply(result)
    }
}
onMounted(load)
</script>
<template>
    <WorkspaceShell :name="currentUser?.name"
        ><div class="workspace-page editor-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <RouterLink to="/projects">{{ t('Projects') }}</RouterLink
                ><span>/</span
                ><RouterLink v-if="project" :to="`/projects/${project.id}`">{{
                    project.title
                }}</RouterLink
                ><span>/</span><strong>{{ t('Experience') }}</strong>
            </nav>
            <LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><template v-if="ready && project"
                    ><PageHeading
                        :title="t('Capture your contribution')"
                        :description="t('Keep the work, your actions and the evidence connected.')"
                        :status="t(status)" /><FormFeedback
                        :error="saving.error.value"
                        :message="saving.message.value"
                        ><Button
                            v-if="saving.error.value?.status === 409"
                            variant="outline"
                            @click="reloadSaved"
                            >{{ t('Reload saved version') }}</Button
                        ></FormFeedback
                    >
                    <fieldset class="form-fields" :disabled="saving.busy.value">
                        <div class="editor-grid">
                            <section class="editor-fields" :aria-label="t('Experience')">
                                <div class="field">
                                    <label for="title"
                                        >{{ t('Title') }}
                                        <span class="required" aria-hidden="true">*</span></label
                                    ><Input
                                        id="title"
                                        v-model="form.title"
                                        name="title"
                                        :aria-label="t('Title')"
                                        :aria-invalid="!!saving.error.value?.fields.title"
                                        :placeholder="t('What did you work on?')"
                                    />
                                </div>
                                <div class="field-row">
                                    <div class="field">
                                        <label for="project">{{ t('Project') }}</label
                                        ><Input
                                            id="project"
                                            :model-value="project.title"
                                            readonly
                                        />
                                    </div>
                                    <div class="field">
                                        <label for="record-type"
                                            >{{ t('Record type') }}
                                            <span class="required" aria-hidden="true"
                                                >*</span
                                            ></label
                                        ><select
                                            id="record-type"
                                            v-model="form.record_type"
                                            name="record_type"
                                            :aria-label="t('Record type')"
                                        >
                                            <option value="work_contribution">
                                                {{ t('Work contribution') }}
                                            </option>
                                            <option value="learning">{{ t('Learning') }}</option>
                                            <option value="problem_solving">
                                                {{ t('Problem solving') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="field-row">
                                    <div class="field">
                                        <label for="role">{{ t('Role') }}</label
                                        ><Input id="role" :model-value="project.role" readonly />
                                    </div>
                                    <OccurrenceFields v-model="form.occurrence" />
                                </div>
                                <div class="editor-divider"></div>
                                <div class="field">
                                    <label for="personal-action"
                                        >{{ t('My contribution') }}
                                        <span class="required" aria-hidden="true">*</span></label
                                    ><Textarea
                                        id="personal-action"
                                        v-model="form.personal_action"
                                        name="personal_action"
                                        :aria-label="t('My contribution')"
                                        :aria-invalid="!!saving.error.value?.fields.personal_action"
                                        :rows="3"
                                        :placeholder="t('Describe your own actions.')"
                                    />
                                </div>
                                <div class="field">
                                    <label for="team-outcome"
                                        >{{ t('Team outcome') }}
                                        <span class="optional">{{
                                            t('(project context)')
                                        }}</span></label
                                    ><Textarea
                                        id="team-outcome"
                                        :model-value="project.team_outcome || ''"
                                        readonly
                                        :rows="2"
                                    /><RouterLink
                                        :to="`/projects/${project.id}/edit`"
                                        class="context-link"
                                        >{{ t('Edit project context') }}</RouterLink
                                    >
                                </div>
                                <details class="background-details">
                                    <summary>
                                        <ChevronDown /><span
                                            >{{ t('Background') }}
                                            <span class="optional">{{
                                                t('(project context)')
                                            }}</span></span
                                        >
                                    </summary>
                                    <p class="prose-value">
                                        {{
                                            project.background ||
                                            t('No project background recorded.')
                                        }}
                                    </p>
                                </details>
                                <p class="info-line">
                                    <Info />{{ t('A measurable result is optional.') }}
                                </p>
                                <ExperienceSkills
                                    :record="record"
                                    :prepare="persistDraft"
                                    @saved="apply"
                                /><Button
                                    type="button"
                                    variant="ghost"
                                    class="details-toggle"
                                    :aria-expanded="details"
                                    @click="details = !details"
                                    ><ChevronDown />{{ t('Additional details') }}</Button
                                >
                                <div v-if="details" class="form-stack">
                                    <div
                                        v-for="field in [
                                            { key: 'result', label: t('Result') },
                                            { key: 'validation', label: t('Validation') },
                                            { key: 'remaining_work', label: t('Remaining work') },
                                            { key: 'private_notes', label: t('Private notes') },
                                        ] as const"
                                        :key="field.key"
                                        class="field"
                                    >
                                        <label :for="field.key">{{ field.label }}</label
                                        ><Textarea
                                            :id="field.key"
                                            v-model="form[field.key]"
                                            :name="field.key"
                                            :rows="3"
                                        /><small
                                            v-if="field.key === 'private_notes'"
                                            class="muted"
                                            >{{ t('Never included in publications.') }}</small
                                        >
                                    </div>
                                </div>
                            </section>
                            <EvidenceEditor
                                :record="record"
                                :prepare="persistDraft"
                                @saved="apply"
                            />
                        </div></fieldset></template
            ></LoadState>
        </div>
        <footer v-if="ready" class="editor-footer">
            <p>
                {{
                    dirty
                        ? t('Unsaved changes')
                        : record
                          ? t('Changes saved')
                          : t('New private draft')
                }}
            </p>
            <div class="actions">
                <Button
                    variant="outline"
                    :disabled="saving.busy.value || !!record?.archived_at"
                    @click="save(false)"
                    >{{ t('Save draft') }}</Button
                ><Button
                    :disabled="saving.busy.value || !!record?.archived_at"
                    @click="save(true)"
                    >{{ t('Confirm experience') }}</Button
                >
            </div>
            <div v-if="record" class="lifecycle-actions">
                <RevisionHistory :id="record.id" kind="experience" />
                <Button
                    variant="ghost"
                    :disabled="saving.busy.value"
                    @click="lifecycle(record.archived_at ? 'restore' : 'archive')"
                    >{{
                        record.archived_at ? t('Restore experience') : t('Archive experience')
                    }}</Button
                ><Button
                    variant="ghost"
                    :disabled="saving.busy.value"
                    @click="lifecycle('revoke')"
                    >{{ t('Withdraw public access') }}</Button
                ><Button
                    variant="ghost"
                    :disabled="saving.busy.value"
                    @click="lifecycle('delete')"
                    >{{ t('Delete experience') }}</Button
                >
            </div>
        </footer></WorkspaceShell
    >
</template>
