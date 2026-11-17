# Three-language product and README localization

Status: VERIFIED.

## Request and scope

Add Chinese, Japanese, and English to the existing P0 application and README, and retest interactions and layout. Preserve the existing product scope, user-authored content, authorization, publication snapshots, and local/shared documentation boundary.

## Implementation

- Added Vue I18n, three shared catalogs, language selection with browser persistence, document language/title updates, localized date/count/enum display, and localized accessibility labels.
- Localized navigation, forms, loading and error states, confirmation dialogs, publication selection/preview, evidence and skill interactions, and public assistant controls/progress/recovery.
- Added API language negotiation for domain/validation/unavailable responses, with stable error codes and original message keys for client-side language changes.
- Kept original record text, source quotations, existing questions and answers, field keys, enums, and route identifiers unchanged.
- Added equivalent English, Chinese, and Japanese READMEs and a narrowly scoped documentation-validator exception for the two translations.
- Added a catalog/template guard, API tests, and three-language browser workflows. Updated the [localization contract](../KnowledgeBase/KB_CareerAtlas_Localization.md).

## Findings and repairs

1. Unknown browser URLs previously returned an English JSON 404 instead of the application. They now render the localized unavailable page with HTTP 404 and no-store headers; JSON API behavior is preserved.
2. The language selector initially pushed the menu onto a separate row at 320px. Narrow-screen header sizing now keeps brand, language, and menu usable together.
3. Narrow breadcrumbs split short English words because flex items shrank. Breadcrumbs now wrap whole items while long user titles remain readable.
4. Buttons allow translated labels to wrap and retain mobile touch-target height. Forms, dialogs, public sources, and publication previews use natural height.

## Visual review

The original contribution-editor, publication-preview, and public-portfolio images remain the visual reference. Browser captures use fictional content and normal, empty, error, and long-text states.

| Area | Review result |
| --- | --- |
| Layout | Desktop sidebar and split editor/public reading columns retained; mobile fields and evidence stack |
| Typography | System font hierarchy retained; Chinese/Japanese labels and mixed-language record text remain readable |
| Color | Emerald actions, mint selection, neutral borders and surfaces retained |
| Controls | Native language selector, wrapping action labels, accessible dialog close and keyboard controls |
| Spacing | Long content expands vertically instead of clipping or horizontal scrolling |
| Responsive behavior | Checks at 1487, 768, 390, and 320 pixels; header and breadcrumb defects repaired |

## Verification

Backend: 70 tests / 872 assertions passed. Catalog guard: 402 messages in each of three catalogs, matching interpolation parameters and no untranslated static production-template text. Production build, TypeScript, PHP formatting, and documentation checks passed. MCP: 41 tools discovered and 26 workflow calls passed.

Live-model acceptance separately asked one fictional source-backed question in each language. All three completed, used the requested answer language, and opened their cited source. Nine captures across desktop and two phone widths showed no horizontal overflow. This is a three-question integration check, not a general quality or accuracy guarantee; the previous 20-question evaluation remains separate.

Browser regression: all 70 tests in the first full run passed. The final expanded localization suite passed all 10 tests after the last unavailable-page and capture refinements, including browser-language variants, unsupported-language fallback, disabled storage, and mobile navigation. This covers 71 distinct current browser cases: the original 61 plus 10 localization cases. No browser page errors were observed in the instrumented authoring and live-provider workflows.

Retained visual evidence contains 144 captures (12 screen/state combinations, three languages, four widths) plus nine live-answer captures. Automated checks found no horizontal document overflow or controls outside the viewport; representative screenshots were also inspected against the original design references. Screenshots of dialogs use the viewport rather than a misleading full-page overlay capture.

No Browser plugin was available, so verification used the repository's installed Playwright/Chromium runtime. Firefox, WebKit, and physical devices have not been tested in this task. Actual screenshot paths, runtime details, and provider records remain outside shared documentation.

No commit, push, cloud deployment, or real personal-data submission was performed for this request.
