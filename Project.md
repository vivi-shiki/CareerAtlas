# Project Specific Instruction

## System objective

CareerAtlas is an AI-powered career portfolio and skills knowledge system for maintaining evidence-linked professional records, publishing selected project demonstrations, answering questions with traceable sources, and later supporting interview preparation and permission-controlled employee development.

The core relationship is **Person -> Project -> Personal contribution -> Skill -> Evidence**.

Treat CareerAtlas as a standalone product for ongoing personal use and organizational collaboration. Requirements, release scope, and engineering decisions are driven by user workflows and acceptance criteria.

## Current repository state

As of 2026-11-18, the P0 Laravel/PostgreSQL backend, Vue owner/public interfaces, developer MCP and read-only public Agent are implemented. The [implementation ledger](.github/TaskLogs/2026-11-18-p0-implementation.md) records separate backend, browser, visual and live-model acceptance evidence. No cloud deployment is claimed.

## Technology choices

| Layer | Choice | Responsibility |
| --- | --- | --- |
| Backend | PHP / Laravel | Identity, Policies, validation, transactions, publications, agent tools |
| Frontend | Vue 3 / TypeScript | Owner workspace, public portfolio, forms, citations, chat |
| Localization | Vue I18n / shared JSON catalogs | English, Simplified Chinese, Japanese UI and system messages |
| Database | PostgreSQL | Structured records, versioned publications, permission-aware queries |
| AI | Provider-neutral server adapter | Tool calling, structured responses, bounded execution |
| Build tooling | Composer, npm, Vite, vue-tsc and Playwright | Lockfile-based installation, asset/type builds and browser tests |
| Deployment | Configurable hosting | Application runtime, persistent PostgreSQL, HTTPS, health checks, backup and recovery |

Use a single Laravel application with Vue UI as the initial architectural direction. A same-origin deployment is preferred to simplify authentication. Vue uses the same-origin JSON API so browser and MCP workflows share the domain contracts. Do not introduce a separate Node/Python application server without a concrete requirement.

Read [Deployment contract](.github/KnowledgeBase/KB_CareerAtlas_Design_Deployment.md) before infrastructure work. Shared implementation details and portable release procedures belong in Git. Actual cloud/API selections, project identifiers, resource settings, and environment verification belong in ignored `.local/docs/`. This separation does not change the application stack or feature requirements.

## Scope

| Phase | Included | Completion boundary |
| --- | --- | --- |
| P0 | Login, profile, manual projects/experiences, skills, evidence links, publish/revoke, cited portfolio Q&A | A usable owner-to-visitor MVP with authorization tests |
| P1 | AI experience capture, interview preparation, text practice, opportunity-specific pages, Markdown export | Repeated personal use across recording, preparation, and presentation |
| P2 | Organizations, recipient grants, confirmations, team search, development conversations | Explicitly authorized collaboration with tenant isolation |

The five scenarios are specified in [Functional Requirements](.github/KnowledgeBase/KB_CareerAtlas_FunctionalRequirements.md). Preserve the original `S1-01` through `S5-08` identifiers and phase assignments; do not implement later phases just because their designs appear in the knowledge base.

## Application areas

Current areas: `app/Domain` for validated, owner-scoped business operations; `app/Http` for browser/session adapters; `app/Mcp` for developer protocol tools; `app/Services` for shared publication projections; `app/Agent` and `app/Jobs` for bounded public Q&A; `resources/js` for Vue pages/components; `contracts` for machine-readable operation schemas; `database` for migrations; `tests/Feature` and `tests/e2e` for PostgreSQL/browser verification; `tests/Fixtures` and `scripts` for synthetic Agent evaluation and MCP acceptance.

## Projects for Verification

- Documentation: [.github/Scripts/Validate-Documentation.ps1](.github/Scripts/Validate-Documentation.ps1).
- Backend: `php artisan test --compact`; uses a dedicated PostgreSQL test database. Developer MCP: `npm run test:mcp` after local fixture setup. See [Building](.github/Guidelines/Building.md). Frontend checks follow at their acceptance gate.
- AI live evaluation: requires an explicitly configured provider and suitable test data. Simulated provider tests do not count as live model evaluation.

## Source and editing rules

Use fictional, clearly labeled seed data unless the user explicitly provides publication-ready data. Keep private personal records and confidential organizational material out of repository fixtures. Preserve user changes and never reset existing work to match a stale plan.

Once generated/vendor directories exist, change source definitions or package manifests rather than hand-editing vendor packages and build output. The original documentation initialization is complete; the current ten-gate task authorizes application implementation and verification.
