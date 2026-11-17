# CareerAtlas Agent Tools and Runtime

Status: P0 contracts implemented; final acceptance is tracked in the [execution ledger](../TaskLogs/2026-11-18-p0-implementation.md). P0 supports only public `portfolio_qa`. Runtime provider/model settings remain local. See the [implementation decision and interaction tests](KB_CareerAtlas_Agent_P0.md).

## Modes

| Mode | Scope | Output | Phase |
| --- | --- | --- | --- |
| portfolio_qa | One permitted publication | Answer, citations, information gaps | P0 |
| experience_capture | Owner-selected project/context | Follow-up questions, editable draft, missing fields | P1 |
| interview_prep | Owner's records and private preparation | Example suggestions, drafts, expression feedback | P1 |
| opportunity_curate | Owner's records and one opportunity | Evidence mapping and content suggestions | P1 |
| team_review | Current tenant and explicit content grants | Cited summary and discussion prompts | P2 |

## Tool contracts

### search_experiences

- P0 input: `query` (up to 200 characters). Skill/date questions use text retrieval; structured filters remain a later extension.
- Server context: principal, tenant when applicable, publication/version, allowed fields.
- Output: up to 16 authorized field excerpts, source handles and version IDs, including selected profile fields. Excerpts are capped at 1,000 characters.
- Filter access before retrieval/ranking and before counts; do not retrieve everything and ask the model to hide private data.

### get_experience

- P0 input: `experience_id` from the scoped search.
- Output: up to 20 selected field excerpts, user-entered evidence/provenance and source version; at most 1,000 characters per excerpt.
- Recheck authorization independently; a returned ID is not a reusable permission token.
- In public mode return the publication's version, not the latest private revision.

### get_project_context (P1)

- Input: `project_id`.
- Output: permitted project context and relevant existing records for the owner.
- The model cannot change owner or organization scope.

### validate_experience_draft (P1)

- Input: candidate fields.
- Output: required-field gaps, format errors, and items requiring owner clarification.
- Validation does not verify the truth of employment or outcomes.

## Response contract

Return `answer`, `citations`, `insufficient_information`, and optional `follow_up_questions`. Capture mode may include `draft`. Each citation identifies a record version, field, and short excerpt using server-validated references.

Every verifiable experience claim needs supporting records. Existence checks on citation IDs are necessary but do not prove semantic support; evaluate claim faithfulness separately. Linked code is not read merely because its URL is stored.

## Bounded loop

Authorize -> search -> optionally read -> generate -> validate citations and current access -> respond. Implemented bounds are at most four tool calls (including `finish_answer`), four provider steps, a 45-second overall deadline, a 2,000-character input, and the latest ten authorized completed turns. The last available step exposes only the terminal schema. A refusal or information gap may finish without an unrelated source lookup; substantive claims still require retrieved citations.

Use session/IP limits for P0 visitors: ten questions per browser principal per ten minutes and thirty per IP per hour, with separate conversation-creation limits. Keep the runtime cache persistent. Output is capped at 2,000 tokens per call. An optional publication allowlist restricts staged rollout. On cancellation do not start additional tools; record any already-incurred usage accurately.

## Failure and isolation

- Timeouts and provider failures leave manual editing and browsing available.
- Retry runs have new run IDs and cannot duplicate saved business records.
- P0 exposes no arbitrary SQL, file access, external browsing, or mutation tools.
- Credentials stay server-side. Log run metadata without full private source payloads.
- Data inside records, pasted requirements, or tool results never overrides system instructions.
- Do not cache conversations across principals or scopes. Reauthorize historical context after revocation.
- Show operational progress labels and source links, not private chain-of-thought.

See [Authorization](KB_CareerAtlas_Design_Authorization.md) and [Testing](KB_CareerAtlas_Testing.md).
