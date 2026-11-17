# CareerAtlas UI Framework and Visual Design

Date: 2026-11-17; implementation update 2026-11-18. Status: reviewed visual targets with static Vue screens, live business UI and an integrated public Agent panel. Separate interaction and visual evidence is recorded in the [acceptance ledger](../TaskLogs/2026-11-18-p0-implementation.md).

## Recommendation

Use **shadcn-vue + Tailwind CSS**, with Vue 3 / TypeScript. The implementation uses a Laravel same-origin JSON API and Vite asset integration, as selected in the [architecture](KB_CareerAtlas_Design_Architecture.md). Source-owned shadcn-vue primitives and Lucide Vue icons are installed; exact reproducible versions are in `package-lock.json`. Laravel and PostgreSQL remain the backend foundation.

CareerAtlas needs both a calm reading surface and precise editing/publication controls. Source-owned components make a shared design vocabulary practical across these different surfaces. The tradeoff is responsibility for component updates, accessibility checks, and regression review. The library alone does not supply CareerAtlas authorization, citation correctness, or domain workflows.

| Candidate | Fit for CareerAtlas | Decision |
| --- | --- | --- |
| shadcn-vue + Tailwind | Custom contribution editor, provenance labels, publication preview and editorial portfolio; aligns with Laravel's official Vue starter | Selected design basis |
| Nuxt UI | Also supports plain Vue/Vite and Laravel Inertia; a valid integrated component alternative, not a requirement to adopt Nuxt | Runner-up if the team prefers package-managed components |
| Element Plus | Broad component inventory and useful form/table conventions; a reasonable choice if dense administrative operations become the dominant workload | Not the preferred visual basis for this portfolio-first P0 |

