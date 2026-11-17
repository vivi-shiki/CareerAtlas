# P0 Publication Assistant: Design and Acceptance

Status: gates 9-10 complete with local implementation and acceptance evidence. This document covers the public `portfolio_qa` mode only.

## Integration decision

The existing Laravel application owns publication snapshots, field selection, source access epochs, cookie sessions, a database queue and the public Vue portfolio. Keep those authorization decisions in the application. A model may request a scoped search or read; it never receives owner MCP credentials or chooses a principal.

| Approach | Verified upstream behavior | Decision for this repository |
| --- | --- | --- |
| Laravel AI SDK | Its current manifest supports PHP 8.3 and Laravel 13, but conflicts with Laravel MCP below 1.0. It offers agents, tools and queued/streamed responses. | Reconsider after a deliberate MCP upgrade; the accepted transport currently uses MCP 0.5. |
| Prism | Supports the current PHP/Laravel versions and provides provider adapters and bounded tool loops. | A suitable future adapter when multiple providers are required. Application authorization and run lifecycle would still be necessary. |
| Google ADK | Provides agent workflow/session tooling in several runtimes; PHP is not listed. | Avoid introducing a second application runtime for two read-only tools. Borrow explicit run state, bounded execution and separate evaluation. |
| Application loop with a thin HTTP adapter | Uses existing Laravel HTTP, queue, validation and test facilities. | Selected for P0: a small transport interface, two retrieval tools and one structured terminal tool. No new agent dependency. |

