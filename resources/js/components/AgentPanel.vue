<script setup lang="ts">
import { t } from '@/i18n'
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { ArrowUp, Info, LoaderCircle, MessageCircle, Square } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { ApiError, request } from '@/lib/api'

export type Citation = {
    handle: string
    experience_id: string | null
    title: string
    version: string | number
    field: string
    excerpt: string
}
type Answer = {
    answer: string
    citations: Citation[]
    insufficient_information: boolean
    follow_up_questions: string[]
}
type Run = {
    id: string
    question: string
    state: string
    progress: string
    answer: Answer | null
    error_code: string | null
}
const props = defineProps<{ slug: string }>()
const emit = defineEmits<{ source: [citation: Citation] }>()
const available = ref(false),
    loading = ref(true),
    busy = ref(false),
    question = ref(''),
    error = ref('')
const runs = ref<Run[]>([]),
    conversation = ref<string | null>(null),
    input = ref<HTMLTextAreaElement | null>(null)
const maxInput = ref(2000),
    pendingId = ref<string | null>(null),
    pendingQuestion = ref('')
const active = computed(() => runs.value.find((run) => ['queued', 'running'].includes(run.state)))
const key = `careeratlas:agent:${props.slug}`
let timer: ReturnType<typeof setTimeout> | undefined
let disposed = false
const suggestions = [
    'What did this person build?',
    'Which skills have supporting examples?',
    'What was their personal contribution?',
]
const valid = computed(
    () => question.value.trim().length > 0 && Array.from(question.value).length <= maxInput.value,
)

