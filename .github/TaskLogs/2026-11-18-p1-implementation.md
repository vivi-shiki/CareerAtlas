# P1 Implementation Across Ten Acceptance Gates

Status: in progress. The user authorized all P1 development after the staged P0 baseline commits. Scope is AI capture, duplicates/history, interview preparation/answers/text practice, independent opportunity publications, page-scoped Q&A, and Markdown export. Keep three-language support. No P2 or cloud deployment is included.

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
| 10 Owner Agent integration and acceptance | Pending | Real provider evaluation, UI workflow, final regression and staged commits |

Do not mark a gate passed using mocked or unrelated evidence. Record scope decisions and failures honestly. Preserve existing records and credentials; operational reports and actual model/environment details stay local.

## Backend review

S2-01/02/08 have private briefs, pasted requirements and immutable answer versions with source-change warnings. S2-06 has one-question practice, retry, stop, explicit retention and expiry cleanup. S2-03/04/05/07 require the later Agent slice; current manual answers and source references are their foundation. S3-04 has editable capture drafts; S3-05 has owner-reviewed duplicate candidates and version-checked merge; S3-06 exposes revision diffs. S4-01/03/05/07 have private opportunity briefs, explicit ordered field selection, independent snapshots and Markdown exports. S4-02/04 await AI suggestions; S4-06 reuses the existing publication-scoped public Agent and its access checks.

The first regression found MCP's maximum page size still capped tool discovery at 50. Both maximum and default are now 100, and protocol acceptance compares discovered names against both catalogs. Manual captures preserve manual provenance; translated exports reject duplicate field keys. No requirement URL is fetched. Source withdrawal removes dependent introduction text and export content.

The expanded focused P1 suite passed 12 tests / 196 assertions, including expiry cleanup that preserves explicitly saved practice. Pint passed. This backend commit is a foundation, not completion of P1; UI and AI acceptance remain required.

## Manual interface acceptance

The P1 date-range toggle inherited a 28-pixel minimum height; a scoped rule now gives it a 44-pixel target. All twelve language/width cases passed on recheck. Six manual P1 workflows cover capture persistence/application, answer history/practice, opportunity preview/export/revoke, stale sources, network failure/draft navigation, and keyboard history dialogs. The first three P0 Agent failures came from a stale local worker reading the pre-change projection; the complete twelve-case Agent rerun passed after refreshing that worker. Raw initial failures are retained locally.

Inspected actual Japanese 320-pixel preparation and Chinese desktop opportunity captures against the accepted static layout. Form groups, selected-source wrapping, independent preview actions, typography and sidebar hierarchy remain consistent. User-authored content stays in its original language; interface translation does not rewrite career facts.
