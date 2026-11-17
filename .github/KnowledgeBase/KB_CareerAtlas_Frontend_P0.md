# P0 Frontend Implementation and Acceptance

Scope: the user confirmed P0 only. This document records the visual system and the browser behavior coverage contract. P1/P2 screens are excluded. The interface supports English, Simplified Chinese, and Japanese through the [localization contract](KB_CareerAtlas_Localization.md); user-authored content remains in its original language.

## Visual implementation inventory

The three images in the [design pack](../../design/2026-11-17/README.md) remain the reference: contribution editor, publication preview and public portfolio. All visible UI is semantic HTML/Vue, not a screenshot backdrop. No photographs or raster illustrations are required. Book, folder, graduation cap, person, megaphone, link, information, chevron, plus, close, eye, send and external-link icons use one Lucide outline family with consistent strokes. No decorative gradients or analytics are added.

Tokens live in `resources/css/app.css`: white canvas, pale neutral sidebar, emerald primary, mint selected state, thin neutral borders, 8px controls, system sans typography. Shared components own the workspace shell, page heading, evidence rows, skill chips and public content. Source-owned shadcn-vue primitives provide buttons, inputs, textareas and checkboxes. Registry imports were inspected and corrected to the local utility alias and chosen icon package. Lockfiles capture the actual versions.

At desktop, the editor is a contribution form and evidence rail; publication has selection and preview columns; public work and questions share a split reading surface. Content uses natural height and wrapping. At 390px, navigation collapses, fields/evidence stack, and publication selection and review become sequential screens. Action controls stay after the content with safe-area padding and cannot cover the final input. Reduced motion is respected. A keyboard skip link and labeled fields are present.

Project, profile, skill and identity screens extend the same component vocabulary because their required workflows have no separate raster reference. No alternative theme is introduced. `/design/*` is restricted to local/testing environments and uses explicit synthetic fixtures; production business routes must never use those fixtures as an error fallback.

## Fidelity review ledger

| Comparison | Reference / rendered decision | Verification |
| --- | --- | --- |
| Structure | Persistent Projects/Skills/Profile/Publish navigation; editor/evidence, selection/preview and portfolio/question columns | Native 1487 × 1058 screenshots against all three accepted PNGs |
| Sidebar | 240px follows the written 216–240px specification; generated PNG sidebar is wider | Intentional normalization to the documented token range |
| Copy and privacy | Original headings, selected navigation, confirmation/privacy note, snapshot/revocation note, supplied-link disclaimer and fictional-data label retained | Above-the-fold strings compared; optional empty-state text appears only in sparse fixtures |
| Typography | Explicit heading/body/control sizes and weights; no browser-default buttons | Native-size visual inspection plus responsive wrapping |
| Palette and containers | White canvas; emerald/mint; dividers and open space; framed visitor preview only | Visual comparison; no added illustration/overlay treatment |
| Icons | Single outline family, correct metaphors, native SVG controls | Inspect navigation, evidence, status, preview and question actions |
| Dense records | Multilingual prose, twelve skills, eight evidence rows and five projects | Natural height/wrapping; no forced truncation or horizontal overflow |
| Mobile | One column, 16px gutters, explicit privacy/source labels and sequential publication review | 390px screenshots; review/back transitions retain selection and move focus |
| Repairs | Mobile final step wrapped vertically; hidden skip link appeared in full-page capture | Grid-based step layout and clipping for unfocused skip link; repeat screenshots |

The public Q&A content on design routes is a labeled fictional layout example, not a model response. Business routes now use Vue Router and the same-origin Laravel API; non-AI live browser acceptance has passed. Visual acceptance does not establish business or Agent acceptance.

## Browser behavior coverage contract

Tests must use actual Laravel/PostgreSQL persistence for successful business flows. API setup is allowed for fixtures; the action under test is performed through the browser. Check resulting API/database state, reload persistence, and anonymous projection as appropriate. Isolated transport failure tests may intercept one request, but must be labeled and must not replace persistence tests.

| Area | Required browser branches | Persistence / boundary assertions |
| --- | --- | --- |
| Identity | Register, invalid input, duplicate address, sign in, wrong credentials, sign out, protected-route redirect | Session ownership, no data after logout; password never rendered |
| Profile | Required name, optional fields, multilingual bio, safe contacts, add/remove links, save/reload, failure, unsaved navigation, conflict | Exact persisted values/version; contacts absent unless selected |
| Projects | Empty, create, required fields, types, imprecise/ongoing range, filter/no results, edit, archive/restore, delete/cancel | Exact values and precision; archived records retained; deleted sources unavailable |
| Experiences | Incomplete draft, required confirmation errors/focus, all optional fields, save/reload, confirm, edit confirmed record, stale version, failure/retry, unsaved navigation | Draft never public; confirmation appends one version; current published snapshot remains unchanged |
| Evidence | Type/label/link/statement, invalid URL, edit, delete/cancel, long content, no automatic external fetch | Correct provenance and association; edits create draft without mutating snapshot |
| Skills | Create/normalize, alias add/remove/collision, rename, search/detail, attach/detach and duplicate attach | Canonical identity, distinct counts, relation persistence; no hidden public counts |
| Publication | Explicit field/record selection, only confirmed records, mobile selection/review, exact preview, publish, replay/double click, source changed during review | Preview equals anonymous snapshot; no unselected fields/private notes; one result |
| Publication lifecycle | Source edit, pending-change warning, review/republish, source access withdrawal, source delete, revoke/cancel/repeat | Old snapshot preserved until republish; current pages, projects, skills and citations reauthorize |
| Public browsing | Profile, project, skills, evidence/source detail, absent/revoked source, empty state | Selected snapshot only, neutral unavailable responses, safe external links |
| Accessibility/recovery | Keyboard navigation and dialog focus, responsive layout, 200% zoom, long CJK, reduced motion, API outage, session expiry | Input retained; focus moved to first error; retry explicit; manual actions do not need AI |