These are product-fit judgments, not benchmark scores. The primary sources checked are [Laravel starter kits](https://laravel.com/docs/starter-kits), [shadcn-vue introduction](https://www.shadcn-vue.com/docs/introduction), [Nuxt UI Vue/Inertia installation](https://ui.nuxt.com/docs/getting-started/installation/vue), and [Element Plus overview](https://element-plus.org/en-US/component/overview).

## Open-source visual references

| Reference | Relationship | What to borrow | What to omit |
| --- | --- | --- | --- |
| [unovue/shadcn-vue](https://github.com/unovue/shadcn-vue), [dashboard block](https://www.shadcn-vue.com/view/dashboard-01) | Vue implementation; MIT | Quiet surfaces, sidebar, consistent forms and restrained green accents | Revenue charts, analytics cards and unrelated navigation |
| [satnaing/shadcn-admin](https://github.com/satnaing/shadcn-admin), [live demo](https://shadcn-admin.netlify.app/) | React/Vite implementation of the same shadcn design family; MIT | Compact navigation, typographic hierarchy, thin separators | React application code, administration-first information architecture |
| [laravel/vue-starter-kit](https://github.com/laravel/vue-starter-kit) | Direct integration reference; not a captured visual reference | Laravel/Vue/TypeScript/Tailwind/shadcn-vue starting point | Unneeded starter features and assumptions about business permissions |

The first two references were visually inspected in the browser; their screenshots are saved in the [design pack](../../design/2026-11-17/README.md). Those actual screenshots were supplied to built-in ImageGen. Licensing references: [shadcn-vue license](https://github.com/unovue/shadcn-vue/blob/dev/LICENSE) and the Shadcn Admin repository license. Preserve applicable notices if code is later reused. No reference application code was copied for this task.

## Visual system

The direction is an evidence-first workspace: a quiet white surface, dark readable text, emerald actions, and a persistent distinction between factual content and publication controls. Do not use skills as ratings, employee rankings, invented matching percentages, or decorative analytics.

| Token | Proposed specification |
| --- | --- |
| Canvas / sidebar | `#FFFFFF` / `#F8FAFC` |
| Text / secondary text | `#17221F` / `#52615A` |
| Primary / selected tint | `#047857` / `#ECFDF5` |
| Pending / destructive | `#92400E` / `#B91C1C`, always accompanied by text |
| Borders | `#D7DFDA`; thin separators before card containers |
| Type | System sans or Inter; body 16px, compact labels 14px, page headings 30px; CJK fallback Noto Sans JP/SC or system equivalent |
| Spacing / radius | 4px base; 8, 12, 16, 24, 32px steps; controls 8px radius |
| Desktop target | 1440 x 1024 design viewport; 216-240px sidebar; content max-width 1200px |
| Touch / keyboard | 44px target for primary touch controls; visible focus; logical Tab order; no color-only status |

Generated PNGs are visual targets, not pixel-accurate component specifications. Their actual output dimensions are recorded in the design pack. Use the tokens and responsive rules when implementing. English demo labels are provisional; the initial UI locale remains open. All people, project details, and contributions in the mockups are fictional.

## Information architecture and screen mapping

P0 owner navigation is Projects, Skills, Profile, Publish. Projects is the default work surface; do not add an analytics dashboard without a user need. Public navigation is Projects and Skills within the active publication. P1/P2 screens are excluded from P0 navigation.

| Surface | Purpose and primary action | Requirements |
| --- | --- | --- |
| Sign-in and first use | Sign in, then create a project; an empty state explains that a complete resume is unnecessary | P0 login; S1-02, S3-01 |
| Profile | Required display name, optional direction/bio/contact; save privately | S1-01 |
| Projects / project detail | Create/filter projects; identify title, project type and required role; separate project context from personal experiences | S1-02, S1-03 |
| Experience editor, mockup 01 | Confirm an experience; secondary Save draft | S3-01 through S3-03; S1-03 through S1-05 |
| Skill detail | Follow permitted supporting experiences and evidence; no proficiency score | S1-05, S3-02 |
| Publication review, mockup 02 | Select exact fields and confirmed records, review, then Publish snapshot | S1-06 |
| Public portfolio + Q&A, mockup 03 | Browse permitted work, ask a question, inspect the citation | S1-07; AC-S1-01 through AC-S1-05 |

The editor's Record type is distinct from Project type. Work contribution is an illustrative record-type label, not a finalized enum. The project owns its required role; the editor shows that context and must not make the project role optional during project creation. Confirmation requires title, project, record type, occurrence date/range and precision, and personal action. Results, metrics, skills and evidence are optional. Month-only dates must stay month-only; a conventional date picker must not silently insert a day.

The public preview shows only the selected experience and fields. Team outcome and contact are unchecked in the final sample. Production preview must render the same publication component and serialized field selection as the visitor route, including every selected section and any continuation below the viewport. Private notes are never selectable. An included evidence link remains a supplied link, not a third-party verification.

## Component mapping

| Product responsibility | Proposed components / pattern |
| --- | --- |
| Workspace navigation | Sidebar, Breadcrumb, Sheet for mobile |
| Experience editing | Field, Input, Textarea, Select/Combobox, Badge; explicit precision-aware date control |
| Evidence | Semantic list with labeled external links; Dialog for adding type, label, URL or entered statement |
| Draft and confirmation | Persistent status text, inline errors, Button; success announcement without losing focus |
| Publication | Checkbox tree/list with explicit field labels, step indicator, shared read-only portfolio renderer, AlertDialog for revocation |
| Public questions | Textarea, submit/cancel buttons, status region, semantic answer text, numbered citation links and source detail Sheet |
| Empty/error/loading | Empty, Alert, Skeleton; recovery action beside the affected content |

Use Lucide Vue icons if adopted during bootstrap. Avoid introducing a second component library for isolated widgets. Laravel validation and Policies remain authoritative regardless of component choice.

## State and recovery contracts

| State | Visible behavior | Recovery / boundary |
| --- | --- | --- |
| No projects | One short explanation and Create project | Personal/learning work is valid |
| Draft incomplete | Save draft stays available; confirmation identifies missing required fields | Focus first error and retain all values |
| Unsaved changes / save failure | Accurate saving/saved/failed indicator | Warn on leaving, retry without discarding input |
| Concurrent revision | Explain that a newer version exists | Preserve local draft; compare/reload before resubmission |
| Confirmed private | State Confirmed / Private, not Public | Separate publication action required |
| Published with source edits | State Published / Unpublished changes | Old snapshot stays active until explicit republish |
| Bad external evidence URL | Field validation or unavailable link indication | Portfolio stays readable; no automatic crawling/execution |
| No matching public information | State that the published records do not establish the requested fact | Offer relevant published project browsing, never infer inability or private existence |
| AI loading | Operational progress, Cancel | Do not display hidden reasoning; preserve input |
| Provider outage / rate limit | Explain the interruption, Retry when allowed | Manual project browsing remains available |
| Citation unavailable | Neutral unavailable response | No private title, snippet or existence disclosure |
| Publication revoked | Neutral unavailable page; owner sees revoked state | Stop pages, new answers, citations and dependent retrieval; already viewed copies cannot be recalled |

Archiving, source deletion, and publication revocation are separate actions. Do not implement archive as a silent access-revocation shortcut. Server-stored conversation history and in-flight answers must be reauthorized after revocation, not merely hidden by frontend controls.

## Responsive and accessible behavior

At 1024px and above, use the editor/evidence and portfolio/Q&A two-column layouts. At 768-1023px, collapse owner navigation and let the evidence area stack when needed. Below 768px, use one column: contribution fields, skills, evidence, then actions. Keep the mobile action bar above the keyboard/safe area without covering the final input. At 390px, use 16px gutters and avoid horizontal scrolling.

Public mobile order is profile, selected work, evidence, Q&A. Provide an Ask about this work jump link; citations open an accessible sheet and return focus to the originating link. Publication selection and preview use two sequential screens on mobile; a final review still precedes publishing. Do not hide privacy state or source labels to save space.

Implementation must verify contrast, keyboard-only completion, screen-reader labels/errors, 200% zoom, reduced motion and long Chinese/Japanese text. These are acceptance targets, not claims of tests completed on an app. Three desktop visual targets are delivered; mobile behavior is specified but no separate mobile raster mockup is included.

## Later phases

P1 adds private AI capture with editable drafts and at most two clarifying questions per turn, private interview preparation and one-question written practice, and opportunity-specific publications. P2 adds authenticated recipient grants, organizations, scoped human confirmations, authorized discovery and development conversations. The [future flows](../../design/2026-11-17/flows/04-later-phases.svg) document these without advancing their release phases.

## Deliverables and verification

Open the [design pack](../../design/2026-11-17/README.md) for three final mockups, four rendered UX diagrams, editable DOT sources, generation prompts, and a static review gallery. Canonical behavior remains in [Functional Requirements](KB_CareerAtlas_FunctionalRequirements.md), [User Experience](KB_CareerAtlas_Design_UserExperience.md), [Authorization](KB_CareerAtlas_Design_Authorization.md), and [Data Model](KB_CareerAtlas_Design_DataModel.md).

The original design pack remains the visual reference. The subsequent implementation supports English, Simplified Chinese, and Japanese interface labels while preserving user-authored content in its original language; see [Localization](KB_CareerAtlas_Localization.md). Static review routes are restricted to local/testing environments and use fictional fixtures; they do not publish data or invoke a model. Actual publication, frontend behavior and P0 Agent integration have separate acceptance gates. P1/P2 remain deferred.
