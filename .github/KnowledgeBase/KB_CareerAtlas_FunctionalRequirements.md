# CareerAtlas Functional Requirements

Version: 0.1. English adaptation of the source specification dated 2026-11-17. All requirements are planned. Original feature IDs and phase assignments are preserved.

Read [Product](KB_CareerAtlas_Product.md) for roles and ownership, [Testing](KB_CareerAtlas_Testing.md) for the 27 scenario acceptance criteria, and [User Experience](KB_CareerAtlas_Design_UserExperience.md) for page states.

## S1: Present Personal Capabilities

### User goals and preconditions

An owner wants visitors to understand representative projects, personal responsibilities, and evidence quickly. A visitor wants to ask a specific question and inspect its sources. A profile and confirmed project records exist; visitors only read published versions.

### Main flow

1. Enter a display name, biography, career direction, and optional public contact fields.
2. Create a commercial, personal, or learning project and identify the owner's role.
3. Record background, personal actions, technology, outcomes, and evidence.
4. Select representative projects and public fields.
5. Preview and explicitly publish a snapshot.
6. A visitor browses or asks a question in that publication's scope.
7. The agent retrieves permitted records and returns an answer with citations.
8. Clicking a citation opens the relevant project experience.

### Functions

| ID | Function | Rules and output | Phase |
| --- | --- | --- | --- |
| S1-01 | Edit profile | Display name required; direction, biography, contact links optional | P0 |
| S1-02 | Maintain projects | Title, type, role required; ongoing and imprecise time ranges supported | P0 |
| S1-03 | Separate contributions | Personal action and team outcomes remain distinct in UI and answers | P0 |
| S1-04 | Register demos and evidence | Labeled typed links; no automatic fetching or embedded execution | P0 |
| S1-05 | Connect skills | Skill details show permitted experiences; hidden records do not affect public counts | P0 |
| S1-06 | Preview, publish, revoke | Explicit field/project selection; revocation covers pages and agent access | P0 |
| S1-07 | Portfolio Q&A | Factual experience claims cite sources; missing information is acknowledged | P0 |
| S1-08 | Named-recipient access | Authenticated identity must match a grant; opaque URLs are insufficient | P2 |

### Pages and exceptions

The public home contains biography, representative projects, skill navigation, selected contact information, publication update time, and Q&A. Project pages show context, role, experiences, outcomes, skills, and evidence.

Show a useful empty state when nothing is published. Broken external links must not break the portfolio or appear verified. A model outage must not prevent browsing. Do not reveal whether a private record exists. Reauthorize an in-flight answer before returning content if a source was revoked.

Acceptance: AC-S1-01 through AC-S1-05.

## S2: Prepare for an Interview

### User goals and preconditions

An authenticated owner wants to select real examples and practice explaining them in the target language and approximate length. Preparation and practice remain private.

### Main flow

1. Create a preparation task with role/topic, optional company/date, and target language.
2. Paste role requirements or enter a practice question; source URLs are metadata only.
3. Retrieve confirmed experiences related to the question.
4. Show candidate examples, reasons, missing details, and citations; the owner selects one.
5. Draft a factual answer with explicit gaps.
6. The owner edits and confirms the answer.
7. Practice one written question at a time and receive feedback.
8. Save only the feedback and practice the owner chooses to retain.

### Functions

| ID | Function | Rules and output | Phase |
| --- | --- | --- | --- |
| S2-01 | Create preparation | Role or topic required; Japanese, Chinese, and English target content supported | P1 |
| S2-02 | Enter requirements | Paste text; store optional source URL without automatic crawling | P1 |
| S2-03 | Select examples | Explain relevance and gaps with sources; no invented matching percentages | P1 |
| S2-04 | Draft answers | Short/standard length; preserve responsibilities, actions, and outcomes | P1 |
| S2-05 | Ask follow-up questions | Ask about missing role, difficulty, or validation; allow skipping | P1 |
| S2-06 | Written practice | One question at a time; stop, retry an answer, or save | P1 |
| S2-07 | Feedback and review | Specificity, structure, consistency, and clarity; no hiring predictions | P1 |
| S2-08 | Answer versions | Preserve edited answers and source versions; flag changed sources | P1 |

### Rules and exceptions

Missing records are not evidence of missing ability. Do not invent a technology to match a job description. Translation preserves names, numbers, dates, and responsibilities. Estimated speaking length is not measured audio duration. Practice feedback is not an employer's evaluation. Provider failure retains user input and saved drafts. Discarded practice must not enter the career archive.

Acceptance: AC-S2-01 through AC-S2-05.

## S3: Record Work and Learning

### User goals and preconditions

An individual or employee wants to preserve recent contributions with minimal friction. A complete résumé is not required; a personal or learning project can be created first.

### Main flow

1. Choose or create a project.
2. Enter a short account of completed work or learning.
3. In P0, complete a manual form for time, personal action, result, skills, and evidence.
4. In P1, optionally ask the capture agent to read selected project context.
5. Ask at most two gap-filling questions per turn; allow skipping and direct editing.
6. Show an editable draft with unknown facts left blank.
7. The owner checks and confirms the experience and skill associations.
8. Keep it private until separately published or shared.

### Required fields

Confirmation requires a title, project, record type, occurrence date/range with explicit precision, and personal action. Background, results, validation, skills, evidence, and remaining work are optional. Record provenance and confirmation time. Do not require metrics that the owner does not have.

