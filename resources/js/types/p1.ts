import type { Experience, Occurrence, Publication, Selection } from './domain'
export interface Source {
    id: string
    record_version_id: string
    content: Partial<Experience>
    project: Record<string, unknown>
}
export interface SourceChange {
    id: string
    state: string
}
export interface Capture {
    id: string
    version: number
    project_id: string
    notes: string
    target_language: string
    draft: CaptureDraft
    questions: string[]
    applied_experience_id: string | null
}
export interface CaptureDraft {
    title?: string | null
    record_type?: Experience['record_type']
    occurrence?: Occurrence | null
    personal_action?: string | null
    result?: string | null
    validation?: string | null
    remaining_work?: string | null
    skill_suggestions?: string[]
}
export interface Answer {
    id: string
    version: number
    question: string
    answer: string
    state: string
    sources: Source[]
    source_changes: SourceChange[]
}
export interface Practice {
    id: string
    version: number
    questions: string[]
    attempts: Record<number, { question: string; answer: string; feedback: unknown }>
    current_index: number
    state: string
    retained: boolean
}
export interface Preparation {
    id: string
    version: number
    topic: string
    company: string
    interview_date: string | null
    requirements: string
    source_url: string | null
    target_language: string
    answer_length: string
    sources: Source[]
    source_changes: SourceChange[]
    answers: Answer[]
    practice: Practice[]
}
export interface Opportunity {
    id: string
    version: number
    title: string
    direction: string
    requirements: string
    deadline: string | null
    target_language: string
    introduction: string
    include_introduction: boolean
    introduction_confirmed: boolean
    sources: Source[]
    source_changes: SourceChange[]
    selection: Selection
    publication: Publication | null
    archived_at: string | null
}
export interface ExportDocument {
    filename: string
    markdown: string
    source_digest: string
    language: string
    fields: { key: string; label: string; text: string; source_version: string | null }[]
}
