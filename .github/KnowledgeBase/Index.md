# Knowledge Base

## Guidance

Start with [Project.md](../../Project.md). CareerAtlas connects experience, project demonstrations, skills, and evidence. English is the working language. P0 is the implementation target; P1/P2 remain planned.

- [Product scope, roles, and ownership](KB_CareerAtlas_Product.md)
- [All five scenarios and 39 functional requirements](KB_CareerAtlas_FunctionalRequirements.md)
- [Acceptance criteria and verification](KB_CareerAtlas_Testing.md)
- [Sources and GacUI adaptation](Sources.md)

## CareerAtlas

### Choosing APIs

#### Business Operations

Use authenticated owner operations for editing, confirming, and publishing. Public operations read permitted snapshots. No application API is implemented yet.

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

#### AWS Deployment

AWS is the selected deployment platform. The configured selected Region is `ap-southeast-2`. Service topology and release tooling remain implementation decisions within the documented environment constraints.

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

## Experiences and Learnings

- [Shared decisions and preferences](Learning.md)
- [Coding observations](../Learning/Learning_Coding.md)
- [Testing observations](../Learning/Learning_Testing.md)
- [Current task records](../TaskLogs/README.md)

These learning files contain observed facts or explicit preferences, not fabricated implementation history.