### Functions

| ID | Function | Rules and output | Phase |
| --- | --- | --- | --- |
| S3-01 | Manual create/edit/archive | Incomplete drafts allowed; confirmation validates required fields | P0 |
| S3-02 | Skill associations | Normalize aliases; avoid duplicate experience/project counts | P0 |
| S3-03 | Evidence registration | Distinguish a supplied link, owner statement, and reviewer confirmation | P0 |
| S3-04 | AI capture interview | Read allowed project context, ask for facts, return an editable draft | P1 |
| S3-05 | Possible duplicates | Show similar records; owner chooses whether to merge or add | P1 |
| S3-06 | Revision history | User-facing versions and differences; P0 still needs immutable publication/version foundations | P1 |
| S3-07 | Request confirmation | Request a reviewer to confirm specified claims in an exact version | P2 |

### Rules and exceptions

Autosave and model output remain drafts until the owner confirms. Unquantified or unverified outcomes can be saved as such. Proposed skill tags require owner confirmation. Do not convert a request to improve wording into unsupported metrics. Removing evidence makes dependent confirmations subject to review. Archiving, deletion, and publication revocation are separate operations.

Acceptance: AC-S3-01 through AC-S3-06.

## S4: Curate Content for Different Opportunities

### User goals and preconditions

An owner wants a role-specific or internal-project presentation based on the same factual records. Confirmed source records exist. Opportunity metadata remains private by default.

### Main flow

1. Create an opportunity with title, target role/project, and requirements.
2. Select experiences manually or request agent suggestions.
3. Review a requirements-to-evidence table and missing information.
4. Choose project order and edit an introductory draft.
5. Preview all selected fields; use public sharing in P1 or named grants in P2.
6. Publish an independent versioned page.
7. If sources change, review differences before explicitly republishing.
8. Revoke the page or archive the opportunity when appropriate.

### Functions

| ID | Function | Rules and output | Phase |
| --- | --- | --- | --- |
| S4-01 | Create opportunity | Title, target direction, requirement text; optional deadline | P1 |
| S4-02 | Requirements/evidence mapping | Show corresponding records and gaps with citations | P1 |
| S4-03 | Select and order | Owner chooses final items; AI suggestions never publish directly | P1 |
| S4-04 | Draft introduction | Change emphasis without changing source facts | P1 |
| S4-05 | Independent publication | Snapshot on publish; unrelated opportunity pages remain undisclosed | P1 |
| S4-06 | Page-specific Q&A | Retrieval is limited to that page, not the entire private archive | P1 |
| S4-07 | Text export | Markdown of selected fields, target language, and source versions | P1 |
| S4-08 | Recipients and expiry | Authenticated grants with expiry and revocation | P2 |

### Rules and exceptions

A record's presence in an archive is not permission to publish it. Ordering can differ across opportunities; facts cannot. Recipients cannot discover other private opportunities. No automatic application submission, external messaging, or fabricated hiring probability. Export enforces the same field allowlist; downloaded copies cannot be recalled.

Source editing leaves published snapshots unchanged; source deletion/outward revocation disables dependent online content. Notify the owner to revise affected pages.

Acceptance: AC-S4-01 through AC-S4-05.

## S5: Understand Employee Experience and Growth

### User goals and preconditions

Employees want fewer repeated skill forms and explicit control of sharing. Managers want real contributions and learning context for development discussions. Colleagues want relevant shared experience for collaboration. Organization identity, membership, and content grants must already be established.

### Main flow

1. An administrator invites members and assigns roles.
2. A member accepts without exposing a personal archive.
3. Create an organization record or share a selected personal version.
4. Optionally ask a named reviewer to confirm specified facts.
5. The reviewer confirms, requests additions, or declines with an explanation.
6. Authorized users search skills, domains, and time ranges.
7. Generate a source-backed summary for one employee and period, with discussion prompts.
8. Save agreed actions, responsible people, target dates, and progress.

### Functions

| ID | Function | Rules and output | Phase |
| --- | --- | --- | --- |
| S5-01 | Organization membership | Invite, accept, change role, leave, suspend; tenant isolation | P2 |
| S5-02 | Share experience | Explicit version, recipient scope, and revocation | P2 |
| S5-03 | Human confirmation | Confirms exact claims/version, not a person's overall capability | P2 |
| S5-04 | Experience discovery | Authorized skill/domain/time filtering with evidence and freshness | P2 |
| S5-05 | Growth timeline | New contributions, learning, and confirmation state over time | P2 |
| S5-06 | Discussion preparation | Summarize facts, employee-raised issues, and questions to discuss | P2 |
| S5-07 | Action items | Record agreed learning/support actions and progress | P2 |
| S5-08 | Access and audit records | Track sharing, revocation, confirmation; do not expose hidden model reasoning | P2 |

### Rules and exceptions

Return related experiences and permit objective sorting such as dates. Do not make automated hiring, promotion, or dismissal decisions. No shared evidence means no shared evidence, not poor performance. Do not score employees by record or chat counts. Authorize source facts, self-assessments, reviewer statements, and private discussion notes separately. Manager comments do not rewrite employee statements. Membership changes apply to existing sessions and citations as well as fresh requests.

Acceptance: AC-S5-01 through AC-S5-06.
