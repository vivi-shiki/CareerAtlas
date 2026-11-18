# P1 Implementation Across Ten Acceptance Gates

Status: all ten gates passed locally, 2026-11-18. The user authorized all P1 development after the staged P0 baseline commits. Scope is AI capture, duplicates/history, interview preparation/answers/text practice, independent opportunity publications, page-scoped Q&A, and Markdown export. Three-language support is preserved. No P2 or cloud deployment is included.

| Gate | Status | Evidence / next work |
| --- | --- | --- |
| 1 Backend design, atomic operations, tests, MCP | Passed for manual foundation | [P1 contract](../KnowledgeBase/KB_CareerAtlas_Implementation_P1.md); 34 new atomic operations, 75 total; AI runs remain at gates 9-10 |
| 2 Backend completeness review | Passed for manual foundation | Reviewed owner isolation, stale versions, source freshness, immutable answer/history records, independent publication and allowlisted export; AI behavior remains pending |
| 3 Backend and real MCP acceptance | Passed for manual foundation | PostgreSQL regression: 81 tests / 1,128 assertions before the extra retention case. Real MCP: 75 tools / 46 successful or expected-error calls. Retention verification recorded below when complete |
| 4 Static layouts and visual review | Passed | Six desktop/mobile renders; [comparison ledger](2026-11-18-p1-ui-acceptance.md) |
| 5 Frontend behavior tests | Passed | Four primary browser cases written before wiring, expanded with failure, keyboard and language coverage |
| 6 Frontend test completeness review | Passed | Backend covers isolation/atomicity; UI covers persistence, source changes, keyboard, failure retention and 12 language/width combinations |
| 7 Non-AI UI integration | Passed | Capture, history, prepared answers/practice and independent opportunity selection/preview/export are wired |
| 8 Combined tests and browser workflow | Passed for manual foundation | Full run: 74/89 passed; recheck: all 30 P0 Agent/P1 cases passed after worker refresh and date-button sizing repair. Together all 89 distinct cases passed. 36 three-language/four-width screenshots had no overflow or browser exceptions |
| 9 Owner Agent design and test review | Passed | [Owner Agent contract](../KnowledgeBase/KB_CareerAtlas_Design_OwnerAgent.md), deterministic failure/citation/freshness tests specified before implementation; fictional live set prepared |
| 10 Owner Agent integration and acceptance | Passed | 91 backend tests; nine final focused owner tests; 97-case browser regression plus 44-case final recheck, covering 101 distinct cases; 80 MCP tools / 53 calls; 20 reviewed fictional live cases after explicit refinements; three-language real browser workflow; staged commits below |

Do not mark a gate passed using mocked or unrelated evidence. Record scope decisions and failures honestly. Preserve existing records and credentials; operational reports and actual model/environment details stay local.

## Backend review at the manual foundation gates

S2-01/02/08 have private briefs, pasted requirements and immutable answer versions with source-change warnings. S2-06 has one-question practice, retry, stop, explicit retention and expiry cleanup. S2-03/04/05/07 require the later Agent slice; current manual answers and source references are their foundation. S3-04 has editable capture drafts; S3-05 has owner-reviewed duplicate candidates and version-checked merge; S3-06 exposes revision diffs. S4-01/03/05/07 have private opportunity briefs, explicit ordered field selection, independent snapshots and Markdown exports. S4-02/04 await AI suggestions; S4-06 reuses the existing publication-scoped public Agent and its access checks.

The first regression found MCP's maximum page size still capped tool discovery at 50. Both maximum and default are now 100, and protocol acceptance compares discovered names against both catalogs. Manual captures preserve manual provenance; translated exports reject duplicate field keys. No requirement URL is fetched. Source withdrawal removes dependent introduction text and export content.

The expanded focused P1 suite passed 12 tests / 196 assertions, including expiry cleanup that preserves explicitly saved practice. Pint passed. This backend commit is a foundation, not completion of P1; UI and AI acceptance remain required.

## Manual interface acceptance

The P1 date-range toggle inherited a 28-pixel minimum height; a scoped rule now gives it a 44-pixel target. All twelve language/width cases passed on recheck. Six manual P1 workflows cover capture persistence/application, answer history/practice, opportunity preview/export/revoke, stale sources, network failure/draft navigation, and keyboard history dialogs. The first three P0 Agent failures came from a stale local worker reading the pre-change projection; the complete twelve-case Agent rerun passed after refreshing that worker. Raw initial failures are retained locally.

Inspected actual Japanese 320-pixel preparation and Chinese desktop opportunity captures against the accepted static layout. Form groups, selected-source wrapping, independent preview actions, typography and sidebar hierarchy remain consistent. User-authored content stays in its original language; interface translation does not rewrite career facts.

