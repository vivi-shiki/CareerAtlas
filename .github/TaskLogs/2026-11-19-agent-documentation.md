# Put AI Agent first in reader documentation

## Scope

The user requested prominent AI Agent positioning in the README, official documentation, and tutorials, with the change published through the existing release pipeline. Preserve English, Simplified Chinese, and Japanese parity and describe existing capabilities accurately.

## Changes

- Each README leads with the AI Agent, explains owner assistance and source-cited visitor Q&A, and uses the existing localized Q&A screenshot as its primary visual. The first action links to the Agent tutorial.
- Documentation overview titles, introductions, primary links, and screenshots now foreground the Agent. The top navigation and the beginning of the guide sidebar expose a localized Agent tutorial entry.
- A new `ai-agent.md` in each locale walks through capture, preparation, opportunity assistance, and public Q&A, including the actual UI entry labels, example input, review/application, citations, and language behavior.
- Quick-start tutorials introduce AI-assisted capture and a first cited portfolio question. AI availability, separate owner/public controls, explicit human confirmation/publication, and manual fallback remain accurate. Existing screenshot fixtures and test-provider labels are preserved.
- The existing anonymous document crawler includes the new chapter. No backend, model, permission, or application homepage behavior changed.

## Verification

- `npm run build`, `npm run test:i18n`, `npm run test:docs`, and the PowerShell engineering documentation validator passed. Reader checks cover 42 localized pages, 39 feature IDs, 51 screenshot references, and 243 local links.
- The eight-case documentation Playwright suite passed, including local search, same-chapter language switching, the slow-loading regression, mobile navigation, and anonymous static-resource fetching.
- Focused rendered checks visited the Agent overview and tutorial in all three locales at 1440, 390, and 320 pixels: prominent heading, desktop/mobile entry, searchable Agent content, and no horizontal overflow or page/console errors. Representative desktop and mobile screenshots were visually inspected. Browser plugin not available; existing Playwright Chromium tooling was used.
- Existing unrelated case-library/importer changes were excluded. The user explicitly authorized committing, pushing, and the existing automatic deployment. Actual release identifiers and live verification belong in the ignored operations log.

## Status

FINISHED: AI Agent prominence and matching three-language tutorials implemented.

VERIFIED: local build, document integrity, translation checks, and rendered behavior listed above. Live release verification is recorded separately after the authorized push.
