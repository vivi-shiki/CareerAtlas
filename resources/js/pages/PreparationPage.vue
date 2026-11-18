<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { t } from '@/i18n'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import LoadState from '@/components/LoadState.vue'
import FormFeedback from '@/components/FormFeedback.vue'
import TargetLanguage from '@/components/TargetLanguage.vue'
import SourcePicker from '@/components/SourcePicker.vue'
import SourceFreshness from '@/components/SourceFreshness.vue'
import RevisionHistory from '@/components/RevisionHistory.vue'
import OwnerAssistant from '@/components/OwnerAssistant.vue'
import { op } from '@/lib/api'
import { sourceChoices } from '@/lib/p1'
import { currentUser } from '@/lib/session'
import { useAction, useDraftGuard } from '@/composables/useFormState'
import { confirmAction } from '@/composables/useConfirm'
import type { Experience } from '@/types/domain'
import type { Answer, Preparation, Practice, OwnerApplication } from '@/types/p1'
const route = useRoute(),
    router = useRouter(),
    row = ref<Preparation | null>(null),
    rows = ref<Preparation[]>([]),
    records = ref<Experience[]>([])
const form = reactive({
    topic: '',
    company: '',
    interview_date: '',
    requirements: '',
    source_url: '',
    target_language: 'en',
    answer_length: 'standard',
    experience_ids: [] as string[],
})
const answer = ref<Answer | null>(null),
    answerForm = reactive({ question: '', answer: '' }),
    practice = ref<Practice | null>(null),
    questions = ref(''),
    attempt = ref('')
const savedBrief = ref(JSON.stringify(form)),
    savedAnswer = ref(JSON.stringify(answerForm)),
    savedAttempt = ref('')
const loading = useAction(),
    action = useAction(),
    guard = useDraftGuard(() => [
        JSON.stringify(form) !== savedBrief.value,
        JSON.stringify(answerForm) !== savedAnswer.value,
        attempt.value !== savedAttempt.value,
    ])
