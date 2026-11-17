# P1 Implementation Contract

Scope: all eighteen P1 functions in S2, S3, and S4. Organizations, recipient grants, reviewer confirmation, and deployment remain outside this implementation. Preserve the existing English/Chinese/Japanese interface and ten-gate development workflow.

## Domain and persistence

| Area | Records and operations | Invariants |
| --- | --- | --- |
| Capture | Private capture drafts, selected project context, editable facts, at most two follow-up questions, duplicate candidates, apply as new or merge into a selected experience | Model output never confirms or publishes; selected skill suggestions require explicit application; merge checks the current target version |
| History | Append-only experience revisions plus existing confirmed versions; owner-only differences | Preserve confirmation/publication history; no public history endpoint; duplicate idempotent confirmation does not create another revision |
| Preparation | Private topic, optional company/date/requirements URL, target language and answer length; selected confirmed source references | URLs are metadata only; no crawling; relevance is explained with source references and gaps, never matching percentages |
| Answers | Editable answer drafts, explicit confirmation, immutable answer versions, source-version freshness | Edits create a new version; changed/unavailable sources are flagged; original answers remain available to their owner |
| Practice | One question at a time, editable current answer, optional feedback, next/stop/save/discard | Only explicitly retained sessions appear in saved practice; discarded practice never becomes an experience; no hiring prediction |
| Opportunities | Private requirements/metadata, ordered field selections, reviewed introduction, independent publication pointer | Opportunity metadata never enters public projections; each page has its own snapshot, order, scope and revoke lifecycle |
| Export | Markdown from the authorized selected publication projection, localized headings, exact source-version references; reviewed translation when requested | No private fields or other opportunities; source withdrawal affects new exports; downloaded copies cannot be recalled |

All owner mutations share the existing transaction/owner lock and expected-version rules. Public readers remain independent. Additive migrations preserve P0 records. Rollback drops only P1 additions after operators have exported any P1 data they intend to retain; never use destructive resets on an existing environment.

## AI boundary

Extend the bounded application-owned model integration instead of introducing a second agent framework. Owner assistance is separate from public Q&A: explicitly selected project/source data, a purpose-specific schema, a bounded asynchronous run, current ownership and source-version rechecks, and operator-controlled availability. Public assistant tools cannot invoke owner operations.

Capture uses the owner's submitted facts and selected project context. Preparation/mapping/answer/introduction/feedback use selected confirmed versions. Exclude private notes by default. Pasted requirements and source text are untrusted data. Tool results cannot broaden scope. Require exact retrieved citations for career claims; describe unknown facts and missing evidence explicitly. Outputs remain editable and require an explicit owner action before being retained or applied. Provider failure/cancellation preserves manual work.

Language is a content setting for generated drafts, distinct from the UI language. Translation must preserve names, numbers, dates, responsibilities and source provenance. Approximate speaking length is guidance, not an audio measurement. No automated employment decision or employer evaluation is produced.

## UI and acceptance

Reuse the existing workspace sidebar, heading, form, list, dialog, evidence, preview, and public reading components. Add Capture, Preparation, and Opportunities routes; history is reached from an experience. Display source freshness and explicit review/apply actions near generated output. Keep manual editing and publication usable without a provider.

Test forbidden/foreign IDs, stale writes, immutable revisions, duplicate application, source changes during generation, discarded practice, independent opportunity pages, export allowlists, prompt injection, missing evidence, and language preservation. Browser acceptance includes full manual and AI-assisted workflows, disabled/failed/cancelled AI, keyboard/source dialogs, and the existing three-language/four-width checks. Fictional live evaluation is separate from deterministic provider tests.
