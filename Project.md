# Project Specific Instruction

## System objective

CareerAtlas is an AI-powered career portfolio and skills knowledge system for maintaining evidence-linked professional records, publishing selected project demonstrations, answering questions with traceable sources, and later supporting interview preparation and permission-controlled employee development.

The core relationship is **Person -> Project -> Personal contribution -> Skill -> Evidence**.

Treat CareerAtlas as a standalone product for ongoing personal use and organizational collaboration. Requirements, release scope, and engineering decisions are driven by user workflows and acceptance criteria.

## Current repository state

As of 2026-11-17, the repository contains the AI documentation foundation only. Application paths described below are planned, not existing. Do not report a running Laravel app, installed packages, passing application tests, or a live deployment without new evidence.

## Technology choices

| Layer | Choice | Responsibility |
| --- | --- | --- |
| Backend | PHP / Laravel | Identity, Policies, validation, transactions, publications, agent tools |
| Frontend | Vue 3 / TypeScript | Owner workspace, public portfolio, forms, citations, chat |
| Database | PostgreSQL | Structured records, versioned publications, permission-aware queries |
| AI | Provider-neutral server adapter | Tool calling, structured responses, bounded execution |
| Build tooling | Composer and a Vue-compatible frontend toolchain | Select exact versions and package manager during bootstrap |
| Deployment | Configurable hosting | Application runtime, persistent PostgreSQL, HTTPS, health checks, backup and recovery |

Use a single Laravel application with Vue UI as the initial architectural direction. A same-origin deployment is preferred to simplify authentication. Whether Vue is mounted through Inertia or uses a JSON API is an open bootstrap decision; preserve the business contracts either way. Do not introduce a separate Node/Python application server without a concrete requirement.

Read [Deployment contract](.github/KnowledgeBase/KB_CareerAtlas_Design_Deployment.md) before infrastructure work. Shared implementation details and portable release procedures belong in Git. Actual cloud/API selections, project identifiers, resource settings, and environment verification belong in ignored `.local/docs/`. This separation does not change the application stack or feature requirements.

## Scope

| Phase | Included | Completion boundary |
| --- | --- | --- |
| P0 | Login, profile, manual projects/experiences, skills, evidence links, publish/revoke, cited portfolio Q&A | A usable owner-to-visitor MVP with authorization tests |
| P1 | AI experience capture, interview preparation, text practice, opportunity-specific pages, Markdown export | Repeated personal use across recording, preparation, and presentation |
| P2 | Organizations, recipient grants, confirmations, team search, development conversations | Explicitly authorized collaboration with tenant isolation |

The five scenarios are specified in [Functional Requirements](.github/KnowledgeBase/KB_CareerAtlas_FunctionalRequirements.md). Preserve the original `S1-01` through `S5-08` identifiers and phase assignments; do not implement later phases just because their designs appear in the knowledge base.

## Proposed application areas

After scaffolding, map the framework's actual directories here. Intended areas are Laravel domain services/Policies/requests, Vue pages/components, migrations/seeders, backend tests, frontend checks, and agent evaluation fixtures. Conventional paths such as `app/`, `resources/`, `database/`, and `tests/` are not yet created.

## Projects for Verification

- Documentation: [.github/Scripts/Validate-Documentation.ps1](.github/Scripts/Validate-Documentation.ps1).
- Backend/frontend/application checks: not available until scaffolding. Record verified commands in [Building](.github/Guidelines/Building.md) and [Testing](.github/Guidelines/Testing.md).
- AI live evaluation: requires an explicitly configured provider and suitable test data. Simulated provider tests do not count as live model evaluation.

## Source and editing rules

Use fictional, clearly labeled seed data unless the user explicitly provides publication-ready data. Keep private personal records and confidential organizational material out of repository fixtures. Preserve user changes and never reset existing work to match a stale plan.

Once generated/vendor directories exist, change source definitions or package manifests rather than hand-editing vendor packages and build output. No application implementation is part of the documentation initialization task.