## Owner assistance acceptance

The final Agent slice completes S2-03/04/05/07, S3-04 and S4-02/04/07 with six explicit purposes: capture, source mapping, answer drafting, introduction drafting, practice feedback and export translation. Five owner AI lifecycle operations bring the catalog to 80 tools. Real MCP discovery and 53 workflow calls pass, including owner proposal start/read/apply/cancel, expected rejection cases and the manual P1 workflow. The protocol client talks to the application; its configured model is a deterministic test double for repeatable acceptance.

Full PostgreSQL regression passes 91 tests / 1,204 assertions. After the final language/protocol refinements, the focused owner Agent suite passes nine tests / 58 assertions. It covers source and owner isolation, exact citations, bounded runs, idempotent replay, source changes, cancelled output, selected-field context, export validation and explicit private application. Pint passes for 89 files. The build/type check and all three 564-key catalogs pass.

The final full browser run passes 97/97 cases. After the final experience ordering, historical source display and readable proposal formatting changes, all 44 focused P1/owner AI/publication/recovery cases pass. This includes four newly added cases, giving 101 distinct passing browser cases across the two runs, not a single 101-case run. Three UI languages remain independently selectable from the generated-content language. The [interface ledger](2026-11-18-p1-ui-acceptance.md) records visual review and the corrected raw-code/date display. Documentation validation passes for 63 Markdown files, including identifier consistency, local links and the shared/local boundary.

## Fictional live model evaluation

Twenty distinct cases cover the six purposes in English, Chinese and Japanese, plus injected instructions and missing evidence. Final outputs were inspected for source attribution, personal versus team contributions, names/numbers/dates, unknown facts, language, optional questions, four feedback dimensions and export scope. All twenty have accepted final results after explicit refinements and targeted rechecks; this is not a claim of twenty first-attempt successes in one batch.

Early runs exposed invalid empty function-argument serialization, provider enum incompatibility, upstream rate limits, invalid export field keys, a failed request reported as a timeout, English prose in Chinese/Japanese tasks, and an inaccurate translation of pending work. Fixes preserve empty argument objects, use compatible enums and exact export-key declarations, pace the evaluation, reinforce per-request language instructions, reject obvious untranslated long prose and distinguish pending work from work in progress. Original failures and raw outputs remain local. There is no automatic paid fallback or automatic provider retry.

The reviewed final samples preserve the fictional twelve-test contribution and October date; do not claim Kubernetes experience without evidence; ignore the injected award request; and flag insufficient evidence rather than judging ability. Feedback challenges an unsupported entire-platform claim. Export retains the exact authorized field set. Original source quotations, requirement quotations and technical names may remain in their original language. Model output still requires owner review; sample acceptance does not establish universal factual accuracy.

A separate real-provider browser walkthrough passes capture generation, citation inspection and explicit private-draft application in all three languages, with six before/after screenshots and no horizontal overflow or page exceptions. Its initial Chinese language rejection is retained before the successful three-language recheck. Three further renders replay the already-evaluated real outputs to inspect the final presentation at desktop and 320 pixels; these are rendering checks, not additional live model completions.

## Release boundary

All eighteen P1 features are implemented locally. P2, audio practice and cloud release remain outside this task. The provider is disabled for unapproved owners; current environment allowlists, actual services, model identifiers, credentials, raw reports and machine commands stay in ignored local documentation. No cloud resources, billing settings or remote Git refs were changed. See the staged commit sequence below for review boundaries.

## Staged commit sequence

The [P0 baseline sequence](2026-11-18-baseline-commits.md) precedes this work. P1 is split into dependent reviewable slices rather than a single application-and-documentation commit.

| Commit | Scope | Review purpose |
| --- | --- | --- |
| `b6611e3` | Manual P1 domain, additive schema, atomic HTTP/MCP operations and backend tests | Establish ownership, versioning, history, independent publication and export invariants before UI wiring |
| `cced65b` | Static design references, three-language manual interfaces and browser cases | Establish editable workflows, responsive layouts and failure/input-retention behavior before owner AI |
| `93cef0f` | Bounded proposal lifecycle, provider protocol, six-purpose review UI, deterministic/live evaluation tooling and final rendering fixes | Review model scope, explicit private application and complete P1 integration independently of documentation |
| This documentation commit | Three equivalent READMEs, current architecture/build/localization references, backlog and acceptance evidence | Explain the implemented product, reusable setup, verified behavior and remaining scope |

These commits do not include ignored local operations notes, raw provider responses, credentials, generated screenshots or deployment configuration. Each code slice has its own focused acceptance evidence; the final integration tests cover the combined result.
