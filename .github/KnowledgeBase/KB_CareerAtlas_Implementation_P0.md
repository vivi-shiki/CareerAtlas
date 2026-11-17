# P0 Implementation Design and Coverage Review

Status: gates 1-3 passed, 2026-11-18. Later frontend query refinements pass 43 PostgreSQL tests / 555 assertions. The task ledger records actual acceptance evidence. P1/P2 features retain their existing scope and are not pulled into P0.

## Delivery gates

1. Define data types, tables, application structure, atomic operations, backend tests, and MCP contracts.
2. Review those definitions against every P0 requirement and UX branch; fill coverage gaps before implementation.
3. Implement the backend, pass PostgreSQL tests, and execute a real MCP client workflow.
4. Build static Vue layouts against the three approved mockups; inspect screenshots with empty, normal, and dense data.
5. Write browser tests for each non-AI UX action and its database effects.
6. Review frontend test completeness and add missing branches.
7. Connect all non-AI business interactions to the backend.
8. Pass both test suites and perform the full workflow in a browser.
9. Research Agent integration, then specify AI interaction states, tools, and tests.
10. Implement and evaluate P0 portfolio Q&A without diverging from the design.

## Stack and application structure

Use PHP 8.3+, Laravel 13, PostgreSQL 16+, Vue 3/TypeScript, and a same-origin JSON API. A JSON API makes HTTP, browser, and MCP acceptance share explicit business contracts; there is no second application backend. Exact installed package versions are locked. Vue's design basis remains shadcn-vue/Tailwind; implementation follows the existing UI specification. Backend verification uses PHPUnit and real PostgreSQL. Browser verification uses Playwright.

- `app/Domain/`: backed enums, validated input objects, and business services. Services own transactions, version checks, and domain invariants.
- `app/Models/`: persisted entities and casts; no public serialization of full models.
- `app/Http/Controllers/`: thin HTTP adapters over domain operations.
- `app/Http/Middleware/`: owner authentication and the consistent API error boundary.
- `app/Mcp/`: authenticated owner-operation tools for developer acceptance; these are separate from the public agent's read-only tool allowlist.
- `app/Services/PublicationProjection.php`: the only preview/public/citation/count projection path.
- `database/migrations/`, `database/seeders/`: reproducible schema and clearly fictional seed records.
- `tests/Feature/`: database-backed business and transport contract tests; `tests/Unit/`: value/validation contracts.
- `resources/js/`: typed API client, shared portfolio renderer, owner pages, public pages, and UI components.
- `scripts/`: reproducible MCP acceptance and later browser/evaluation runners.

## Data types

Use UUIDs for business records, integer user IDs from Laravel identity, integer monotonically increasing edit versions, UTC timestamptz for events, and JSONB for immutable version/projection documents. API IDs are strings. Text is Unicode; optional unknown facts are null/empty, not invented values.

| Type | Allowed values and semantics |
| --- | --- |
| ProjectType | commercial, personal, learning |
| RecordType | work_contribution, learning, problem_solving |
| ContentStatus | draft, confirmed; archive timestamp is an independent lifecycle flag |
| Provenance | manual, ai_assisted (no AI writes in non-AI gates) |
| EvidenceKind | repository, demo, document, statement |
| EvidenceProvenance | supplied_link, owner_statement; P0 cannot claim reviewer confirmation |
| DatePrecision | year, month, day; validate YYYY, YYYY-MM, YYYY-MM-DD without synthesizing a day |
| Occurrence | start plus precision; optional end with precision; ongoing flag; end cannot precede start |
| PublicationState | published, revoked |
| Public field selections | Explicit allowlists; private notes, owner IDs, audit data, and credentials are never selectable |

## Relational schema

Every mutable business row has created_at/updated_at; ownership is always derived from the authenticated principal. UUID references, owner scoping, unique constraints, and transactions prevent cross-owner relationships. JSONB payloads are versioned application contracts, not unvalidated arbitrary model output.

