import { createHash } from 'node:crypto'
import { readdir, readFile, writeFile } from 'node:fs/promises'
import { resolve, join } from 'node:path'

// VitePress boot data and appearance initialization must obey script-src 'self'.
const root = resolve('public/docs')
async function processDirectory(directory) {
    for (const entry of await readdir(directory, { withFileTypes: true })) {
        const path = join(directory, entry.name)
        if (entry.isDirectory()) await processDirectory(path)
        else if (entry.name.endsWith('.html')) {
            let html = await readFile(path, 'utf8')
            const scripts = [...html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/g)]
            for (const [tag, attributes, body] of scripts) {
                if (/\bsrc\s*=/.test(attributes) || !body.trim()) continue
                if (/\btype=["']application\//.test(attributes)) continue
                const filename = `boot-${createHash('sha256').update(body).digest('hex').slice(0, 16)}.js`
                await writeFile(join(root, 'assets', filename), body)
                html = html.replace(
                    tag,
                    `<script${attributes} src="/docs/assets/${filename}"></script>`,
                )
            }
            await writeFile(path, html)
        }
    }
}
await processDirectory(root)
console.log('Documentation boot scripts externalized for the application CSP.')
