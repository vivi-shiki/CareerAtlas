# Knowledge Base

## Guidance

Start with [Project.md](../../Project.md). CareerAtlas connects experience, project demonstrations, skills, and evidence. English is the working language. P0 is locally accepted; P1 implementation is authorized and in progress. P2 remains planned.

- [Product scope, roles, and ownership](KB_CareerAtlas_Product.md)
- [All five scenarios and 39 functional requirements](KB_CareerAtlas_FunctionalRequirements.md)
- [Acceptance criteria and verification](KB_CareerAtlas_Testing.md)
- [Sources and GacUI adaptation](Sources.md)

- [P0 implementation design, schema, atomic operations, and coverage review](KB_CareerAtlas_Implementation_P0.md)
- [P0 frontend visual system and browser behavior coverage](KB_CareerAtlas_Frontend_P0.md)
- [P0 Agent integration decision, lifecycle, UI and acceptance](KB_CareerAtlas_Agent_P0.md)
- [P1 domain, owner assistance, preparation and opportunity implementation](KB_CareerAtlas_Implementation_P1.md)

## CareerAtlas

### Choosing APIs

#### Business Operations

Use authenticated owner operations for editing, confirming, and publishing. Public operations read permitted snapshots. P0 non-AI operations now share one domain dispatcher across HTTP and developer MCP; frontend and Agent acceptance remain separate.

- Use request validation for input and Policy-style authorization for objects.
- Use explicit publication actions for snapshot updates and revocation.

[API Explanation](KB_CareerAtlas_API.md)

#### Agent Tools

Use bounded retrieval tools for source-backed portfolio answers. Add experience capture tools in P1.

- Use `search_experiences` to locate authorized records.
- Use `get_experience` to retrieve allowed fields and source versions.

[API Explanation](KB_CareerAtlas_AgentTools.md)

### Design Explanation

#### Application Architecture

Laravel owns business rules; Vue renders the interface; PostgreSQL stores structured records. AI is an optional execution capability with a non-AI fallback.

[Design Explanation](KB_CareerAtlas_Design_Architecture.md)

#### Deployment Contract

Define portable runtime, persistence, configuration, release, and recovery requirements. Actual service selections and environment observations are maintained in ignored local operations documentation.

[Design Explanation](KB_CareerAtlas_Design_Deployment.md)

#### Data and Versioning

Separate work-in-progress records, owner confirmation, publication snapshots, and third-party confirmations.

[Design Explanation](KB_CareerAtlas_Design_DataModel.md)

#### Authorization and Publication

Apply permission and version checks consistently across pages, retrieval, citations, and exports.

[Design Explanation](KB_CareerAtlas_Design_Authorization.md)

#### User Experience

Support explicit drafts, clear errors, editable AI output, accessible citations, and manual fallbacks.

[Design Explanation](KB_CareerAtlas_Design_UserExperience.md)

#### UI Framework and Visual Design

Framework recommendation, open-source visual references, screen specifications, responsive behavior, and the UI/UX design pack.

[Design Explanation](KB_CareerAtlas_Design_UIUX.md)

## Experiences and Learnings

- [Localization: three-language UI, API messages, and README maintenance](KB_CareerAtlas_Localization.md)

- [Shared decisions and preferences](Learning.md)
- [Coding observations](../Learning/Learning_Coding.md)
- [Testing observations](../Learning/Learning_Testing.md)
- [Current task records](../TaskLogs/README.md)

These learning files contain observed facts or explicit preferences, not fabricated implementation history.
