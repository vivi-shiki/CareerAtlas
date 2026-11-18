<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { t } from '@/i18n'
import { op, ApiError } from '@/lib/api'
import { occurrenceText } from '@/lib/portfolio'
import type { Occurrence } from '@/types/domain'
import { useAction } from '@/composables/useFormState'
import FormFeedback from './FormFeedback.vue'
import type { OwnerApplication, OwnerProposal } from '@/types/p1'
const props = defineProps<{
    subject: { id: string; version: number }
    purpose: 'capture' | 'mapping' | 'answer' | 'introduction' | 'feedback' | 'export'
    kind: 'capture' | 'preparation' | 'practice' | 'opportunity'
    question?: string
    disabled?: boolean
}>()
const emit = defineEmits<{ applied: [value: OwnerApplication] }>()
interface Run {
    id: string
    state: string
    output: OwnerProposal | null
    error_code: string | null
}
const available = ref(false),
    run = ref<Run | null>(null),
    action = useAction(),
    skipped = ref(false),
    polling = ref(false)
const active = computed(() => !!run.value && ['queued', 'running'].includes(run.value.state))
const names: Record<string, string> = {
    capture: 'Draft with AI',
    mapping: 'Map requirements to evidence',
    answer: 'Draft an answer',
    introduction: 'Draft an introduction',
    feedback: 'Review this attempt',
    export: 'Translate selected export',
}
const feedbackLabels: Record<string, string> = {
    specificity: 'Specificity',
    structure: 'Structure',
    consistency: 'Consistency',
    clarity: 'Clarity',
}
const draftLabels: Record<string, string> = {
    title: 'Title',
    personal_action: 'Personal action',
    result: 'Result',
    validation: 'Validation',
    remaining_work: 'Remaining work',
    record_type: 'Record type',
    occurrence: 'Date',
    skill_suggestions: 'Suggested skills',
}
function draftValue(field: string, value: unknown): string {
    if (value == null || value === '') return '—'
    if (field === 'occurrence') return occurrenceText(value as Occurrence) || '—'
    if (field === 'record_type') {
        const labels: Record<string, string> = {
            work_contribution: 'Work contribution',
            learning: 'Learning',
            problem_solving: 'Problem solving',
        }
        return t(labels[String(value)] || String(value))
    }
    if (Array.isArray(value)) return value.join(' · ') || '—'
    return String(value)
}
let timer: ReturnType<typeof setTimeout> | undefined,
    alive = true,
    key = '',
    fingerprint = '',
    generation = 0
