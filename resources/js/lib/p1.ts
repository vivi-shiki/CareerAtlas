import { op } from './api'
import type { Experience, PageResult, Project } from '@/types/domain'
export async function allOwned<T>(operation: string): Promise<T[]> {
    const rows: T[] = []
    for (let page = 1; ; page++) {
        const result = await op<PageResult<T>>(operation, { page, per_page: 100 })
        rows.push(...result.items)
        if (rows.length >= result.total || !result.items.length) return rows
    }
}
export const sourceChoices = async () =>
    (await allOwned<Experience>('list_experiences')).filter((row) => row.confirmed_version_id)
export const projectChoices = () => allOwned<Project>('list_projects')
export function downloadMarkdown(filename: string, text: string) {
    const url = URL.createObjectURL(new Blob([text], { type: 'text/markdown;charset=utf-8' }))
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    link.click()
    setTimeout(() => URL.revokeObjectURL(url), 1000)
}
