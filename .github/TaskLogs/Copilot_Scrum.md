# !!!SCRUM!!!

## DESIGN REQUEST

Create English AI documentation for CareerAtlas using GacUI's organization, grounded in the existing feature specification and technology choices.

## UPDATES

- 2026-11-17: Documentation initialization is complete and validated. The application slices below are a proposed future backlog.
- 2026-11-17: Standalone product documentation established. Shared release requirements are recorded in [Deployment](../KnowledgeBase/KB_CareerAtlas_Design_Deployment.md).
- 2026-11-17: Shared documentation and ignored demo operations documentation separated; see the [separation record](2026-11-17-documentation-separation.md).

## TASKS

| ID | Phase | Slice | Depends on | Completion boundary | Status |
| --- | --- | --- | --- | --- | --- |
| D0 | Foundation | English AI documentation | Source specification and format reference | Linked docs, full baseline coverage, passing validator | Complete |
| D1 | Foundation | Product positioning and deployment documentation | D0 | Consistent product scope and shared deployment requirements; environment details maintained locally | Complete |
| D2 | Foundation | Public/local documentation separation | D1 | Portable shared docs, ignored local operations notes, validated boundary | Complete |
| B0 | P0 | Application bootstrap | D0 | Compatible pinned stack, documented local setup, runnable smoke check | Not started |
| B1 | P0 | Owner identity and private profile | B0 | Login and owner-only read/edit access verified | Not started |
| B2 | P0 | Manual project, experience, skill, and evidence records | B1 | CRUD, validation, owner confirmation, provenance labels | Not started |
| B3 | P0 | Public portfolio publication and revocation | B2 | Selected immutable versions, public projection, immediate access revocation | Not started |
| B4 | P0 | Source-cited portfolio agent | B3 | Scoped tools, valid citations, bounded failure handling, isolation checks | Not started |
| R0 | P0 | Initial application release | B4, D1 | Supported target environment, reproducible release, P0 smoke checks and recovery procedure | Not started |
| B5 | P1 | AI-assisted experience recording | B4 | Editable draft and explicit owner confirmation | Not started |
| B6 | P1 | Interview preparation and practice | B4 | Source-backed preparation and editable text practice | Not started |
| B7 | P1 | Opportunity pages and export | B3 | Curated page, independent publication lifecycle, Markdown export | Not started |
| B8 | P2 | Organization collaboration | B2 and explicit organization scope | Grants, tenant isolation, confirmations, search, development discussion | Not started |

Feature IDs and exact priorities remain authoritative in [Functional Requirements](../KnowledgeBase/KB_CareerAtlas_FunctionalRequirements.md). These slices may be refined when implementation is requested.
