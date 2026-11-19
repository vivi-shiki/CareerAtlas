# Trilingual backend documentation and review navigation

## Scope

Make the product understandable from its GitHub repository: explain the actual Laravel implementation, link design and verification material, and retain the requested online examples near the beginning of all three READMEs. Keep the content reusable as product documentation.

## Changes

- Added matching Laravel responsibility summaries and design/specification indexes to the English, Simplified Chinese and Japanese READMEs. Existing online documentation and fictional portfolio links remain prominent.
- Added three chapters in each language: backend implementation with source links and design rationale, data relationships and publication lifecycle, and testing procedures with expected behavior and dated evidence.
- Connected the new chapters through the documentation overview and localized development sidebar.
- Scoped a mobile table minimum width to the new technical chapters. Tables scroll within the document instead of compressing Japanese and Chinese descriptions into narrow columns.
- Distinguished query-builder domain storage from Eloquent identity, confirmed records from public snapshots, implemented P0/P1 features from planned P2 work, and deterministic AI testing from live-model evaluation.

## Verification

- `npm run docs:build`, `npm run test:docs`, `npm run test:i18n`, and the PowerShell engineering documentation validator passed.
- Reader checks cover 51 localized pages, 39 feature IDs, 51 screenshot references and 297 local links. Source-map GitHub paths were also checked against the local repository.
- Playwright checked all nine added pages at 1440, 390 and 320 pixels: 27 rendered combinations, no document overflow, readable minimum-width mobile table cells, and no page errors or failed HTTP responses. Desktop/mobile sidebar navigation and same-chapter language switching passed. Representative desktop and mobile screenshots were inspected.
- The local preview server was restarted after rebuilding because its previous asset inventory referenced removed hashed bundles; the final browser pass used the fresh build.
- The four linked fictional public examples returned successful JSON responses. Environment-specific URLs remain in the user-requested README links; operational details remain local.
- Backend business logic was not changed. The backend suite was not rerun locally for this documentation edit; the existing CI workflow provides commit-specific verification.
- Unrelated case-library and importer work was excluded from this change.

## Status

FINISHED: matching README review navigation and technical chapters in all three languages.

VERIFIED: local document build, integrity, localization and rendered checks described above. Remote publication is verified separately after push.