Agent behaviors (loading/cancel/retry, source cards, information gaps, adversarial requests, final authorization and provider evaluation) are designed and tested at gates 9–10.

## Executable completeness review (gates 5–6)

The first browser suite contains 35 behavior tests. Review against the contracts above added 11 explicit cases, for 46 tests across seven files. Test discovery and TypeScript checking pass; these are acceptance specifications awaiting live UI implementation, not passing business acceptance.

| Requirement / branch | Test identifiers |
| --- | --- |
| Identity and protected navigation | AUTH-01–03 |
| S1-01 profile, safe contacts, persistence, conflict and recovery | PROFILE-01–04; REVIEW-01, REVIEW-10–11 |
| S1-02 projects, ranges, filters, pagination, lifecycle and conflicts | PROJECT-01–05; REVIEW-02, REVIEW-09 |
| S1-03 / S3-01 draft, confirmation, optional fields, revision, lifecycle | EXPERIENCE-01–05; REVIEW-03 |
| S1-04 / S3-03 evidence CRUD, provenance and safe external links | EVIDENCE-01–02; PUBLIC-01 |
| S1-05 / S3-02 canonical skills, alias collisions/search, association and distinct counts | SKILL-01–02; REVIEW-06 |
| S1-06 exact selection, preview, publish, stale review, replay, withdrawal and revocation | PUBLISH-01–07; REVIEW-04–09; PUBLIC-02 |
| Object isolation, no fictional error fallback, session expiry, API failure | BOUNDARY-01; RECOVERY-01–02; PROJECT-05 |
| Keyboard focus, dialogs, long CJK, responsive reflow and reduced motion | ACCESSIBILITY-01–02; PUBLISH-07 |

The review found backend query gaps for project-type filtering and skill-alias search; corresponding browser tests must pass after implementation. Project background/team outcome are project context, while personal action/result/validation/remaining work/private notes belong to an experience; UI edits must respect these existing persistence boundaries.

The added cases cover required profile focus, project concurrency, all date precisions/ranges, optional publication fields, a response lost after a successful commit, deduplicated public skill counts, source reauthorization on reopening, no confirmed records, dependent project deletion, expired CSRF refresh, and rate-limit recovery. The snapshot test compares the serialized preview to the anonymous result. A read-only observation helper checks database row counts for confirmation/publication idempotency, only for synthetic owners in the dedicated E2E database.

## Business integration review (gate 7)

All non-AI P0 pages are wired to the shared operation contracts. The browser obtains cookie/CSRF sessions, refreshes an expired CSRF token once, preserves input on failures, and uses expected versions for edits. Publication review retains the exact reviewed selection, digest and idempotency key across retries. Public project and skill dialogs request authorized snapshots on each opening. An expired session offers sign-in in another tab so the editing tab can retain its draft.

Implementation review added AUTH-04 (cancelled sign-out keeps both session and draft) REVIEW-12 (different field selections for multiple experiences retain their shared project context and separate dates), and REVIEW-13 (a pending save cannot silently consume later edits), bringing the non-AI suite to 49 tests. Shared project presentation merges only fields already returned by the authorized projection. All 49 non-AI browser cases passed together in 4.5 minutes. The complete create-to-revoke browser workflow passed with ten screenshots and no page errors or horizontal overflow.

Repairs established by browser evidence: avoid passing an undefined `href` through RouterLink because it removes the generated anchor destination; check the draft before ending the session; show occurrence dates per experience. Source formatting is standardized with a pinned Prettier version. Build/type checking pass after these changes.

Limitations: narrow reflow at 743px approximates the layout viewport at 200% desktop zoom; it is not an actual browser-zoom or screen-reader audit. Screenshot checks remain separate from business assertions. Transport interception is limited to explicit failure cases. Provider behavior is still excluded until gates 9–10.

Live visual review retained the reference structure and palette. Intentional extensions include read-only shared project context in the experience editor, per-experience dates, an additional-details section, lifecycle actions, and complete selected-field rendering. Publication review summarizes selected project context and excludes unselected rows from its read-only checklist; selection remains fully editable before review. Restricting button transitions to color/opacity prevents transient text compression during responsive resizing. Capture with animations disabled and instant top scrolling avoids misplacing fixed navigation in full-page artifacts. The Agent panel now implements suggestions, bounded questions, progress, cancellation, explicit retries, cited answers, source dialogs, history and scope-change clearing; see [Agent design and tests](KB_CareerAtlas_Agent_P0.md).