function stored(value?: string | null) {
    try {
        if (value === undefined) return sessionStorage.getItem(key)
        if (value) sessionStorage.setItem(key, value)
        else sessionStorage.removeItem(key)
    } catch {
        /* The assistant also works when browser storage is disabled. */
    }
    return null
}
function forget() {
    conversation.value = null
    runs.value = []
    pendingId.value = null
    stored(null)
}
function failure(cause: unknown) {
    if (cause instanceof ApiError && (cause.status === 404 || cause.code === 'scope_changed')) {
        forget()
        error.value =
            'Published content changed or is unavailable. Previous answers have been cleared.'
    } else
        error.value =
            cause instanceof Error
                ? cause.message
                : 'Unable to complete this request. Please retry.'
}
function schedule() {
    clearTimeout(timer)
    if (!disposed && conversation.value) timer = setTimeout(refresh, active.value ? 900 : 10000)
}
async function refresh() {
    if (!conversation.value || disposed) return
    const requestedConversation = conversation.value
    try {
        const history = await request<{ id: string; runs: Run[] }>(
            `/api/public/${props.slug}/agent/conversations/${conversation.value}`,
        )
        if (!disposed && conversation.value === requestedConversation) runs.value = history.runs
    } catch (cause) {
        if (!disposed && conversation.value === requestedConversation) failure(cause)
    }
    schedule()
}
async function load() {
    loading.value = true
    error.value = ''
    try {
        const status = await request<{ available: boolean; max_input: number }>(
            `/api/public/${props.slug}/agent`,
        )
        available.value = status.available
        maxInput.value = status.max_input
        conversation.value = stored()
        if (conversation.value) await refresh()
    } catch (cause) {
        failure(cause)
    } finally {
        loading.value = false
    }
}
async function submit() {
    if (!valid.value || busy.value || active.value || !available.value) return
    busy.value = true
    error.value = ''
    try {
        if (!conversation.value) {
            const created = await request<{ id: string }>(
                `/api/public/${props.slug}/agent/conversations`,
                {},
            )
            conversation.value = created.id
            stored(created.id)
        }
        const text = question.value.trim()
        if (pendingQuestion.value !== text || !pendingId.value) {
            pendingQuestion.value = text
            pendingId.value = crypto.randomUUID()
        }
        const run = await request<Run>(
            `/api/public/${props.slug}/agent/conversations/${conversation.value}/runs`,
            { question: text, request_id: pendingId.value },
        )
        if (!runs.value.some((existing) => existing.id === run.id)) runs.value.push(run)
        pendingId.value = null
        schedule()
    } catch (cause) {
        failure(cause)
    } finally {
        busy.value = false
    }
}
async function cancel() {
    if (!active.value || busy.value) return
    busy.value = true
    error.value = ''
    try {
        const run = await request<Run>(
            `/api/public/${props.slug}/agent/runs/${active.value.id}/cancel`,
            {},
        )
        runs.value = runs.value.map((existing) => (existing.id === run.id ? run : existing))
    } catch (cause) {
        failure(cause)
    } finally {
        busy.value = false
        schedule()
    }
}
async function choose(text: string) {
    question.value = text
    await nextTick()
    input.value?.focus()
}
async function retry(run: Run) {
    question.value = run.question
    pendingId.value = null
    await submit()
}
function keydown(event: KeyboardEvent) {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
        event.preventDefault()
        void submit()
    }
}
function reset() {
    forget()
    error.value = ''
    question.value = ''
    input.value?.focus()
}
function visible() {
    if (document.visibilityState === 'visible') void refresh()
}
function citationLabel(field: string) {
    const labels: Record<string, string> = {
        personal_action: 'My contribution',
        result: 'Result',
        validation: 'Validation',
        remaining_work: 'Remaining work',
        title: 'Experience title',
        record_type: 'Record type',
        'project.title': 'Project',
        'project.role': 'Role',
        'project.background': 'Background',
        'project.team_outcome': 'Team outcome',
        'project.type': 'Project type',
        'occurrence.start': 'Start date',
        'occurrence.end': 'End date',
        'occurrence.precision': 'Date precision',
        'occurrence.ongoing': 'Ongoing work',
        display_name: 'Name',
        biography: 'About',
        direction: 'Career direction',
        preferred_language: 'Preferred language',
    }
    if (labels[field]) return labels[field]
    if (field.startsWith('skills.')) return 'Skill'
    if (field.startsWith('evidence.')) {
        const part = field.split('.').at(-1)
        return (
            (
                {
                    url: 'Evidence link',
                    label: 'Evidence label',
                    excerpt: 'Evidence excerpt',
                    kind: 'Evidence type',
                    provenance: 'Source provenance',
                } as Record<string, string>
            )[part || ''] || 'Evidence'
        )
    }
    if (field.startsWith('contact_links.')) return 'Contact link'
    return 'Published detail'
}
function runError(run: Run) {
    if (run.error_code === 'rate_limited')
        return 'The question service is busy or its limit was reached. Try again later.'
    if (run.error_code === 'timeout')
        return 'The answer took too long. You can retry or browse the sources.'
    if (run.error_code?.startsWith('invalid'))
        return 'The answer could not be verified against published sources. Please retry.'
    return 'An answer is unavailable right now. Your question is preserved.'
}
onMounted(() => {
    void load()
    document.addEventListener('visibilitychange', visible)
    window.addEventListener('focus', visible)
})
onBeforeUnmount(() => {
    disposed = true
    clearTimeout(timer)
    document.removeEventListener('visibilitychange', visible)
    window.removeEventListener('focus', visible)
})
</script>

