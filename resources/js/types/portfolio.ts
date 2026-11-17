export interface EvidenceView {
    id: string
    label: string
    provenance: 'Supplied link' | 'Owner statement'
    url?: string
    statement?: string
}

export interface ExperienceView {
    id: string
    title: string
    contribution: string
    teamOutcome?: string
    background?: string
    occurred: string
    skills: string[]
    evidence: EvidenceView[]
    recordType?: string
    result?: string
    validation?: string
    remainingWork?: string
}

export interface ProjectView {
    id: string
    title: string
    type: string
    role: string
    summary: string
    occurred?: string
    experiences: ExperienceView[]
}

export interface PortfolioView {
    name: string
    direction?: string
    bio?: string
    publishedAt?: string
    projects: ProjectView[]
    contacts?: { label: string; url: string }[]
    preferredLanguage?: string
}
