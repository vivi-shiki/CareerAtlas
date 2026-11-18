# P1 Interface Acceptance Plan

Status: static review complete; behavior cases specified before API integration.

## Static comparison ledger

Reference: accepted [experience editor](../../design/2026-11-17/mockups/01-experience-editor.png) and the P0 workspace. Inspected actual capture desktop and opportunity mobile screenshots; six rendered cases cover capture, preparation and opportunity at 1440 and 390 pixels. All six have no horizontal overflow or browser exceptions. Browser plugin unavailable; existing local Playwright/Chromium used.

1. Reuse the fixed desktop sidebar, brand, profile area and mobile header; keep workspace navigation consistent.
2. Preserve the heading hierarchy, green primary actions, subdued borders and private-state badge. The two-column evidence context becomes a single flow below 900 pixels.
3. Keep labels above full-width fields, 44-pixel controls and multiline text areas. Mobile actions wrap without leaving the card.
4. Long titles and source excerpts wrap inside the available column. No fixed desktop form width is carried into mobile.
5. The source panel explains personal contribution versus team outcomes and shows confirmed evidence without making verification claims.
6. Static buttons are design references only. Production integration must add loading, error, unsaved-change, keyboard and source-refresh behavior and repeat visual checks in all three languages.

## Behavior cases to implement and run

| Case | Expected outcome |
| --- | --- |
| Capture notes, reload, edit, duplicate review, apply | Private draft persists; applying creates one unconfirmed experience; a reviewed merge preserves unknown fields |
| Experience history | Owner sees immutable versions and differences; dialog supports keyboard close/focus return |
| Preparation and answer | Topic/requirements/language/length and chosen sources persist; answer confirmation is explicit; old versions remain available |
| Source change | Stale warning appears; confirmation requires explicit source refresh; unsaved text survives a conflict |
| Written practice | One question at a time, retry replaces current attempt, stop prevents further submission, save retains and discard removes |
| Opportunity selection | Owner chooses and orders experiences and individual fields; private brief stays absent from preview/public/export |
| Independent publication | Two pages retain different selections; republishing one preserves the other; revoked page and chat become unavailable |
| Introduction and export | Introduction needs review; export uses current authorized snapshot; translated fields require review and exact source digest |
| Failures and navigation | API failure keeps text, double click does not duplicate mutations, unsaved navigation asks before discarding |
| Responsive languages | English, Chinese and Japanese at 1440/768/390/320; no overflow, clipped controls, missing focus or console exceptions |

AI drafting, citations, follow-up limits, cancellation and provider-failure fallback belong to the separate Agent gates. Passing manual workflows does not imply AI acceptance.
