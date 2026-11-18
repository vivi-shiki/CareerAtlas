import assert from 'node:assert/strict'
import { readdir, readFile, access } from 'node:fs/promises'
import { resolve, dirname, relative } from 'node:path'

const locales = ['en', 'zh-CN', 'ja']
const root = resolve('docs')
const features = JSON.parse(await readFile(resolve(root, 'feature-map.json'), 'utf8'))
const spec = await readFile(
    '.github/KnowledgeBase/KB_CareerAtlas_FunctionalRequirements.md',
    'utf8',
)
const expectedIds = [...spec.matchAll(/\| (S[1-5]-\d{2}) \|/g)].map((match) => match[1]).sort()
assert.deepEqual(
    features.map((f) => f.id).sort(),
    expectedIds,
    'Feature map must cover every requirement exactly once.',
)
const chapters = (await readdir(resolve(root, 'en'))).filter((name) => name.endsWith('.md')).sort()
let links = 0,
    images = 0,
    pages = 0
for (const locale of locales) {
    assert.deepEqual(
        (await readdir(resolve(root, locale))).filter((name) => name.endsWith('.md')).sort(),
        chapters,
        `${locale}: chapter parity`,
    )
    const roadmap = await readFile(resolve(root, locale, 'roadmap.md'), 'utf8')
    for (const feature of features) {
        assert(roadmap.includes(`| ${feature.id} |`), `${locale}: missing ${feature.id}`)
        assert(
            roadmap
                .split('\n')
                .find((line) => line.includes(`| ${feature.id} |`))
                .includes(feature.phase),
            `${locale}: wrong phase`,
        )
        await access(resolve(root, locale, `${feature.page}.md`))
    }
    for (const chapter of chapters) {
        const file = resolve(root, locale, chapter)
        const text = await readFile(file, 'utf8')
        assert(!/\]\([^)]*\.local\//.test(text), `${file}: private link`)
        for (const match of text.matchAll(/(!?)\[[^\]]*\]\(([^)]+)\)/g)) {
            if (/^(https?:|#)/.test(match[2])) continue
            const target = resolve(dirname(file), match[2].split('#')[0])
            assert(!relative(root, target).startsWith('..'), `${file}: link leaves docs`)
            await access(target)
            links++
            if (match[1]) {
                assert(
                    target.includes(
                        `screenshots${process.platform === 'win32' ? '\\' : '/'}${locale}`,
                    ),
                    `${file}: wrong screenshot language`,
                )
                images++
            }
        }
        const html = await readFile(
            resolve('public/docs', locale, chapter.replace(/\.md$/, '.html')),
            'utf8',
        )
        assert(html.includes(`<html lang="${locale}"`), `${locale}: HTML language`)
        assert(
            !/<script\b(?![^>]*\bsrc=)[^>]*>\s*[^<\s]/.test(html),
            `${file}: inline executable script violates CSP`,
        )
        pages++
    }
}
assert(images >= 36, 'Expected at least twelve localized screenshots per language.')
console.log(
    `Documentation verified: ${pages} localized pages, ${features.length} feature IDs, ${images} screenshot references, ${links} local links.`,
)
