export type Precision = 'year' | 'month' | 'day'
export interface Occurrence {
    start: string
    precision: Precision
    end?: string | null
    end_precision?: Precision | null
    ongoing?: boolean
}
export interface Versioned {
    id: string
    version: number
}
export interface User {
    id: number
    name: string
    email: string
}
export interface Contact {
    label: string
    url: string
}
export interface Profile extends Versioned {
    display_name: string
    direction: string | null
    biography: string | null
    preferred_language: 'en' | 'ja' | 'zh'
    contact_links: Contact[]
}
export interface Project extends Versioned {
    title: string
    type: 'commercial' | 'personal' | 'learning'
    role: string
    background: string | null
    team_outcome: string | null
    occurrence: Occurrence | null
    archived_at: string | null
    deleted_at: string | null
}
export interface SkillRef {
    id: string
    name: string
}
export interface Evidence extends Versioned {
    experience_id: string
    kind: 'repository' | 'demo' | 'document' | 'statement'
    label: string
    url: string | null
    excerpt: string | null
    provenance: 'supplied_link' | 'owner_statement'
}
export interface Experience extends Versioned {
    project_id: string
    title: string | null
    record_type: 'work_contribution' | 'learning' | 'problem_solving' | null
    occurrence: Occurrence | null
    personal_action: string | null
    result: string | null
    validation: string | null
    remaining_work: string | null
    private_notes: string | null
    status: 'draft' | 'confirmed'
    confirmed_version_id: string | null
    archived_at: string | null
    deleted_at: string | null
    skills: SkillRef[]
    evidence: Evidence[]
    access_epoch: number
}
export interface Skill extends Versioned {
    name: string
    aliases: string[]
    experience_count: number
    project_count: number
    experiences: Pick<Experience, 'id' | 'title' | 'project_id' | 'status'>[]
}
export interface PageResult<T> {
    items: T[]
    total: number
    page: number
    per_page: number
}
export interface SelectionItem {
    experience_id: string
    fields: string[]
    project_fields: string[]
}
export interface Selection {
    profile_fields: string[]
    items: SelectionItem[]
    publication_id?: string
}
export interface Publication extends Versioned {
    slug: string
    state: 'published' | 'revoked'
    published_at: string
    has_unpublished_changes: boolean
    selection: Selection
}
export interface PublicItem
    extends Partial<
        Pick<
            Experience,
            | 'title'
            | 'record_type'
            | 'occurrence'
            | 'personal_action'
            | 'result'
            | 'validation'
            | 'remaining_work'
            | 'skills'
            | 'evidence'
        >
    > {
    id: string
    record_version_id: string
    confirmed_at: string
    project: Partial<Project> & { id: string }
}
export interface PublicSkill extends SkillRef {
    experience_ids: string[]
    project_ids: string[]
    experience_count: number
    project_count: number
}
export interface PublicPortfolio {
    profile: Partial<Profile>
    items: PublicItem[]
    skills: PublicSkill[]
    slug?: string
    version?: number
    published_at?: string
}
export interface Preview {
    portfolio: PublicPortfolio
    digest: string
}