| Table | Columns and constraints |
| --- | --- |
| users | bigint id PK; name; unique email; hashed password; remember token; timestamps |
| profiles | UUID id; unique user_id FK; display_name varchar(120); direction varchar(240); biography text; contact_links JSONB; preferred_language enum-like string; version integer >=1 |
| projects | UUID id; owner_id FK; title varchar(200); type; role varchar(160); background/team_outcome text; occurrence JSONB nullable; version >=1; archived_at/deleted_at nullable; owner/title indexes |
| experiences | UUID id; owner_id FK; project_id FK; nullable draft title/record_type/occurrence/personal_action/result/validation/remaining_work/private_notes; provenance; status; version >=1; confirmed_version_id nullable FK; access_epoch >=1; archived_at/deleted_at nullable; owner/project indexes |
| record_versions | UUID id; experience_id FK; unique(experience_id, number); number; immutable payload JSONB; confirmed_at; payload includes skills/evidence at confirmation |
| skills | UUID id; owner_id FK; name; normalized_name; edit version; unique(owner_id, normalized_name) |
| skill_aliases | UUID id; owner_id FK; skill_id FK; normalized_alias; unique(owner_id, normalized_alias); aliases cannot collide with another canonical name |
| experience_skill | experience_id/skill_id FKs; unique pair; associations count distinct records/projects |
| evidence | UUID id; experience_id FK; kind; label; URL nullable; excerpt nullable; provenance; version; no network fetch |
| publications | UUID id; owner_id FK; unique unguessable slug; state; version; active_snapshot_id nullable; published_at/revoked_at |
| publication_snapshots | UUID id; publication_id FK; unique(publication_id, number); number; profile projection JSONB; selection/source_versions JSONB; created_at; append-only |
| publication_items | UUID id; snapshot_id FK; experience_id/project_id/record_version_id FKs; source_access_epoch; sort_order; projected payload JSONB; unique(snapshot_id, experience_id) |
| idempotency_keys | UUID id; owner_id FK; operation; key; request_hash; response JSONB; unique(owner_id, operation, key) |
| audit_events | UUID id; actor_id nullable; action; subject_type/id; metadata JSONB limited to IDs/versions; created_at |
| sessions | Laravel session store; owner login, CSRF and visitor identity; opaque session credential |

Agent sessions/runs are added at gates 9-10 with server-controlled principal/publication scope, input limits, cancellation, source-version handles, reauthorization, and redacted usage. Do not expose developer mutation tools to public Q&A.

## Revision and publication invariants

Editing confirmed experience content, skills, or evidence creates a new draft edit version. Confirmation validates the exact current version and creates one immutable record version; repeating confirmation of that version returns the same result. Publishing chooses confirmed versions and explicit fields. A preview digest covers the full projected snapshot; publish recomputes it under transaction locks and rejects stale previews. Publication replay requires an idempotency key; the same key with different input is a conflict.

An active publication references an immutable snapshot. Editing source/profile/project data does not alter it. Explicit republication creates a new snapshot and switches the active pointer. Archive does not revoke online access. Deletion makes dependent items unavailable; withdrawing a source's outward authorization increments its access epoch so old items stop resolving. Public pages, counts, skills, citations, retrieval, and final agent responses all use the same current-authorization filtering. A newly reviewed publication may explicitly include the source again.

## Atomic operation inventory

Each mutation validates input, derives owner, locks current rows, checks expected versions, commits associated changes and audit metadata together, and returns a stable result. HTTP and developer MCP invoke the same services. Read list operations paginate with bounded page size and deterministic ordering. Every owner object lookup hides foreign/missing objects with the same 404 contract.

| Group | Atomic operations | Key acceptance checks |
| --- | --- | --- |
| Identity | register, login, logout, current_user | Unique email, hashed password, invalid credentials, CSRF, session regeneration, logout invalidation |
| Profile | get_profile, update_profile | Required name, safe contact URLs, expected version, foreign owner ignored/rejected |
| Projects | list_projects, get_project, create_project, update_project, archive_project, restore_project, delete_project | Required title/type/role, precision-aware date range, filters/pagination, own scope, archive versus deletion |
| Experiences | list_experiences, get_experience, create_experience, update_experience, confirm_experience, archive_experience, restore_experience, delete_experience, revoke_experience_access | Incomplete drafts, exact confirmation requirements, immutable version, conflict, replay, dependent public invalidation |
| Skills | list_skills, get_skill, create_skill, update_skill, add_skill_alias, delete_skill_alias, attach_skill, detach_skill | Case/whitespace normalization, alias collision, unique relationships, confirmed-association semantics, scoped distinct counts |
| Evidence | list_evidence, create_evidence, update_evidence, delete_evidence | Kind/provenance consistency, safe URL schemes, version conflict, no false verification, source draft invalidation |
| Publication | preview_publication, publish_publication, list_publications, get_publication, republish_publication, revoke_publication | Exact projection, stale preview, idempotency, immutable snapshot, missing/foreign/unconfirmed sources, revocation |
| Public browsing | get_public_portfolio, get_public_project, list_public_skills, get_public_skill, get_public_citation | Selected snapshot only; no private existence/count/snippet leaks; live source/publication authorization |

## HTTP and MCP contracts

