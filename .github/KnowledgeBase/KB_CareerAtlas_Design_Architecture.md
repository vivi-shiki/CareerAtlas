# CareerAtlas Application Architecture

Status: P0 backend, live Vue UI and bounded public Agent implemented, with separate backend, browser, MCP and live-model verification. See the [implementation design](KB_CareerAtlas_Implementation_P0.md), [Agent architecture](KB_CareerAtlas_Agent_P0.md) and [acceptance ledger](../TaskLogs/2026-11-18-p0-implementation.md).

## Components

| Component | Responsibility |
| --- | --- |
| Laravel application | Identity, authorization, validation, persistence, publication, agent orchestration |
| Vue 3 / TypeScript UI | Forms, portfolio pages, source cards, conversation, draft previews |
| PostgreSQL | Records, relationships, snapshots, grants, runs, consistency constraints |
| Model adapter | Provider-specific transport and response normalization |

Use one deployable Laravel application with a same-origin Vue 3 / TypeScript UI. Vite builds assets served through Laravel; browser operations use the cookie-authenticated JSON API with CSRF protection. Developer MCP tools call the same domain services. The model is not the database authority. Server code performs all access and publication decisions.

## Business path

Input -> request validation -> object authorization -> business action -> transaction -> persistence -> permission-filtered response.

Use small responsibilities for creating experiences, confirming versions, publishing snapshots, and revoking access. Keep critical logic usable without AI. Do not create a parallel business implementation inside prompts.

## Question-answering path

Authorize session -> bind server-controlled scope -> retrieve allowed records -> bounded model/tool loop -> validate sources and access again -> render answer and citations.

Public retrieval is limited to the selected published snapshot. Private owner Q&A uses confirmed records in the chosen scope. Do not share model conversation memory across people or publication scopes.

## Version decisions

P0 requires immutable published snapshots and version identifiers for confirmation and citation integrity. P1 adds user-facing revision browsing and diffs; deferring that UI does not defer snapshot correctness.

Regular source edits do not update existing publications. Explicit republishing creates a new snapshot. Revoking a source's outward authorization or deleting it disables dependent online publication references even if an old snapshot exists.

## Operational direction

Use structured, redacted runtime logs. Keep API credentials server-side. Start with bounded database text/tag retrieval; vector search is optional only after observed retrieval failures justify it. Queue infrastructure is not required by this document; add it if runtime behavior needs background execution.

Preserve the Laravel, Vue, and PostgreSQL application boundaries across hosting environments. Follow the shared [Deployment contract](KB_CareerAtlas_Design_Deployment.md) for configuration, persistence, and release behavior.

Record exact runtime versions, shared integration code, retention policy, and portable release commands in the repository as they are implemented. Keep actual service selections, model settings, resource mappings, and environment-specific commands in ignored local documentation. No application deployment has been implemented.

Related: [Data Model](KB_CareerAtlas_Design_DataModel.md), [Authorization](KB_CareerAtlas_Design_Authorization.md), [Agent Tools](KB_CareerAtlas_AgentTools.md).
