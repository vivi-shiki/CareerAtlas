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
    const catalog = Object.assign({}, ...await Promise.all(['contracts/operations.json', 'contracts/operations.p1.json'].map(async path => JSON.parse(await readFile(path, 'utf8')))));
    assert.deepEqual(tools.map(tool => tool.name).sort(), Object.keys(catalog).sort());
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
    const capture = await call('create_capture', { project_id: project.id, notes: 'I added search keyboard tests.', draft: { title: 'Search follow-up', personal_action: 'I added search keyboard tests.' } });
    const candidates = await call('find_capture_duplicates', { id: capture.id });
    assert.ok(candidates.some(candidate => candidate.id === experience.id));
    const captured = await call('apply_capture', { id: capture.id, expected_version: capture.version });
    assert.equal(captured.status, 'draft');
    assert.equal(captured.provenance, 'manual');
    let ownerAiAccepted = false;
    const ownerStatus = await call('owner_ai_status');
    if (process.env.MCP_OWNER_AI_ACCEPTANCE === '1') {
      assert.equal(ownerStatus.available, true, 'Owner AI acceptance requires an explicitly enabled synthetic test environment.');
      const aiCapture = await call('create_capture', { project_id: project.id, notes: 'I added keyboard navigation tests.' });
      let run = await call('start_owner_ai', { purpose: 'capture', subject_id: aiCapture.id, expected_version: aiCapture.version, request_id: crypto.randomUUID() });
      const deadline = Date.now() + 50000;
      while (['queued', 'running'].includes(run.state) && Date.now() < deadline) {
        await new Promise(resolve => setTimeout(resolve, 900));
        run = await call('get_owner_ai', { id: run.id });
      }
      assert.equal(run.state, 'completed');
      const applied = await call('apply_owner_ai', { id: run.id });
      assert.equal(applied.subject.provenance, 'ai_assisted');
      assert.equal(applied.subject.applied_experience_id, null);
      run = await call('start_owner_ai', { purpose: 'capture', subject_id: aiCapture.id, expected_version: applied.subject.version, request_id: crypto.randomUUID() });
      assert.equal((await call('cancel_owner_ai', { id: run.id })).state, 'cancelled');
      ownerAiAccepted = true;
    }
    assert.ok((await call('get_experience_history', { id: experience.id })).length >= 3);
    const preparation = await call('create_preparation', { topic: 'Frontend engineering', requirements: 'Explain accessible interaction design.', target_language: 'ja', experience_ids: [experience.id] });
    let answer = await call('create_prepared_answer', { preparation_id: preparation.id, question: 'What did you contribute?', answer: 'I improved keyboard focus handling and tests.' });
    answer = await call('confirm_prepared_answer', { id: answer.id, expected_version: answer.version });
    assert.equal((await call('get_answer_history', { id: answer.id })).length, 2);
    let practice = await call('start_practice', { preparation_id: preparation.id, questions: ['What did you contribute?', 'How did you validate it?'] });
    practice = await call('answer_practice', { id: practice.id, expected_version: practice.version, answer: 'I built keyboard navigation.' });
    practice = await call('next_practice', { id: practice.id, expected_version: practice.version });
    assert.equal(practice.current_index, 1);
    practice = await call('stop_practice', { id: practice.id, expected_version: practice.version });
    practice = await call('save_practice', { id: practice.id, expected_version: practice.version });
    assert.equal(practice.retained, true);
    let opportunity = await call('create_opportunity', { title: 'Fictional opportunity', direction: 'Frontend engineer', requirements: 'PRIVATE_REQUIREMENT_SENTINEL', selection, target_language: 'en' });
    const opportunityPreview = await call('preview_opportunity', { id: opportunity.id });
    opportunity = await call('publish_opportunity', { id: opportunity.id, expected_version: opportunity.version, preview_digest: opportunityPreview.digest, idempotency_key: crypto.randomUUID() });
    const exported = await call('export_opportunity', { id: opportunity.id });
    assert.ok(exported.markdown.includes(experience.personal_action));
    assert.ok(!exported.markdown.includes('PRIVATE_REQUIREMENT_SENTINEL'));
    opportunity = await call('revoke_opportunity', { id: opportunity.id, expected_version: opportunity.version });
    await call('get_public_portfolio', { slug: opportunity.publication.slug }, true);
    await call('get_public_portfolio', { slug: publication.slug });
    await call('revoke_experience_access', { id: experience.id, expected_version: experience.version });
    assert.equal((await call('get_public_portfolio', { slug: publication.slug })).items.length, 0);
    await call('get_public_citation', { slug: publication.slug, id: experience.id }, true);
    await call('revoke_publication', { id: publication.id, expected_version: publication.version });
    await call('get_public_portfolio', { slug: publication.slug }, true);
    const result = { passed: true, protocol: 'MCP Streamable HTTP via official TypeScript SDK', tool_count: tools.length, owner_ai_accepted: ownerAiAccepted, trace, completed_at: new Date().toISOString() };
    await writeFile(evidencePath, JSON.stringify(result, null, 2) + '\n');
    return { passed: true, tool_count: tools.length, calls: trace.length, evidencePath };
  } finally { await client.close(); }
}

if (typeof process !== 'undefined' && process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  console.log(JSON.stringify(await runAcceptance(process.argv[2], process.argv[3]), null, 2));
}
