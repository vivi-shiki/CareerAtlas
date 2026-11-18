# CareerAtlas product homepage

The public `/` route introduces the implemented product before sign-in. English, Simplified Chinese and Japanese copy and product images follow the existing language selector. `/login` retains the existing authentication form; private workspace routes retain their session guard.

## Reference and visual direction

Source: the [official shadcn-vue homepage](https://www.shadcn-vue.com/), inspected on 2026-11-18. The repository already selects shadcn-vue as its primary UI foundation. Borrowed visual traits: centered typographic hero, white canvas, restrained green actions, fine separators and product-led previews. No upstream application code, financial graphics, analytics claims or testimonials are copied.

- [Hero concept](01-hero-concept.png): generated with built-in ImageGen; native size 1402 by 1122.
- [Use cases and privacy concept](02-use-cases-concept.png): generated with built-in ImageGen; native size 1487 by 1058.
- [Exact generation prompts](generation-prompts.json).
- [Editable product illustration source](product-preview.html).

Tokens: white canvas, `#17221f` text, `#205641` secondary hero line, `#047857` primary action, `#d7dfda` separators, and `#eff7f3` privacy surface. System sans typography follows the application. Containers use a 1300px maximum and reduce to 16px mobile gutters. Primary controls have 8px corners, images 12px, without decorative gradients or shadows.

The two concepts define one continuous page: header, hero, three-tab product preview, three open use-case columns, privacy band, closing sign-in action, footer. Implemented UI remains semantic Vue/HTML. Only the illustrative product screenshots are raster images.

## Product illustrations

Nine JPEG images under `public/images/homepage/` combine `record`, `prepare`, and `share` with `en`, `zh-CN`, and `ja`. Each is a 1200 by 560 browser rendering of the standalone illustration source. Both interface labels and the fictional example prose are localized. These are illustrative marketing compositions, not screenshots of private records or claims of a live model run.

To reproduce: serve this directory with a temporary static file server, open `product-preview.html?lang=en&view=record` (substitute a supported language/view), and capture the region at x=0, y=0, width=1200, height=560. No API, credentials or database are involved. Keep this source outside the production public directory; only the final JPEGs are shipped. The preview images can be opened at full size, including from narrow phones.

## Copy and behavior contract

Allowed first-viewport copy consists of the brand, Product / Use cases / Your data links, native language names, Sign in, the two-line experience/evidence heading, its supporting sentence, Sign in to get started / Explore the workflow actions, the three workflow tab labels, and the explicit illustrative-example caption. The three catalogs contain their complete translations. Alt text and full-size image labels are accessible text rather than additional visible headlines.

Home requires no session API, account creation, model request or private data. Every sign-in action leads to `/login`. Product tabs support click, arrow keys, Home and End. Switching language preserves the selected tab and updates its image, alternative text, labels and page title. Language persistence uses the existing application mechanism. Anchor links reach the product, use cases and privacy sections. Native link behavior supports opening sign-in and full-size images in a new tab.

The page advertises the implemented record/capture, source-based preparation and text practice, selective publication, independent opportunity pages and public cited Q&A. It does not advertise organization features, audio practice, hiring predictions or automatic AI publication.

## Fidelity decisions

The initial visual pass increased the desktop hero type to 80px to match the concept hierarchy. Narrow Japanese navigation was compacted to avoid fragmented labels. The original open columns, white background, green actions, mint privacy band and outline icon family are retained.

Intentional extensions: preparation and sharing previews extend the initial record composition; the raster aspect ratio is 1200:560 to accommodate all three languages without clipping; a fictional owner and source-scope note clarify the illustration. Mobile stacks the use cases, keeps labels readable, and exposes full-size images through accessible links. Product imagery uses editable HTML rendered to JPEG instead of generated UI text so all three languages remain exact. These changes preserve the concept's layout and visual system while satisfying the requested localized imagery.

Verification and the final comparison ledger are recorded in the [homepage acceptance log](../../.github/TaskLogs/2026-11-18-homepage.md).
