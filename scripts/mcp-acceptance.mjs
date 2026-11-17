import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StreamableHTTPClientTransport } from '@modelcontextprotocol/sdk/client/streamableHttp.js';
import { readFile, writeFile } from 'node:fs/promises';
import { pathToFileURL } from 'node:url';
import assert from 'node:assert/strict';

export async function runAcceptance(configPath = '.local/mcp-acceptance.json', evidencePath = '.local/mcp-acceptance-result.json') {
  const config = JSON.parse(await readFile(configPath, 'utf8'));
  const endpoint = new URL(config.url);
  assert.ok(['localhost', '127.0.0.1', '[::1]'].includes(endpoint.hostname), 'Use a local acceptance server.');
  const client = new Client({ name: 'careeratlas-acceptance', version: '0.1.0' });
  const transport = new StreamableHTTPClientTransport(endpoint, { requestInit: { headers: { Authorization: `Bearer ${config.token}` } } });
  const trace = [];
  const call = async (name, args = {}, expectError = false) => {
    const start = Date.now();
    const response = await client.callTool({ name, arguments: args });
    trace.push({ operation: name, isError: !!response.isError, elapsed_ms: Date.now() - start });
    assert.equal(!!response.isError, expectError, `Unexpected result for ${name}: ${JSON.stringify(response.content)}`);
    if (expectError) return response;
    return response.structuredContent?.data ?? JSON.parse(response.content[0].text).data;
  };
  try {
    await client.connect(transport);
    const { tools } = await client.listTools();
    assert.equal(tools.length, 41);
    const profile = await call('get_profile');
    await call('update_profile', { expected_version: profile.version, display_name: 'Alex Morgan', biography: 'Fictional MCP acceptance portfolio.' });
    const project = await call('create_project', { title: 'Atlas Notes — MCP acceptance', type: 'personal', role: 'Frontend developer', team_outcome: 'The fictional team shipped search.' });
    let experience = await call('create_experience', { project_id: project.id, title: 'Accessible project search' });
    await call('confirm_experience', { id: experience.id, expected_version: experience.version }, true);
    experience = await call('update_experience', { id: experience.id, expected_version: experience.version, record_type: 'work_contribution', occurrence: { start: '2026-10', precision: 'month', ongoing: false }, personal_action: 'I built keyboard navigation and component tests.', private_notes: 'PRIVATE_MCP_SENTINEL' });
    const skill = await call('create_skill', { name: 'Vue 3' });
    experience = await call('attach_skill', { id: experience.id, skill_id: skill.id, expected_version: experience.version });
    experience = await call('create_evidence', { experience_id: experience.id, expected_version: experience.version, kind: 'document', label: 'Fictional test notes', url: 'https://example.com/tests' });
    experience = await call('confirm_experience', { id: experience.id, expected_version: experience.version });
    const selection = { profile_fields: ['display_name', 'biography'], items: [{ experience_id: experience.id, fields: ['title', 'occurrence', 'personal_action', 'skills', 'evidence'], project_fields: ['title', 'role'] }] };
    let preview = await call('preview_publication', selection);
    let publication = await call('publish_publication', { ...selection, preview_digest: preview.digest, idempotency_key: `mcp-${crypto.randomUUID()}` });
    const original = await call('get_public_portfolio', { slug: publication.slug });
    assert.deepEqual(original.items, preview.portfolio.items);
    assert.ok(!JSON.stringify(original).includes('PRIVATE_MCP_SENTINEL'));
    assert.ok(!JSON.stringify(original).includes('The fictional team shipped search.'));
    await call('get_public_citation', { slug: publication.slug, id: experience.id });
    await call('get_public_skill', { slug: publication.slug, id: skill.id });
    experience = await call('update_experience', { id: experience.id, expected_version: experience.version, personal_action: 'I improved keyboard focus handling and tests.' });
    experience = await call('confirm_experience', { id: experience.id, expected_version: experience.version });
    assert.deepEqual((await call('get_public_portfolio', { slug: publication.slug })).items, original.items);
    const revised = { ...selection, publication_id: publication.id };
    preview = await call('preview_publication', revised);
    publication = await call('republish_publication', { ...revised, expected_version: publication.version, preview_digest: preview.digest, idempotency_key: `mcp-${crypto.randomUUID()}` });
    assert.equal((await call('get_public_portfolio', { slug: publication.slug })).items[0].personal_action, experience.personal_action);
    await call('revoke_experience_access', { id: experience.id, expected_version: experience.version });
    assert.equal((await call('get_public_portfolio', { slug: publication.slug })).items.length, 0);
    await call('get_public_citation', { slug: publication.slug, id: experience.id }, true);
    await call('revoke_publication', { id: publication.id, expected_version: publication.version });
    await call('get_public_portfolio', { slug: publication.slug }, true);
    const result = { passed: true, protocol: 'MCP Streamable HTTP via official TypeScript SDK', tool_count: tools.length, trace, completed_at: new Date().toISOString() };
    await writeFile(evidencePath, JSON.stringify(result, null, 2) + '\n');
    return { passed: true, tool_count: tools.length, calls: trace.length, evidencePath };
  } finally { await client.close(); }
}

if (typeof process !== 'undefined' && process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  console.log(JSON.stringify(await runAcceptance(process.argv[2], process.argv[3]), null, 2));
}
