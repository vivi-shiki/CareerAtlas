---
pageClass: technical-reference
---

# Data model and publication lifecycle

PostgreSQL stores the editable career records and the published versions. The shared `/p/{slug}` page loads `GET /api/public/{slug}`; the server resolves that publication and returns its currently permitted snapshot. Adding a person does not require a new page component.

## Main tables

| Tables | Relationship and purpose |
| --- | --- |
| `users`, `profiles` | Login identity and one profile per user |
| `projects`, `experiences` | An owner has projects; each experience belongs to a project of that same owner |
| `skills`, `skill_aliases`, `experience_skill` | Owner-scoped skill names and aliases; many-to-many experience associations |
| `evidence` | Links or statements attached to an experience; a supplied link is not independent verification |
| `record_versions`, `experience_revisions` | Confirmed source versions and the history of later changes |
| `publications`, `publication_snapshots`, `publication_items` | Public slug and state, active snapshot, selected fields and source-version references |
| `idempotency_keys`, `audit_events` | Duplicate-request handling and audit records for business operations |
| `capture_drafts`, `preparations`, `prepared_answers`, `answer_versions`, `practice_sessions`, `opportunities` | P1 private capture, preparation, practice and separately published opportunities |
| `agent_conversations`, `agent_runs`, `owner_ai_runs` | Scoped conversations, queued work and owner proposals |

The physical source of truth is the [migration directory](https://github.com/vivi-shiki/CareerAtlas/tree/main/database/migrations). The core migration defines foreign keys and uniqueness constraints; later migrations add revision guards, Agent storage and P1 tables. Core authoring tables use relational columns and associations. Date precision, snapshots and proposal payloads also use PostgreSQL JSONB. This database representation is distinct from a hardcoded frontend JSON fixture; example JSON files are optional import material.

```text
User -> Profile
     -> Project -> Experience -> Evidence
                              <-> Skill (experience_skill)
                              -> Confirmed record version
Publication -> Active snapshot -> Selected versioned items
Public page / Q&A -> permitted projection of that publication
```

## Confirmation and publication

1. **Draft:** save incomplete work privately; missing results need not be invented.
2. **Confirm:** check the title, project, record type, occurrence with precision and personal action; preserve a confirmed version.
3. **Preview:** choose confirmed records and explicit profile/experience/project fields.
4. **Publish:** check the preview digest and create the snapshot and items in a transaction.
5. **Edit:** a later edit makes the current experience a draft; it does not replace the previously published version.
6. **Republish:** review and explicitly publish the updated selection.
7. **Revoke:** disable the publication. Source deletion or outward access revocation also removes dependent online references.

Archiving is a separate timestamp-based lifecycle action; it is not equivalent to revoking public access. Revocation cannot erase a copy already downloaded or displayed by a visitor. See [publishing](./publishing.md).

## Integrity and disclosure rules

- Composite ownership references prevent cross-owner project/experience and skill associations.
- An `expected_version` detects stale writes; same-owner operations are serialized in a transaction.
- Confirmation and publication use separate versions. A preview digest detects source changes before publication.
- An idempotency key permits replaying the same publication request; conflicting reuse is rejected.
- Public projects, skill counts and citations are derived from selected items, rather than querying all of an owner's drafts.
- Private notes are not selectable public fields. Private preparation requirements and unselected opportunity data are outside public Q&A.
- Dates retain year/month/day precision. Evidence links and owner statements retain distinct provenance.

## Example to inspect

Record a fictional contribution, confirm it and publish only its action, skills and evidence. Edit the result privately: the public page should still show the selected published version. Republish after review to change it, or revoke access to make it unavailable. Test this on a separate local fixture, not another person's records.

Organization memberships, recipient grants and third-party confirmations are planned P2 concepts, not tables implied to be implemented by this chapter. See [feature coverage](./roadmap.md), [backend code](./backend.md) and [verification cases](./testing.md).