This decision is an architectural inference from the repository and upstream sources, not an upstream recommendation. Sources: [Laravel manifest](https://github.com/laravel/ai/blob/master/composer.json), [Laravel AI SDK](https://laravel.com/docs/13.x/ai-sdk), [Prism manifest](https://github.com/prism-php/prism/blob/main/composer.json), [Prism tools](https://prismphp.com/core-concepts/tools-function-calling/), [ADK documentation](https://google.github.io/adk-docs/). Concrete runtime provider/model/project settings remain in ignored local documentation.

## Data and lifecycle

- `agent_conversations`: UUID, random browser-principal hash, publication slug, current authorized projection hash, creation/expiry times. Conversations expire after 24 hours.
- `agent_runs`: UUID, conversation ID, client idempotency UUID, question, state, progress label, validated answer, safe error code, tool metadata, available token usage, creation/start/finish/deadline times. A run is the persisted question/answer turn.
- States: `queued -> running -> completed | failed | cancelled | expired`. A terminal state never becomes running again. Retrying a failed question creates a new run and request UUID.
- Only one active run per conversation. A duplicate request UUID with identical input returns the same run; changed input returns a conflict. Database locks/constraints enforce this.
- Browser principal, publication slug and scope hash are checked on every create/read/cancel request. Polling or continuing after a scope change returns no old question, answer or source. Expired rows are removed by a daily scheduled cleanup command.
- Reauthorize before every provider call, tool execution and final write. History includes at most ten completed turns from the same unchanged scope; provider-side stored conversations are not used.
- Cancellation stops subsequent calls and suppresses a late answer. An already running remote request may still incur usage; retain returned usage metadata without exposing intermediate reasoning.

## Transport and bounds

All routes use same-origin cookies, existing CSRF protection for writes and `no-store` responses:

| Route | Purpose |
| --- | --- |
| GET `/api/public/{slug}/agent` | Availability and input limits; no credentials/model details |
| POST `/api/public/{slug}/agent/conversations` | Create a browser-bound conversation |
| GET `/api/public/{slug}/agent/conversations/{id}` | Authorized recent turns |
| POST `/api/public/{slug}/agent/conversations/{id}/runs` | Queue a question with `request_id` |
| GET `/api/public/{slug}/agent/runs/{id}` | Poll safe state, answer, usage and errors |
| POST `/api/public/{slug}/agent/runs/{id}/cancel` | Idempotent cancellation |

Default limits: 2,000 question characters, four tool calls including the terminal call, four provider steps, 45 seconds including queue delay, 2,000 output tokens per provider call, bounded search/read output, ten recent turns, session/IP request limits. Configuration may reduce these limits. An optional operator publication allowlist supports a restricted rollout. No automatic paid-provider fallback. Disabled/unconfigured AI does not affect manual browsing.

`search_experiences(query)` searches only the current public projection, including selected profile fields. It returns bounded field excerpts and opaque source handles. `get_experience(experience_id)` reads selected fields of one currently permitted published record. `finish_answer(answer, citations, insufficient_information, follow_up_questions)` is a terminal schema, not a business mutation. Citation handles and exact excerpts must have been returned by a tool in this run and still be authorized. Each citation resolves to a snapshot version, record, field and excerpt. A substantive answer needs citations; an explicit information gap may have none. Source-ID validation cannot prove semantic entailment; the evaluation below separately checks it.

The adapter preserves opaque model content/signature metadata across function-call exchanges and matches function call IDs. It does not fetch evidence URLs, enable web search, execute arbitrary SQL/files, reveal private notes, or expose the owner operation catalog. Source content and questions are untrusted data. See the [content generation API](https://ai.google.dev/api/generate-content) for the wire protocol; provider-specific operational choices stay local.

Runtime review found a timezone mismatch between application and database defaults. Agent timestamps now include explicit offsets when written or compared, including cleanup queries; an integration test asserts the actual deadline interval. This avoids expiring queued work immediately in deployments whose database session timezone differs from the application.

## UI and interaction test design

Keep the accepted white/mint portfolio and right-hand question panel. On narrow screens it follows the portfolio. Do not replace the project presentation with a full-page chat.

| Test group | Interaction and assertion |
| --- | --- |
| AGENT-01 | Availability off: clear notice, projects and source dialogs remain usable; no question request |
| AGENT-02 | Suggestion populates editable input; blank/over-limit input blocked; Enter submits, Shift+Enter adds a line, IME composition never submits |
| AGENT-03 | Submit once: question retained, visible queued/searching/writing status, disabled duplicate submit, cancellable active run; no fabricated preview answer |
| AGENT-04 | Completed answer: plain escaped text, information-gap wording, exact source cards, follow-up suggestions; source opens authorized record dialog and restores keyboard focus |
| AGENT-05 | Cancel while queued/running: terminal cancelled status; no late answer appears; manual navigation works |
| AGENT-06 | Provider/network/rate-limit/deadline failure: input and earlier answers retained, explicit retry, new run on provider retry and stable request ID on ambiguous submission retry |
| AGENT-07 | Revoke/delete/republish during generation or history retrieval: old answer/source cards cleared; no subsequent source disclosure |
| AGENT-08 | Another browser or publication cannot read, submit to or cancel a conversation/run |
| AGENT-09 | Repeated questions preserve same-scope history, bounded retention; refresh can recover conversation in the same tab; clear conversation starts fresh |
| AGENT-10 | Desktop/mobile, sparse/dense/long answers, keyboard focus/live regions, no overflow; existing layout and manual workflow remain intact |

Backend tests cover all state transitions, idempotency/concurrency constraints, source filtering before search, cross-browser scope, latest-private-edit exclusion, revoked sources at each boundary, malformed model output, invented/mismatched citations, prompt-injection records, tool/step/time limits, provider errors, cancellation, usage and expiry. Browser tests use an explicitly configured testing-only provider double for deterministic orchestration; live-model evaluation is reported separately.

## Live acceptance

Run at least 20 fixed fictional cases: correct retrieval, absent skills/dates/metrics, personal versus team attribution, unread evidence URLs, private fields, other publications, instruction injection and multilingual questions. Preserve raw outcomes in ignored local artifacts and publish aggregate counts. Gate: zero private disclosures/invented sources/fabricated experience; at least 90% correct sources on answerable cases. Review semantic support manually. Also run the full backend/browser suites, an actual browser question/source/cancel flow and compare desktop/mobile screenshots with the accepted portfolio reference. Passing mocks alone does not satisfy gate 10.

See [Agent tools](KB_CareerAtlas_AgentTools.md), [Testing](KB_CareerAtlas_Testing.md) and the [execution ledger](../TaskLogs/2026-11-18-p0-implementation.md).

## Implementation and review evidence

The implementation uses `app/Agent` for transport, projection-based retrieval and bounded orchestration; `app/Jobs/RunPortfolioAgent.php` for queue execution; `AgentController` for browser-bound HTTP; and `AgentPanel.vue` for the public UI. Twenty-four PostgreSQL-backed Agent tests and twelve browser tests cover the groups above. The browser suite additionally tests ambiguous submission recovery, profile-citation version drift, escaped long output and the mobile Ask jump link. Controlled model-output fixtures are labeled in the tests; they are not counted as live model evidence.

The live-model browser walkthrough completed question submission, operational progress, a cited answer, current-source dialog, keyboard focus return, a missing-date question and cancellation. Seven screenshots showed no page errors or horizontal overflow. A final controlled layout fixture passed at 1487, 768, 390 and 320 pixels; desktop and narrow mobile images were visually inspected against the reference. The controlled fixture is visual evidence only, separate from the live-model workflow.

| Visual review point | Finding / resolution |
| --- | --- |
| Page hierarchy | Brand/header, profile, selected work and right-hand Q&A retain the reference order and divider structure |
| Palette and typography | White canvas, emerald actions and mint question/source surfaces retained; desktop answer/source typography increased to match the reference's hierarchy |
| Source cards | Exact excerpts are visible with human-readable field labels; internal dotted field paths are not product labels |
| Input and states | Multiline input, explicit Send/Cancel/Retry and character count extend the one-line mockup for the required keyboard and failure behavior |
| Mobile ordering | Work and evidence precede Q&A; an Ask navigation link jumps directly to the panel; source dialogs remain keyboard accessible |
| Content extremes | Real multi-record portfolios, long escaped answers and multiple turns grow vertically without horizontal overflow; a separate controlled visual fixture supports direct reference comparison |
| Privacy and recovery | No fabricated answer during loading; version/scope changes clear old answers or reject stale source views |

Current live evaluation: 20 fixed fictional questions, 19 initial completions and one explicit retry after an upstream 503; 20 final completions, 12/12 expected sources for answerable cases and no observed private disclosures, invented sources or fabricated experience. Detailed answers and machine-specific artifacts remain local. The [testing report](KB_CareerAtlas_Testing.md) records the limitations and prior-round findings.
