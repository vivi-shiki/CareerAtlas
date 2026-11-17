// Render design documentation, not the CareerAtlas application.
// Usage: node render-diagrams.cjs <directory-containing-node_modules>
const fs = require('node:fs/promises');
const path = require('node:path');
const { createRequire } = require('node:module');

async function main() {
  const dependencyRoot = process.argv[2];
  if (!dependencyRoot) throw new Error('Provide the documentation tooling directory.');
  const toolRequire = createRequire(path.resolve(dependencyRoot, 'package.json'));
  const { instance } = toolRequire('@viz-js/viz');
  const sharp = toolRequire('sharp');
  const viz = await instance();
  const directory = path.join(__dirname, 'flows');
  const names = (await fs.readdir(directory)).filter(name => name.endsWith('.dot')).sort();
  const manifest = [];
  for (const name of names) {
    const source = await fs.readFile(path.join(directory, name), 'utf8');
    const svg = viz.renderString(source, { format: 'svg', engine: 'dot' });
    const stem = name.slice(0, -4);
    await fs.writeFile(path.join(directory, stem + '.svg'), svg);
    await sharp(Buffer.from(svg), { density: 144 }).png().toFile(path.join(directory, stem + '.png'));
    const { width, height } = await sharp(path.join(directory, stem + '.png')).metadata();
    manifest.push({ file: 'flows/' + stem + '.png', width, height });
  }
  for (const name of (await fs.readdir(path.join(__dirname, 'mockups'))).filter(name => name.endsWith('.png')).sort()) {
    const { width, height } = await sharp(path.join(__dirname, 'mockups', name)).metadata();
    manifest.push({ file: 'mockups/' + name, width, height });
  }
  await fs.writeFile(path.join(__dirname, 'asset-manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  console.log(JSON.stringify(manifest));
}
main().catch(error => { console.error(error); process.exitCode = 1; });