<template>
    <aside id="ask" class="question-panel agent-panel" aria-labelledby="agent-title">
        <div class="agent-heading">
            <MessageCircle />
            <h2 id="agent-title">{{ t('Ask about this work') }}</h2>
        </div>
        <p class="muted">{{ t('Answers use this published portfolio only.') }}</p>
        <p v-if="loading" role="status" class="muted">{{ t('Checking question availability…') }}</p>
        <p v-else-if="!available" class="privacy-notice">
            <Info /><span>{{
                t(
                    'Questions are unavailable right now. You can still browse projects, skills and supporting evidence.',
                )
            }}</span>
        </p>
        <template v-else>
            <div
                v-if="!runs.length"
                class="agent-suggestions"
                :aria-label="t('Suggested questions')"
            >
                <button
                    v-for="suggestion in suggestions"
                    :key="suggestion"
                    type="button"
                    @click="choose(t(suggestion))"
                >
                    {{ t(suggestion) }}
                </button>
            </div>
            <ol v-if="runs.length" class="agent-turns" :aria-label="t('Conversation')">
                <li v-for="run in runs" :key="run.id" class="agent-turn" :data-state="run.state">
                    <p class="agent-question prose-value">
                        <span class="sr-only">{{ t('Your question:') }} </span>{{ run.question }}
                    </p>
                    <div
                        v-if="['queued', 'running'].includes(run.state)"
                        class="agent-progress"
                        role="status"
                    >
                        <LoaderCircle class="animate-spin" />{{ t(run.progress) }}…
                    </div>
                    <template v-else-if="run.answer">
                        <p v-if="run.answer.insufficient_information" class="agent-gap">
                            {{ t('Information gap') }}
                        </p>
                        <p class="agent-answer prose-value">{{ run.answer.answer }}</p>
                        <ul
                            v-if="run.answer.citations.length"
                            class="agent-citations"
                            :aria-label="t('Answer sources')"
                        >
                            <li
                                v-for="(citation, index) in run.answer.citations"
                                :key="`${citation.handle}-${index}`"
                            >
                                <button type="button" @click="emit('source', citation)">
                                    <strong>{{ index + 1 }}. {{ citation.title }}</strong
                                    ><span>{{ t(citationLabel(citation.field)) }}</span
                                    ><q>{{ citation.excerpt }}</q
                                    ><span class="source-action">{{
                                        t('View published source →')
                                    }}</span>
                                </button>
                            </li>
                        </ul>
                        <div v-if="run.answer.follow_up_questions.length" class="agent-suggestions">
                            <button
                                v-for="follow in run.answer.follow_up_questions"
                                :key="follow"
                                :disabled="!!active || busy"
                                @click="choose(follow)"
                            >
                                {{ follow }}
                            </button>
                        </div>
                    </template>
                    <p v-else-if="run.state === 'cancelled'" role="status" class="muted">
                        {{ t('Answer cancelled. You can ask another question.') }}
                    </p>
                    <div v-else role="status" class="agent-run-error">
                        <p>{{ t(runError(run)) }}</p>
                        <Button
                            variant="outline"
                            :disabled="!!active || busy"
                            @click="retry(run)"
                            >{{ t('Retry question') }}</Button
                        >
                    </div>
                </li>
            </ol>
            <form class="agent-form" @submit.prevent="submit">
                <label for="agent-question">{{ t('Your question') }}</label>
                <textarea
                    id="agent-question"
                    ref="input"
                    v-model="question"
                    rows="3"
                    :disabled="busy || !!active"
                    :placeholder="t('Ask about projects, skills or contributions…')"
                    aria-describedby="agent-help agent-count"
                    @keydown="keydown"
                />
                <div class="agent-form-meta">
                    <span
                        id="agent-count"
                        :class="{ 'field-error': Array.from(question).length > maxInput }"
                        >{{ Array.from(question).length }} / {{ maxInput }}</span
                    ><Button
                        v-if="active"
                        type="button"
                        variant="outline"
                        :disabled="busy"
                        @click="cancel"
                        ><Square />{{ t('Cancel answer') }}</Button
                    ><Button v-else type="submit" :disabled="!valid || busy"
                        ><ArrowUp />{{ busy ? t('Sending…') : t('Ask question') }}</Button
                    >
                </div>
                <p id="agent-help" class="agent-help">
                    {{
                        t(
                            'Enter to send · Shift+Enter for a new line. AI can make mistakes; check the sources. Avoid confidential information in questions.',
                        )
                    }}
                </p>
            </form>
            <button
                v-if="conversation"
                type="button"
                class="agent-clear"
                :disabled="!!active || busy"
                @click="reset"
            >
                {{ t('New conversation') }}
            </button>
        </template>
        <div v-if="error" class="agent-run-error" role="alert">
            <p>{{ t(error) }}</p>
            <Button variant="outline" :disabled="busy" @click="conversation ? refresh() : load()">{{
                t('Retry connection')
            }}</Button>
        </div>
    </aside>
</template>
