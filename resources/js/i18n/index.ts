import { createI18n, type MessageContext } from 'vue-i18n'
import { watch } from 'vue'
import en from './en.json'
import zh from './zh-CN.json'
import ja from './ja.json'

export const locales = ['en', 'zh-CN', 'ja'] as const
export type Locale = (typeof locales)[number]
export const localeStorageKey = 'careeratlas:locale'
export function supportedLocale(value: string | null | undefined): Locale | undefined {
    const language = value?.toLowerCase().split('-')[0]
    return language === 'zh'
        ? 'zh-CN'
        : language === 'ja'
          ? 'ja'
          : language === 'en'
            ? 'en'
            : undefined
}
function initialLocale(): Locale {
    try {
        const saved = supportedLocale(localStorage.getItem(localeStorageKey))
        if (saved) return saved
    } catch {
        /* Storage may be unavailable in private browsing. */
    }
    return (
        navigator.languages?.map(supportedLocale).find(Boolean) ||
        supportedLocale(navigator.language) ||
        'en'
    )
}
// Literal English keys avoid translating user-authored content. Message functions keep
// punctuation literal and interpolate only named parameters; no HTML is interpreted.
function messages(catalog: Record<string, string>) {
    return Object.fromEntries(
        Object.entries(catalog).map(([key, value]) => [
            key,
            (context: MessageContext) =>
                value.replace(/\{(\w+)\}/g, (_, name) => String(context.named(name))),
        ]),
    )
}
export const i18n = createI18n({
    legacy: false,
    locale: initialLocale(),
    fallbackLocale: 'en',
    missingWarn: false,
    fallbackWarn: false,
    messageResolver: (object, path) => (object as Record<string, any>)[path] ?? null,
    messages: { en: messages(en), 'zh-CN': messages(zh), ja: messages(ja) },
})
export const locale = i18n.global.locale
const validationPatterns = Object.keys(en)
    .filter((key) => key.includes('{field}'))
    .map((key) => ({
        key,
        names: [...key.matchAll(/\{(\w+)\}/g)].map((match) => match[1]!),
        pattern: new RegExp(
            '^' +
                key
                    .split(/(\{\w+\})/g)
                    .map((part) =>
                        /^\{\w+\}$/.test(part)
                            ? '(.+?)'
                            : part.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'),
                    )
                    .join('') +
                '$',
        ),
    }))
export function t(
    key: string | undefined,
    values: Record<string, string | number | null> = {},
): string {
    if (!key) return ''
    if (locale.value !== 'en' && !Object.hasOwn(en, key)) {
        for (const rule of validationPatterns) {
            const match = key.match(rule.pattern)
            if (!match) continue
            const fields: Record<string, string> = {
                name: 'Display name',
                email: 'Email',
                password: 'Password',
                question: 'Your question',
                'request id': 'Request ID',
            }
            const parameters = Object.fromEntries(
                rule.names.map((name, index) => {
                    const value = match[index + 1]!
                    return [name, name === 'field' ? t(fields[value] || value) : value]
                }),
            )
            return i18n.global.t(rule.key, parameters)
        }
    }
    return i18n.global.t(key, values)
}
export function setLocale(value: string) {
    locale.value = supportedLocale(value) || 'en'
}
watch(
    locale,
    (value) => {
        document.documentElement.lang = value
        try {
            localStorage.setItem(localeStorageKey, value)
        } catch {
            /* Optional persistence. */
        }
    },
    { immediate: true },
)
export function projectType(value: string): string {
    return t(
        (
            {
                commercial: 'Commercial project',
                personal: 'Personal project',
                learning: 'Learning project',
            } as Record<string, string>
        )[value] || value,
    )
}
export function skillCounts(projects: number, experiences: number): string {
    return `${t(projects === 1 ? '{count} project' : '{count} projects', { count: projects })} · ${t(experiences === 1 ? '{count} experience' : '{count} experiences', { count: experiences })}`
}
export function formatDate(
    value: string,
    options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' },
): string {
    return new Intl.DateTimeFormat(locale.value, options).format(new Date(value))
}