function applyBrief(value: Preparation) {
    row.value = value
    for (const key of [
        'topic',
        'company',
        'requirements',
        'target_language',
        'answer_length',
    ] as const)
        form[key] = value[key]
    form.interview_date = value.interview_date || ''
    form.source_url = value.source_url || ''
    form.experience_ids = value.sources.map((source) => source.id)
    savedBrief.value = JSON.stringify(form)
}
async function load() {
    const data = await loading.run(async () => ({
        records: await sourceChoices(),
        rows: await op<Preparation[]>('list_preparations'),
        row: route.params.id
            ? await op<Preparation>('get_preparation', { id: route.params.id })
            : null,
    }))
    if (data) {
        records.value = data.records
        rows.value = data.rows
        if (data.row) applyBrief(data.row)
        guard.markSaved()
    }
}
async function refresh() {
    row.value = await op<Preparation>('get_preparation', { id: row.value!.id })
}
async function save() {
    const input = {
        ...form,
        interview_date: form.interview_date || null,
        source_url: form.source_url || null,
    }
    const result = await action.run(
        () =>
            op<Preparation>(
                row.value ? 'update_preparation' : 'create_preparation',
                row.value
                    ? { ...input, id: row.value.id, expected_version: row.value.version }
                    : input,
            ),
        'Saved',
    )
    if (result) {
        applyBrief(result)
        if (!route.params.id) await router.replace(`/preparation/${result.id}`)
    }
}
async function editAnswer(value: Answer | null) {
    if (
        JSON.stringify(answerForm) !== savedAnswer.value &&
        !(await confirmAction({
            title: 'Discard unsaved changes?',
            message: 'Your changes have not been saved.',
            confirm: 'Discard changes',
            cancel: 'Keep editing',
        }))
    )
        return
    answer.value = value
    Object.assign(answerForm, { question: value?.question || '', answer: value?.answer || '' })
    savedAnswer.value = JSON.stringify(answerForm)
}
async function saveAnswer(refreshSources = false) {
    const result = await action.run(async () => {
        const value = await op<Answer>(
            answer.value ? 'update_prepared_answer' : 'create_prepared_answer',
            {
                ...answerForm,
                ...(answer.value
                    ? { id: answer.value.id, expected_version: answer.value.version }
                    : { preparation_id: row.value!.id }),
                ...(refreshSources ? { experience_ids: form.experience_ids } : {}),
            },
        )
        await refresh()
        return value
    }, 'Saved')
    if (result) {
        answer.value = result
        savedAnswer.value = JSON.stringify(answerForm)
    }
}
async function confirmAnswer() {
    const result = await action.run(async () => {
        const value = await op<Answer>('confirm_prepared_answer', {
            id: answer.value!.id,
            expected_version: answer.value!.version,
        })
        await refresh()
        return value
    }, 'Saved')
    if (result) answer.value = result
}
function applyPractice(value: Practice) {
    practice.value = value
    attempt.value = value.attempts[value.current_index]?.answer || ''
    savedAttempt.value = attempt.value
}
async function start() {
    const result = await action.run(() =>
        op<Practice>('start_practice', {
            preparation_id: row.value!.id,
            questions: questions.value
                .split('\n')
                .map((value) => value.trim())
                .filter(Boolean),
        }),
    )
    if (result) applyPractice(result)
}
async function practiceAction(name: string) {
    if (
        name === 'discard' &&
        !(await confirmAction({
            title: 'Discard practice?',
            message: 'This removes the practice session and its answers.',
            confirm: 'Discard',
            cancel: 'Cancel',
            destructive: true,
        }))
    )
        return
    const result = await action.run(async () => {
        const value = await op<Practice>(`${name}_practice`, {
            id: practice.value!.id,
            expected_version: practice.value!.version,
            ...(name === 'answer' ? { answer: attempt.value } : {}),
        })
        await refresh()
        return value
    })
    if (result) {
        if (name === 'discard') {
            practice.value = null
            attempt.value = ''
            savedAttempt.value = ''
        } else applyPractice(result)
    }
}
onMounted(load)
const feedbackLabels: Record<string, string> = {
    specificity: 'Specificity',
    structure: 'Structure',
    consistency: 'Consistency',
    clarity: 'Clarity',
}
const currentFeedback = computed(
    () =>
        (practice.value?.attempts[practice.value.current_index]?.feedback || null) as Record<
            string,
            string
        > | null,
)
async function appliedProposal(value: OwnerApplication) {
    if (value.output.mapping) {
        form.experience_ids = [
            ...new Set(value.output.mapping.flatMap((mapping) => mapping.source_ids)),
        ]
        return
    }
    if (value.output.feedback) {
        applyPractice(value.subject as Practice)
        return
    }
    const saved = value.subject as Answer
    answer.value = saved
    Object.assign(answerForm, { question: saved.question, answer: saved.answer })
    savedAnswer.value = JSON.stringify(answerForm)
    await action.run(refresh)
}
</script>
<template>
    <WorkspaceShell active="Preparation" :name="currentUser?.name"
        ><div class="workspace-page p1-page">
            <PageHeading
                :title="t('Prepare with your own evidence')"
                :description="
                    t(
                        'Build answers from confirmed experiences and practice one question at a time.',
                    )
                "
                :status="t('Private')"
            />
            <LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><div class="p1-grid">
                    <div>
                        <section class="p1-card">
                            <h2>{{ t('Preparation brief') }}</h2>
                            <FormFeedback
                                :error="action.error.value"
                                :message="action.message.value"
                            /><SourceFreshness :changes="row?.source_changes" />
                            <form @submit.prevent="save">
                                <fieldset :disabled="action.busy.value">
                                    <label
                                        >{{ t('Topic or role')
                                        }}<input
                                            v-model="form.topic"
                                            name="topic"
                                            required
                                            maxlength="200" /></label
                                    ><label
                                        >{{ t('Company (optional)')
                                        }}<input v-model="form.company" maxlength="200" /></label
                                    ><label
                                        >{{ t('Interview date (optional)')
                                        }}<input v-model="form.interview_date" type="date" /></label
                                    ><label
                                        >{{ t('Requirements')
                                        }}<textarea v-model="form.requirements" rows="5" /></label
                                    ><label
                                        >{{ t('Source URL (reference only)')
                                        }}<input v-model="form.source_url" type="url"
                                    /></label>
                                    <div class="p1-row">
                                        <TargetLanguage v-model="form.target_language" /><label
                                            >{{ t('Answer length')
                                            }}<select v-model="form.answer_length">
                                                <option value="short">{{ t('Short') }}</option>
                                                <option value="standard">
                                                    {{ t('Standard') }}
                                                </option>
                                            </select></label
                                        >
                                    </div>
                                    <SourcePicker
                                        v-model="form.experience_ids"
                                        :records="records"
                                    /><button class="p1-primary" type="submit">
                                        {{ t('Save preparation') }}
                                    </button>
                                </fieldset>
                            </form>
                            <OwnerAssistant
                                v-if="row"
                                :subject="row"
                                purpose="mapping"
                                kind="preparation"
                                :disabled="action.busy.value || JSON.stringify(form) !== savedBrief"
                                @applied="appliedProposal"
                            />
                        </section>
                        <template v-if="row"
                            ><section class="p1-card">
                                <h2>{{ t('Prepared answers') }}</h2>
                                <button @click="editAnswer(null)">{{ t('New answer') }}</button>
                                <div v-for="saved in row.answers" :key="saved.id" class="p1-source">
                                    <strong>{{ saved.question }}</strong>
                                    <p>
                                        {{ t(saved.state === 'confirmed' ? 'Confirmed' : 'Draft') }}
                                    </p>
                                    <button @click="editAnswer(saved)">
                                        {{ t('Edit answer') }}
                                    </button>
                                </div>
                                <SourceFreshness :changes="answer?.source_changes" />
                                <OwnerAssistant
                                    :subject="row"
                                    purpose="answer"
                                    kind="preparation"
                                    :question="answerForm.question"
                                    :disabled="
                                        action.busy.value ||
                                        !answerForm.question.trim() ||
                                        JSON.stringify(form) !== savedBrief ||
                                        answerForm.answer !== (answer?.answer || '')
                                    "
                                    @applied="appliedProposal"
                                />
                                <form @submit.prevent="saveAnswer()">
                                    <fieldset :disabled="action.busy.value">
                                        <label
                                            >{{ t('Question')
                                            }}<input
                                                v-model="answerForm.question"
                                                name="question"
                                                required
                                                maxlength="2000" /></label
                                        ><label
                                            >{{ t('Prepared answer')
                                            }}<textarea
                                                v-model="answerForm.answer"
                                                name="answer"
                                                rows="7"
                                                maxlength="16000"
                                            /></label
                                        ><button
                                            type="submit"
                                            class="p1-primary"
                                            :disabled="action.busy.value"
                                        >
                                            {{ t('Save answer draft') }}
                                        </button>
                                    </fieldset>
                                </form>
                                <template v-if="answer"
                                    ><button
                                        :disabled="
                                            action.busy.value ||
                                            JSON.stringify(answerForm) !== savedAnswer
                                        "
                                        @click="confirmAnswer"
                                    >
                                        {{ t('Confirm answer') }}</button
                                    ><button
                                        :disabled="action.busy.value"
                                        @click="saveAnswer(true)"
                                    >
                                        {{ t('Refresh answer sources') }}</button
                                    ><RevisionHistory :id="answer.id" kind="answer"
                                /></template>
                                <p>
                                    {{
                                        t(
                                            'Review selected sources before refreshing. This creates a new draft version.',
                                        )
                                    }}
                                </p>
                            </section>
                            <section class="p1-card">
                                <h2>{{ t('Written practice') }}</h2>
                                <template v-if="!practice"
                                    ><label
                                        >{{ t('Practice questions')
                                        }}<textarea
                                            v-model="questions"
                                            rows="4"
                                            :placeholder="t('One question per line')"
                                        /></label
                                    ><button
                                        class="p1-primary"
                                        :disabled="action.busy.value || !questions.trim()"
                                        @click="start"
                                    >
                                        {{ t('Start practice') }}
                                    </button>
                                    <div
                                        v-for="saved in row.practice"
                                        :key="saved.id"
                                        class="p1-source"
                                    >
                                        <p>{{ saved.questions[0] }}</p>
                                        <button @click="applyPractice(saved)">
                                            {{ t('Open saved practice') }}
                                        </button>
                                    </div></template
                                >
                                <template v-else
                                    ><p>
                                        {{ t('Question') }} {{ practice.current_index + 1 }} /
                                        {{ practice.questions.length }}
                                    </p>
                                    <h3 data-testid="practice-question">
                                        {{ practice.questions[practice.current_index] }}
                                    </h3>
                                    <label
                                        >{{ t('Your answer')
                                        }}<textarea
                                            v-model="attempt"
                                            rows="6"
                                            :disabled="
                                                action.busy.value || practice.state !== 'active'
                                            "
                                        /></label
                                    ><button
                                        :disabled="
                                            action.busy.value ||
                                            practice.state !== 'active' ||
                                            !attempt.trim()
                                        "
                                        @click="practiceAction('answer')"
                                    >
                                        {{ t('Save attempt') }}</button
                                    ><button
                                        :disabled="
                                            action.busy.value ||
                                            practice.state !== 'active' ||
                                            !practice.attempts[practice.current_index] ||
                                            attempt !== savedAttempt
                                        "
                                        @click="practiceAction('next')"
                                    >
                                        {{ t('Next question') }}</button
                                    ><button
                                        :disabled="
                                            action.busy.value ||
                                            practice.state !== 'active' ||
                                            attempt !== savedAttempt
                                        "
                                        @click="practiceAction('stop')"
                                    >
                                        {{ t('Stop practice') }}</button
                                    ><button
                                        :disabled="
                                            action.busy.value ||
                                            practice.retained ||
                                            attempt !== savedAttempt
                                        "
                                        @click="practiceAction('save')"
                                    >
                                        {{ t('Keep practice') }}</button
                                    ><button
                                        :disabled="action.busy.value"
                                        @click="practiceAction('discard')"
                                    >
                                        {{ t('Discard practice') }}
                                    </button>
                                    <p>
                                        {{
                                            t(
                                                'Unsaved practice expires after one day. Keep it explicitly to retain it.',
                                            )
                                        }}
                                    </p>
                                    <OwnerAssistant
                                        v-if="practice.attempts[practice.current_index]"
                                        :subject="practice"
                                        purpose="feedback"
                                        kind="practice"
                                        :disabled="action.busy.value || attempt !== savedAttempt"
                                        @applied="appliedProposal"
                                    />
                                    <dl v-if="currentFeedback" class="p1-source">
                                        <template
                                            v-for="(value, field) in currentFeedback"
                                            :key="field"
                                            ><dt>{{ t(feedbackLabels[field] || field) }}</dt>
                                            <dd>{{ value }}</dd></template
                                        >
                                    </dl>
                                </template>
                            </section></template
                        >
                    </div>
                    <aside class="p1-card p1-context">
                        <h2>{{ t('Saved preparations') }}</h2>
                        <RouterLink class="text-action" to="/preparation">{{
                            t('New preparation')
                        }}</RouterLink>
                        <ul>
                            <li v-for="item in rows" :key="item.id">
                                <RouterLink :to="`/preparation/${item.id}`">{{
                                    item.topic
                                }}</RouterLink>
                            </li>
                        </ul>
                        <h3>{{ t('Evidence in context') }}</h3>
                        <div v-for="source in row?.sources" :key="source.id" class="p1-source">
                            <RouterLink :to="`/experiences/${source.id}`">{{
                                source.content.title
                            }}</RouterLink>
                            <p>{{ source.content.personal_action }}</p>
                        </div>
                    </aside>
                </div></LoadState
            >
        </div></WorkspaceShell
    >
</template>
