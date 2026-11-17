# CareerAtlas Agent Tools and Runtime

Status: proposed contracts. Provider and model are not selected. P0 supports only `portfolio_qa`.

## Modes

| Mode | Scope | Output | Phase |
| --- | --- | --- | --- |
| portfolio_qa | One permitted publication or confirmed owner scope | Answer, citations, information gaps | P0 |
| experience_capture | Owner-selected project/context | Follow-up questions, editable draft, missing fields | P1 |
| interview_prep | Owner's records and private preparation | Example suggestions, drafts, expression feedback | P1 |
| opportunity_curate | Owner's records and one opportunity | Evidence mapping and content suggestions | P1 |
| team_review | Current tenant and explicit content grants | Cited summary and discussion prompts | P2 |

## Tool contracts

### search_experiences

- Input: `query`, optional skill and occurrence-time filters.
- Server context: principal, tenant when applicable, publication/version, allowed fields.
- Output: authorized record summaries with source handles and version IDs.
- Filter access before retrieval/ranking and before counts; do not retrieve everything and ask the model to hide private data.

### get_experience

- Input: an `experience_id` or a validated source handle from the scoped search.
- Output: authorized fields, user-entered evidence excerpts, provenance, and source version.
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

Authorize -> search -> optionally read -> generate -> validate citations and current access -> respond. Suggested configurable defaults are at most 4 tool calls, a 45-second overall deadline, a 2,000-character input, and the latest 10 authorized conversation turns.

Use per-user limits for authenticated use and session/IP limits for anonymous use. Set concrete quotas after measuring representative MVP workloads. Bound result size and provider output during implementation. On cancellation do not start additional tools; record any already-incurred usage accurately.

## Failure and isolation

- Timeouts and provider failures leave manual editing and browsing available.
- Retry runs have new run IDs and cannot duplicate saved business records.
- P0 exposes no arbitrary SQL, file access, external browsing, or mutation tools.
- Credentials stay server-side. Log run metadata without full private source payloads.
- Data inside records, pasted requirements, or tool results never overrides system instructions.
- Do not cache conversations across principals or scopes. Reauthorize historical context after revocation.
- Show operational progress labels and source links, not private chain-of-thought.

See [Authorization](KB_CareerAtlas_Design_Authorization.md) and [Testing](KB_CareerAtlas_Testing.md).
