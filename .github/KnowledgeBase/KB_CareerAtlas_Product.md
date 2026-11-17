# CareerAtlas Product

Status: intended product behavior, derived from functional specification v0.1 dated 2026-11-17.

## Objective

Provide an AI-powered career portfolio and skills knowledge system that helps people record real contributions, connect them to skills and evidence, publish selected versions, and answer questions with traceable sources. Later phases reuse these records for interview preparation, opportunity-specific presentations, and authorized employee development.

CareerAtlas is a standalone product for recurring use by individuals, collaborators, and organizations. Product success is measured through useful recording, discovery, presentation, and development workflows.

## Shared problem

Individuals and organizations often have incomplete or outdated descriptions of real experience. CareerAtlas makes contributions easier to record, find, and explain. The intended value must be tested through actual use; no productivity or hiring outcome is promised.

## Roles

| Role | Allowed purpose | Boundary |
| --- | --- | --- |
| Owner | Maintain records, use private AI, publish chosen content | Does not gain permission to disclose employer-owned material |
| Anonymous visitor | Browse and ask about one public portfolio | No private content, other visitor sessions, or editing |
| Named recipient | Read a specific authenticated grant | An opaque link alone is not authorization |
| Organization member | Maintain and read authorized shared experience | Joining never exposes a personal archive |
| Manager / mentor | Review shared records, confirm facts, prepare discussions | No automatic access to private job-search or interview material |
| Organization administrator | Manage membership and configuration | Administration is not unrestricted content access |

## Spaces and ownership

Personal records remain under personal control. Organization records live in a separate scope. Sharing a personal record means granting a specified version and fields. Leaving an organization removes organization access without removing the personal archive or copying organization material into it.

Access modes are `private` and `public` in P0; named recipients and organization access arrive in P2. Private notes, opportunity status, interview practice, and private AI conversations never enter publication implicitly. Existing downloaded files and screenshots cannot be recalled when an online grant is revoked.

## Phases

- P0: S1 public capability presentation and cited Q&A, plus manual S3 experience/skill/evidence management.
- P1: AI capture, interview preparation, practice, opportunity curation, and text export.
- P2: explicit organization and recipient grants, third-party confirmation, team discovery, and development discussion support.

## Product boundaries

P0 accepts typed text and registered URLs. It does not crawl repositories, verify linked code, run uploaded demonstrations, or connect to a live HR system. Neither record volume nor chat activity is a performance score. Missing records mean insufficient shared information, not insufficient ability.

## Validation metrics

Measure time to first confirmed experience, repeat recording, successful source retrieval, source clicks, accepted/edited/discarded AI drafts, and recurring unanswered question categories. For organization use, interview users about duplicate collection steps and verify with observed workflows.

## Product design references

The [UI/UX specification](KB_CareerAtlas_Design_UIUX.md) and [visual design pack](../../design/2026-11-17/README.md) are shared product documentation. Use them alongside the functional requirements to review the proposed workflows:

| Product intent | Design reference | Requirement connection |
| --- | --- | --- |
| Record personal contributions and their evidence privately | Experience editor | S3-01 through S3-03 |
| Review the exact fields a visitor will receive | Publication preview | S1-06 |
| Present work and answer questions with inspectable sources | Public portfolio and cited Q&A | S1-07 |
| Preserve access rules through publication changes | Update/revocation flow | Publication and authorization contracts |

These references illustrate intended behavior and use fictional records. They do not establish working authentication, authorization, accessibility, mobile behavior, or model integration. Acceptance criteria remain authoritative; UI/UX references help reviewers assess the design before implementation.

## Open choices

The [Deployment contract](KB_CareerAtlas_Design_Deployment.md) defines shared operational requirements. Hosting and API selections for an individual installation are local environment decisions.

Runtime/package versions, frontend integration, reusable delivery tooling, retention periods, future attachments, recipient sign-in, and detailed third-party confirmation policy remain shared implementation decisions. The product name has not been checked for domain or trademark availability.

See [Functional Requirements](KB_CareerAtlas_FunctionalRequirements.md) and [Sources](Sources.md).
