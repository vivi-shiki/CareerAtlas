import { t, locale, projectType, formatDate } from '@/i18n'
import type { Occurrence, PublicPortfolio } from '@/types/domain'
import type { PortfolioView, ProjectView } from '@/types/portfolio'

export function occurrenceText(value?: Occurrence | null) {
    if (!value?.start) return ''
    const date = (text: string, precision: string) => {
        if (precision === 'year') return text
        const [year, month, day] = text.split('-').map(Number)
        const formatted = new Intl.DateTimeFormat(locale.value, {
            month: 'long',
            year: 'numeric',
            ...(precision === 'day' ? { day: 'numeric' } : {}),
            timeZone: 'UTC',
        }).format(new Date(Date.UTC(year!, month! - 1, day || 1)))
        return formatted
    }
    return `${date(value.start, value.precision)}${value.ongoing ? ` — ${t('Ongoing')}` : value.end ? ` — ${date(value.end, value.end_precision || value.precision)}` : ''}`
}

export function portfolioView(data: PublicPortfolio): PortfolioView {
    const projects = new Map<string, ProjectView>()
    for (const item of data.items) {
        const context = item.project
        if (!projects.has(context.id))
            projects.set(context.id, {
                id: context.id,
                title: context.title || t('Selected project'),
                type: context.type ? projectType(context.type) : '',
                role: context.role || '',
                summary: context.background || '',
                occurred: occurrenceText(context.occurrence),
                experiences: [],
            })
        // A shared project may be selected through multiple experiences with different field lists.
        // Merge only context fields already present in this authorized publication projection.
        const project = projects.get(context.id)!
        if (context.title) project.title = context.title
        if (context.type) project.type = projectType(context.type)
        if (context.role) project.role = context.role
        if (context.background) project.summary = context.background
        if (context.occurrence) project.occurred = occurrenceText(context.occurrence)
        projects.get(context.id)!.experiences.push({
            id: item.id,
            title: item.title || '',
            contribution: item.personal_action || '',
            teamOutcome: context.team_outcome || '',
            occurred: occurrenceText(item.occurrence),
            skills: item.skills?.map((skill) => skill.name) || [],
            evidence: (item.evidence || []).map((evidence) => ({
                id: evidence.id,
                label: evidence.label,
                provenance:
                    evidence.provenance === 'owner_statement' ? 'Owner statement' : 'Supplied link',
                ...(evidence.url ? { url: evidence.url } : {}),
                ...(evidence.excerpt ? { statement: evidence.excerpt } : {}),
            })),
            recordType: item.record_type
                ? t(
                      (
                          {
                              work_contribution: 'Work contribution',
                              learning: 'Learning',
                              problem_solving: 'Problem solving',
                          } as Record<string, string>
                      )[item.record_type],
                  )
                : '',
            result: item.result || '',
            validation: item.validation || '',
            remainingWork: item.remaining_work || '',
        })
    }
    return {
        name: data.profile.display_name || '',
        direction: data.profile.direction || '',
        bio: data.profile.biography || '',
        contacts: data.profile.contact_links || [],
        preferredLanguage: t(
            ({ en: 'English', zh: 'Chinese', ja: 'Japanese' } as Record<string, string>)[
                data.profile.preferred_language || ''
            ],
        ),
        publishedAt: data.published_at
            ? formatDate(data.published_at, {
                  day: 'numeric',
                  month: 'short',
                  year: 'numeric',
              })
            : '',
        projects: [...projects.values()],
    }
}
