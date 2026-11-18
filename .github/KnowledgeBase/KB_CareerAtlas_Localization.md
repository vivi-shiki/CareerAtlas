# Localization

CareerAtlas supports English (`en`), Simplified Chinese (`zh-CN`), and Japanese (`ja`). This is presentation behavior for the P0 product, including the public assistant. Existing feature identifiers and authorization rules remain unchanged.

## Selection and state

`resources/js/i18n/index.ts` configures Vue I18n in composition mode. Exact English message keys and three JSON catalogs live beside it. Message functions interpolate named parameters as text, without interpreting HTML or message syntax from records.

Language selection checks `careeratlas:locale` in localStorage, then supported browser languages, then English. Chinese regional variants map to Simplified Chinese; Traditional Chinese is not a separate translation. Disabled storage is tolerated. Selecting a language updates the document language, system page titles, navigation, forms, status and error messages, accessibility labels, dates, and counts. It does not remount the router view, mutate business fields, or discard unsaved input.

The selector is available on the product homepage, authentication pages, desktop and mobile workspace navigation, public portfolios, and unavailable pages. The homepage switches its illustrative product image and alternative text with the interface language, preserving the selected workflow tab. Its synthetic marketing examples are product-owned content; this does not translate private or published user records. Profile `preferred_language` remains a separate user-authored business field. A visitor's interface language does not rewrite the owner's profile preference.

## Content and assistant boundary

Translate only application-owned labels, enum display names, and system messages. Keep project titles, roles, descriptions, experience details, skill names and aliases, evidence, contact labels, source excerpts, questions, and existing model answers unchanged. Do not pass user data to the translation helper as message keys. Interpolated titles in accessible action labels are values, not keys.

The public assistant already instructs the model to answer in the question's language. Its starter questions, progress, error and cancellation states, citation field labels, and controls are localized. Follow-up questions and answers are model content, and previous turns retain their original language when the interface changes. A language change does not alter conversation IDs, source handles, quoted evidence, scope hashes, publication versions, cancellation, or revocation behavior.

P1 owner assistance follows the record's explicit target language, independently of the interface language. Generated capture, answer, introduction, feedback and translation prose is reviewed before application; selected-source excerpts remain verbatim. Source mapping may quote an original requirement. Proposal dates and record-type labels are rendered through the UI locale, while underlying ISO dates and enum values remain stable. All three READMEs describe the same P1 workflows.

Dates use Intl with the selected language and preserve recorded year/month/day precision. Date-entry values remain explicit ISO forms so switching languages cannot reinterpret stored dates. Counts use full named messages with singular and plural English variants and natural Chinese/Japanese ordering.

## API messages

The browser sends `Accept-Language`. `App\Support\Localize` uses the same JSON catalog files to present domain errors and validation messages; request language is not persisted to domain data. Supported language variants are normalized and unsupported languages fall back to English.

Localized errors retain stable status and `code` values. `message_key` and `error_keys` add the original application-owned English messages so the client can re-render an already-visible error after a language switch. Existing `message` and `errors` remain available to other clients in the requested language. Parameterized framework validation messages translate field labels and limits. Unknown messages fall back to their original text. These additive fields contain system messages, not hidden records or provider diagnostics.

HTML requests for unavailable paths render the localized application shell with HTTP 404 and no-store caching. API and MCP requests continue to receive JSON errors.

## Documentation and maintenance

`README.md`, `README.zh-CN.md`, and `README.ja.md` contain equivalent product sections and reciprocal language links. Engineering documentation remains English. Only the two translated README paths are exempt from the documentation validator's English character guard.

When adding UI text, add the same key to all three catalogs. Preserve named parameters. Translate at rendering time, especially for confirmation-dialog state and metadata arrays. Keep enum values, routes, field names, IDs, and API operation names stable. Add a complete sentence instead of concatenating translated fragments when word order or plurality matters.

## Verification

- `npm run test:i18n`: catalog/parameter parity and untranslated static production-template text.
- `npm run build`: TypeScript and production compilation.
- `php artisan test`: API localization, regional variants and fallback, plus the domain and Agent regression suite.
- `npm run test:e2e`: P0/P1 behavior and three-language browser acceptance, including narrow owner proposal previews.
- `tests/e2e/i18n.spec.ts`: language persistence, dynamic errors, input preservation, authoring, skills/aliases, evidence dialogs, preview, publication, source browsing, revocation, assistant history/errors/cancellation, and overflow checks at 1487, 768, 390, and 320 pixels.

Set `E2E_I18N_SCREENSHOTS` to an absolute directory outside the repository to retain the screenshot matrix. The browser suite uses fictional records and a clearly configured testing model; this does not demonstrate real-provider response quality. Keep actual provider evaluation evidence and machine-specific runtime information in local operations records.
