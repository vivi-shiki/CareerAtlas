import assert from 'node:assert/strict'
import fs from 'node:fs'
import path from 'node:path'
import { parse } from '@vue/compiler-sfc'
import { baseParse } from '@vue/compiler-dom'

const directory = 'resources/js/i18n'
const english = JSON.parse(fs.readFileSync(`${directory}/en.json`, 'utf8'))
const tokens = (text) => [...text.matchAll(/\{(\w+)\}/g)].map((match) => match[1]).sort()
for (const language of ['zh-CN', 'ja']) {
    const catalog = JSON.parse(fs.readFileSync(`${directory}/${language}.json`, 'utf8'))
    assert.deepEqual(
        Object.keys(catalog).sort(),
        Object.keys(english).sort(),
        `${language}: key parity`,
    )
    for (const [key, value] of Object.entries(catalog)) {
        assert.equal(typeof value, 'string')
        assert.ok(value.trim(), `${language}: empty ${key}`)
        assert.deepEqual(tokens(value), tokens(english[key]), `${language}: interpolation ${key}`)
    }
}
// Native language names, brand names, and date/URL input formats are intentional literals.
const literal = new Set([
    'CareerAtlas',
    'English',
    '中文',
    '日本語',
    'https://',
    'YYYY',
    'YYYY-MM',
    'YYYY-MM-DD',
])
const errors = []
function walk(directory) {
    for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
        const file = path.join(directory, entry.name)
        if (entry.isDirectory()) {
            if (!['design', 'ui', 'i18n', 'fixtures'].includes(entry.name)) walk(file)
        } else if (file.endsWith('.vue')) {
            const template = parse(fs.readFileSync(file, 'utf8')).descriptor.template
            if (!template) continue
            const inspect = (node) => {
                const check = (value) => {
                    const text = value.replace(/\s+/g, ' ').trim()
                    if (/[A-Za-z\u3040-\u30ff\u3400-\u9fff]/.test(text) && !literal.has(text))
                        errors.push(`${file}: ${text}`)
                }
                if (node.type === 2) check(node.content)
                for (const prop of node.props || []) {
                    if (
                        prop.type === 6 &&
                        ['title', 'description', 'placeholder', 'aria-label', 'status'].includes(
                            prop.name,
                        ) &&
                        prop.value
                    )
                        check(prop.value.content)
                }
                for (const child of node.children || []) inspect(child)
            }
            inspect(baseParse(template.content))
        }
    }
}
walk('resources/js')
assert.deepEqual(errors, [], 'Unlocalized visible production template text')
console.log(
    `PASS: 3 catalogs, ${Object.keys(english).length} messages each, parameter parity and production template coverage.`,
)
