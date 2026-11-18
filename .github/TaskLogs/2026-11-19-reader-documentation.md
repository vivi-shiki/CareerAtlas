# Trilingual reader documentation

## Scope and structure

The user requested concise English, Simplified Chinese, and Japanese READMEs covering product purpose, architecture, usage, and development; systematic screenshot-based guides for all features; a homepage entry; and publication through the existing deployment pipeline.

The root READMEs now provide a short product introduction, five capability bullets, three-step usage, a stack table, initial local commands, and contribution links. The case-library entry remains linked to its independently maintained import guide. Detailed reader content lives in thirteen equivalent chapters per language under `docs/`. Engineering contracts remain authoritative and English.

## Sources and implementation

- [Memos README](https://github.com/usememos/memos) informed the concise introduction, screenshot, feature list, and quick-start/documentation split.
- [Immich README](https://github.com/immich-app/immich) informed the language links and separation between repository orientation and full guides.
- [VitePress internationalization](https://vitepress.dev/guide/i18n) and [local search](https://vitepress.dev/reference/default-theme-search) informed the static multilingual documentation implementation. The stable VitePress dependency is pinned; the application stack was preserved.
- Actual product code, contracts, and P0/P1 implementation notes determine feature claims. The coverage table maps all 39 requirement IDs: 10 P0 and 18 P1 implemented, 11 P2 planned. No roadmap date is promised.
- VitePress builds into ignored `public/docs`, included by the existing release allowlist. `npm run build` builds both app and docs. A post-build step externalizes boot scripts instead of weakening the production CSP.
- Explicit `.html` links avoid reliance on directory-index server changes. A compact language selector preserves the current chapter; it also updates the product's saved locale. Search is local and requires no hosted search service.
- The homepage links to the current language's guide. Product-home links return from the documentation. Existing sign-in behavior is preserved.
- Fictional localized data is created through actual owner operations in an isolated local test environment. An opt-in Playwright capture script makes screenshots reproducible. Real user data and operational details are excluded.

## Verification

Browser plugin not available; the repository's Playwright Chromium workflow provides rendered verification. Target flow: public homepage, localized documentation, local search, chapter navigation, language switching, and return to the product homepage.

The initial browser check caught malformed corresponding-language links caused by treating a locale prefix as a file URL. The final explicit language selector preserves chapters and `.html` entry points. Desktop and 390/320-pixel mobile checks cover all three locales. Production CSP is applied to the local documentation response during interaction checks.

- `npm run build`: application type checking, Vite production assets, VitePress pages, and CSP finalization passed.
- `npm run test:i18n`: three catalogs, 600 messages each, parameter parity and template coverage passed.
- `npm run test:docs`: 39 localized chapters, 39 requirement IDs, 45 image references and 210 local links passed. There are 42 distinct localized screenshots.
- PowerShell documentation validator: engineering/root links, requirement IDs, English baseline, and public/local boundary passed.
- Playwright homepage and documentation suites: 16 homepage cases and 8 documentation cases passed. Page identity, nonblank content, absence of framework overlays, console health, loaded images, search, corresponding-language navigation, return-home language persistence, and responsive navigation passed. All rendered documentation links/assets were fetched anonymously.
- Opt-in screenshot capture: three locale workflows passed, including real owner operations and queued fake-provider draft/Q&A execution. Final desktop and mobile documentation images and representative product screenshots were visually inspected. A transient sidebar-closing frame in the first mobile QA capture was resolved by waiting for the closed state and disabling screenshot animations.

Automated model fixtures illustrate UI workflows only; they do not establish live model quality. No backend/domain behavior changed. The existing CI performs its full backend/release checks before production publication. Deployment identifiers and live operational evidence belong in the ignored runbook.

The first live interaction pass exposed a slow-network race: VitePress changes the address before the lazy chapter data arrives. Reading the previous page's metadata during an immediate language switch could navigate to the translated overview. The selector now derives the chapter from the current URL. A regression case holds the incoming chapter bundle and switches language before it completes; the intended chapter is preserved. The final eight-case documentation suite passed after this correction.

## Status

FINISHED: trilingual reader documentation, concise READMEs, screenshot assets, homepage navigation, and release-build integration implemented.

VERIFIED: local build, documentation integrity, localization, and rendered interaction checks listed above. The user authorized the main-branch push and existing automatic publication; live revision checks are recorded separately in the local operations log.