function clear() {
    if (timer) clearTimeout(timer)
    timer = undefined
}
async function poll() {
    clear()
    if (!alive || !run.value) return
    const revision = generation
    polling.value = true
    try {
        const value = await op<Run>('get_owner_ai', { id: run.value.id })
        if (!alive || revision !== generation) return
        run.value = value
        action.error.value = null
        if (active.value) timer = setTimeout(poll, 900)
    } catch (error) {
        if (alive && revision === generation) action.error.value = error as ApiError
    } finally {
        polling.value = false
    }
}
async function start() {
    const input = {
        purpose: props.purpose,
        subject_id: props.subject.id,
        expected_version: props.subject.version,
        subject_kind: props.kind,
        ...(props.question ? { question: props.question } : {}),
    }
    const next = JSON.stringify(input)
    if (next !== fingerprint || !key || (run.value && !active.value)) {
        key = crypto.randomUUID()
        fingerprint = next
    }
    const value = await action.run(() => op<Run>('start_owner_ai', { ...input, request_id: key }))
    if (value) {
        run.value = value
        skipped.value = false
        if (active.value) timer = setTimeout(poll, 300)
    }
}
async function cancel() {
    clear()
    generation++
    if (!run.value) return
    const value = await action.run(() => op<Run>('cancel_owner_ai', { id: run.value!.id }))
    if (value) run.value = value
}
async function apply() {
    const value = await action.run(() =>
        op<OwnerApplication>('apply_owner_ai', { id: run.value!.id }),
    )
    if (value) {
        run.value = null
        key = ''
        emit('applied', value)
    }
}
watch(
    () => [props.subject.id, props.subject.version, props.purpose],
    () => {
        clear()
        generation++
        if (active.value && run.value)
            void op('cancel_owner_ai', { id: run.value.id }).catch(() => {})
        run.value = null
        key = ''
        action.error.value = null
    },
)
onMounted(async () => {
    const value = await action.run(() => op<{ available: boolean }>('owner_ai_status'))
    if (alive && value) available.value = value.available
})
onBeforeUnmount(() => {
    alive = false
    clear()
    if (active.value && run.value) void op('cancel_owner_ai', { id: run.value.id }).catch(() => {})
})
</script>
<template>
    <section class="p1-source owner-assistant" :data-purpose="purpose">
        <h3>{{ t(names[purpose]) }}</h3>
        <p>{{ t('AI proposes a draft. You decide what to keep, confirm and publish.') }}</p>
        <FormFeedback :error="action.error.value" />
        <p v-if="!available && !action.busy.value">
            {{ t('AI drafting is unavailable. You can keep editing manually.') }}
        </p>
        <button
            type="button"
            :disabled="!available || disabled || action.busy.value || active"
            @click="start"
        >
            {{ run ? t('Try another draft') : t(names[purpose]) }}
        </button>
        <template v-if="active"
            ><p role="status">{{ t('Preparing a source-backed draft...') }}</p>
            <button type="button" :disabled="action.busy.value && !polling" @click="cancel">
                {{ t('Cancel drafting') }}</button
            ><button v-if="action.error.value" type="button" @click="poll">
                {{ t('Check draft status') }}
            </button></template
        >
        <p v-if="run && ['failed', 'expired', 'cancelled'].includes(run.state)" role="status">
            {{
                run.state === 'cancelled'
                    ? t('Drafting cancelled. Your saved work is unchanged.')
                    : run.error_code === 'source_changed'
                      ? t('Sources changed. Review and select them again.')
                      : t('Draft unavailable. Your input is preserved; retry when ready.')
            }}
        </p>
        <div v-if="run?.state === 'completed' && run.output" class="owner-proposal">
            <p class="p1-warning">
                {{ t('Review this proposal before applying it as a private draft.') }}
            </p>
            <p v-if="run.output.insufficient_information">
                {{ t('Some requested facts are not established by the selected sources.') }}
            </p>
            <p v-if="run.output.text" class="p1-history">{{ run.output.text }}</p>
            <dl v-if="run.output.draft">
                <template v-for="(value, field) in run.output.draft" :key="field"
                    ><dt>{{ t(draftLabels[field] || field) }}</dt>
                    <dd>
                        {{ draftValue(field, value) }}
                    </dd></template
                >
            </dl>
            <article v-for="(mapping, index) in run.output.mapping" :key="index" class="p1-source">
                <h4>{{ mapping.requirement }}</h4>
                <p>{{ mapping.reason }}</p>
                <p v-if="mapping.gap">{{ t('Evidence gap') }}: {{ mapping.gap }}</p>
                <a
                    v-for="id in mapping.source_ids"
                    :key="id"
                    :href="`/experiences/${id}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    >{{ t('Open source') }}</a
                >
            </article>
            <dl v-if="run.output.feedback">
                <template v-for="(value, field) in run.output.feedback" :key="field"
                    ><dt>{{ t(feedbackLabels[field] || field) }}</dt>
                    <dd>{{ value }}</dd></template
                >
            </dl>
            <p v-if="run.output.translations">
                {{ t('Translated fields are ready for your review before download.') }}
            </p>
            <details v-if="run.output.citations.length">
                <summary>{{ t('Sources') }}</summary>
                <blockquote
                    v-for="(citation, index) in run.output.citations"
                    :key="index"
                    class="p1-source"
                >
                    <p>{{ citation.excerpt }}</p>
                    <a
                        v-if="citation.experience_id"
                        :href="`/experiences/${citation.experience_id}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        >{{ t('Open source') }}</a
                    ><small v-else>{{ t('Selected owner source') }}</small>
                </blockquote>
            </details>
            <template v-if="run.output.questions.length && !skipped"
                ><h4>{{ t('Optional follow-up questions') }}</h4>
                <ol>
                    <li v-for="question in run.output.questions" :key="question">{{ question }}</li>
                </ol>
                <p>
                    {{
                        t(
                            'Add missing facts to your notes or source records, save, then request another draft.',
                        )
                    }}
                </p>
                <button type="button" @click="skipped = true">
                    {{ t('Skip follow-ups') }}
                </button></template
            >
            <button
                type="button"
                class="p1-primary"
                :disabled="disabled || action.busy.value"
                @click="apply"
            >
                {{
                    purpose === 'mapping'
                        ? t('Use suggested sources')
                        : purpose === 'export'
                          ? t('Review translated fields')
                          : purpose === 'feedback'
                            ? t('Keep feedback')
                            : t('Use draft')
                }}</button
            ><button type="button" :disabled="action.busy.value" @click="cancel">
                {{ t('Discard proposal') }}
            </button>
        </div>
    </section>
</template>
<style scoped>
.owner-proposal dt {
    font-weight: 600;
    margin-top: 0.75rem;
}
.owner-proposal dd {
    margin: 0.25rem 0 0;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}
</style>
