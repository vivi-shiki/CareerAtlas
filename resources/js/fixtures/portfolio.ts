import type { PortfolioView } from '@/types/portfolio'

export type Density = 'sparse' | 'normal' | 'dense'

// Fictional visual fixtures only. Never used as a fallback for live API data.
export function portfolioFixture(density: Density = 'normal'): PortfolioView {
    const portfolio: PortfolioView = {
        name: 'Alex Morgan',
        direction: 'Frontend developer',
        bio: 'I build accessible interfaces and document how they work.',
        publishedAt: '17 Nov 2026',
        projects: [
            {
                id: 'atlas-notes',
                title: 'Atlas Notes',
                type: 'Personal project',
                role: 'Frontend developer',
                summary: 'A searchable workspace for project notes.',
                experiences: [
                    {
                        id: 'accessible-search',
                        title: 'Accessible project search',
                        occurred: 'October 2026',
                        contribution:
                            'Built the search interface, keyboard navigation and component tests.',
                        teamOutcome: 'The team released the project search workflow.',
                        skills: ['Vue 3', 'TypeScript', 'Accessibility'],
                        evidence: [
                            {
                                id: 'pull-request',
                                label: 'Pull request #24',
                                provenance: 'Supplied link',
                                url: 'https://example.com/pull/24',
                            },
                            {
                                id: 'keyboard-notes',
                                label: 'Keyboard test notes',
                                provenance: 'Owner statement',
                                statement: 'Checked tab order and keyboard navigation.',
                            },
                        ],
                    },
                ],
            },
        ],
    }
    const record = portfolio.projects[0]!.experiences[0]!
    if (density === 'sparse') {
        record.title = ''
        record.contribution = ''
        record.teamOutcome = ''
        record.skills = []
        record.evidence = []
    }
    if (density === 'dense') {
        record.title = 'Accessible project search / 多言語のプロジェクト検索とキーボード操作の改善'
        record.contribution =
            'I built a multilingual search interface with keyboard navigation, focus recovery and component tests. 日本語・中文の長いプロジェクト名や改行を含む説明でも、元の内容を省略せず読めるようにしました。 '
                .repeat(4)
                .trim()
        record.teamOutcome =
            'The team released the project search workflow across multiple languages. '
                .repeat(3)
                .trim()
        record.skills = [
            'Vue 3',
            'TypeScript',
            'Accessibility',
            'PostgreSQL',
            'PHP',
            'Laravel',
            'Playwright',
            'Internationalization',
            'Keyboard navigation',
            'Design systems',
            'Component testing',
            'Documentation',
        ]
        record.evidence = Array.from({ length: 8 }, (_, index) => ({
            id: `evidence-${index}`,
            label: `${index + 1}. Keyboard navigation and multilingual search / 検証記録`,
            provenance: 'Owner statement',
            statement: 'Fictional test notes.',
        }))
        portfolio.projects = Array.from({ length: 5 }, (_, index) => ({
            ...structuredClone(portfolio.projects[0]!),
            id: `project-${index}`,
            title: index === 0 ? 'Atlas Notes' : `Atlas Notes — iteration ${index + 1}`,
            experiences: [{ ...structuredClone(record), id: `experience-${index}` }],
        }))
    }
    return portfolio
}
