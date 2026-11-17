# CareerAtlas Data Model and Versioning

Status: logical model; names below are not existing tables or classes.

## Entities

| Entity | Required responsibilities | Phase |
| --- | --- | --- |
| User / Profile | Identity, display name, biography, preferred language, selected contact fields | P0 |
| Project | Owner, type, title, role, background, time range/precision, team outcomes | P0 |
| Experience | Project, author, type, title, occurrence time/precision, personal action, results, verification method, status, version | P0 |
| Skill / SkillAlias | Canonical name, aliases, confirmed experience associations | P0 |
| Evidence | Record/version relationship, type, label, URL or user-entered excerpt, provenance | P0 |
| RecordVersion | Immutable source identifier/version sufficient for confirmation and publication | P0 foundation; P1 history UI |
| Publication / PublicationItem | Selected fields and source versions, immutable snapshot, slug, state, timestamps | P0 |
| AgentSession / AgentRun | Owner/visitor scope, mode, tools, citations, status, latency, available usage | P0 |
| AuditEvent | Publication, revocation, and other critical state transitions | P0 foundation |
| InterviewPreparation / PracticeSession | Private requirements, questions, answers, feedback, source versions, save preference | P1 |
| Opportunity | Private target requirements, selected records, separate page version and status | P1 |
| Organization / Membership | Tenant, member identity, role, membership state | P2 |
| ShareGrant | Source version, field allowlist, recipients/scope, expiry, revocation | P2 |
| Confirmation | Exact source version and claim scope, reviewer, outcome, comments, time | P2 |
| ActionItem | Agreed development action, owner, target date, progress | P2 |

## Relationships

A profile owns projects; a project has experiences; experiences reference skills and evidence. Publications select source versions and fields. Agent citations reference only retrieved authorized versions. Later organization grants and confirmations attach to exact versions, not all future edits.

## Experience validation

Drafts may be incomplete. Owner confirmation requires title, project, record type, occurrence date/range with precision, and personal action. Background, quantified results, skills, and evidence are optional. Unknown results remain unknown; do not require invented metrics.

Separate team outcomes from personal actions. Store a statement's provenance as manual or AI-assisted and its confirmation time. A URL's presence indicates registration only, not independent verification.

## State machines

- Content: `draft -> confirmed -> archived`. Editing confirmed content creates a draft revision; prior confirmed versions remain distinguishable.
- Publication: `unpublished -> published -> revoked`. Republishing explicitly replaces the active snapshot; old server-accessible views must respect current revocation.
- Third-party confirmation: `not_requested -> pending -> confirmed | changes_requested`; material changes make the current version `stale` pending renewed review. A reviewer may decline to confirm with an explanation; do not label that as a negative ability assessment.

Archiving alone preserves history and is distinct from deletion or outward authorization revocation. The UI must expose the intended action clearly. Deletion and revocation invalidate dependent online access; clarify retention before implementing physical deletion.

## Integrity

- Use stable IDs, foreign keys, uniqueness constraints, transactions, and duplicate-request handling.
- Update operations carry an expected version; conflicts must not silently overwrite newer work.
- Repeated skill associations do not inflate project counts.
- Store occurrence time, record time, confirmation time, and publication time separately.
- Preserve month-only dates instead of inventing a day. Store timestamps consistently and render using an explicit timezone.
- Validate URL schemes; external links are not automatically fetched or trusted as code evidence.

See [API](KB_CareerAtlas_API.md) and [Testing](KB_CareerAtlas_Testing.md).