Use same-origin cookie authentication for the browser; write requests require CSRF. Provide a user-bound, scoped developer token for MCP with a separate authenticated endpoint. Never accept owner/user IDs from tool arguments as authority. The developer MCP server exposes named operations with JSON schemas and returns structured results and tool errors. Integration uses the official Laravel MCP package and a real protocol client; do not call a plain REST script an MCP acceptance run.

P0 public Agent tools are separately limited to search_experiences and get_experience. MCP mutation support for developer acceptance does not expand the Agent's permissions.

API errors contain `code`, `message`, and `errors` where relevant. Use 401 unauthenticated, uniform 404 unavailable, 422 invalid fields, 409 version/idempotency/preview conflict, 429 rate limit, and 503 unavailable dependency. Avoid echoing secrets or full private payloads in errors/logs.

## Completeness review before backend implementation

| Requirement / branch | Required backend evidence | Later evidence |
| --- | --- | --- |
| S1-01 profile and contact selection | Profile validation/version tests; omitted contacts absent from snapshot | Profile browser edit and publication preview |
| S1-02 projects and imprecise dates | CRUD, enum, date/range/ongoing, filtering and pagination tests | Create/edit/archive/restore flows |
| S1-03 personal versus team actions | Separate persistence and independent projection fields | Editor labels and later AI attribution evaluation |
| S1-04 / S3-03 evidence | URL/provenance/ownership/version tests, no outbound request | Evidence add/edit/remove and error states |
| S1-05 / S3-02 skills | Alias normalization, duplicate/foreign association and public count tests | Skill navigation and hidden-record counts |
| S3-01 drafts and confirmation | Incomplete save, each missing field, exact version, immutable confirmed copy | Draft/error/focus/unsaved/conflict behavior |
| S1-06 preview/publication | Field allowlist, exact preview, idempotency, stale digest, immutable edits, explicit republish | Preview equals public renderer; publish/revoke workflow |
| T03/T04 authorization | Cross-owner read/write/relationships, anonymous private endpoints, uniform errors | Authenticated and anonymous browser contexts |
| T06/T10 consistency | Repeated actions, stale writes, rollback, database uniqueness | Double submit and conflicting sessions |
| Revocation/deletion/archive | Every public read path; archive preserves snapshots, delete/epoch revoke removes them | Owner warnings and unavailable visitor page |
| T01/T02/T05/T07/T11 | Real PostgreSQL assertions, no SQLite substitute | MCP full workflow and browser persistence checks |
| T08/T09/T12, S1-07 | Agent session/current scope/failure tests at gates 9-10 | AI browser flow and synthetic live evaluation |

Review findings resolved in this baseline: explicitly distinguish project and record types, model date precision without fake days, separate archive/delete/revoke, include skill/evidence mutations in revision invalidation, add preview staleness protection, bind MCP identity server-side, cover public counts/citations, and preserve old snapshots under source edits. Review is a design check; executable tests and implementation evidence are still required.

## Primary references

- [Laravel installation](https://laravel.com/docs/13.x/installation)
- [Laravel MCP](https://laravel.com/docs/13.x/mcp)
- [MCP specification](https://modelcontextprotocol.io/specification/2025-11-25)
- [Functional requirements](KB_CareerAtlas_FunctionalRequirements.md), [Acceptance](KB_CareerAtlas_Testing.md), [UI/UX](KB_CareerAtlas_Design_UIUX.md)

## Executable coverage review

The machine-readable catalog in `contracts/operations.json` defines 36 owner operations and five public-read operations. Identity uses dedicated session endpoints. `OperationDispatcher` binds the principal, validates the exact schema, and serializes owner operations in a database transaction; server-owned identifiers are not accepted in input. The developer MCP server exposes these operations through the official Laravel MCP transport. The public AI tool surface remains separate and unimplemented at this gate.

`PortfolioWorkflowTest` covers the non-AI feature inventory, revision/precision constraints, immutable snapshots, field projection, current public authorization, aliases/counts, and rollback. `IdentityTest` covers registration/login/logout. `McpContractTest` covers protocol initialization, schema discovery, tool results, and scopes. `TransportSecurityTest` exercises CSRF outside Laravel's default test bypass, credential revocation, hostile Origin rejection, and no-store responses.

Review corrections after the initial tests: skill renames/aliases need expected versions; project/profile changes need unpublished-change indicators; mixed date precisions compare possible interval endpoints without changing stored precision; draft fields may be null; public reads need MCP tools to exercise the whole workflow without substituting REST for MCP. JSON object key order is not part of snapshot equality. An invalid extra field on a read request is a schema error, so ownership tests send a valid read shape before comparing foreign/missing responses.

Remaining gates explicitly include rendered UX validation, browser/database interaction coverage, Agent session/citation/in-flight revocation tests, and live synthetic Agent evaluation. Passing these backend tests does not prove those later requirements.
