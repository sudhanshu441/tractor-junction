# Phase 5 — Content, SEO, multilingual, API & launch

**Status: complete.** 226 tests passing (54 new), verified in a browser across
both languages, the CMS, the reports dashboard and the API.

---

## What shipped

| # | Deliverable | Detail |
|---|---|---|
| 5.1 | CMS | Posts with categories, tags, scheduling and moderated comments; static pages on root slugs; offers; a two-level menu builder; and five small masters (videos, banners, FAQs, testimonials, blog categories) behind one config-driven controller |
| 5.2 | SEO engine | `SeoService` resolving override → template → default for every field; JSON-LD for Product, Offer, Review, FAQ, Breadcrumb, AutoDealer, Article and VideoObject; chunked sitemaps with hreflang; generated `robots.txt`; redirect manager with chain-flattening; a 404 log that turns into a redirect in one click |
| 5.3 | Multilingual | Full Hindi pass, `/hi` URL tree, hreflang on every page, a language switcher, and admin-editable interface strings that override the shipped file without a deploy |
| 5.4 | Reports | Admin dashboard on Chart.js (traffic, funnel, leads by type and state, top pages, zero-result searches), streamed CSV exports, GA4/GTM with a consent-first banner |
| 5.5 | REST API v1 | Sanctum tokens over the same OTP flow, public catalogue and marketplace reads, private account endpoints, documented in `docs/14-API-REFERENCE.md` |
| 5.6 | Performance | Self-hosted fonts, vendored Chart.js, `kj:warm` cache warmer, and a query-budget test that fails when a page starts an N+1 |
| 5.7 | Security & operations | Security headers with a real CSP, verified nightly backups with pruning, and runbooks in `docs/16-RUNBOOKS.md` |
| 5.8 | Launch | `docs/15-LAUNCH-CHECKLIST.md`, with blocking items separated from nice-to-haves and the known gaps listed rather than buried |

## How the bilingual routing works

The public routes are registered **twice** — bare for English, and again under
`/hi` with an `hi.` name prefix. A `LocalizedUrlGenerator` rewrites the route
name at generation time, so `route('products.show', …)` returns the Hindi URL on
a Hindi page and the English one everywhere else.

That choice matters for two reasons. Rewriting at *generation* time rather than
registration time keeps `route:cache` working. And no view needed changing —
forty templates kept their existing `route()` calls and became bilingual.

English URLs did not change shape, so existing links and rankings survive.

## Bugs caught before shipping

1. **`DB::afterResponse()` does not exist.** I used it for deferred writes in the
   redirect middleware; it is not a Laravel method. Replaced with the real
   `defer()` helper. Caught by the first SEO test that hit a redirect.
2. **404s were never logged.** The logging ran in the middleware's `handle()`,
   but most 404s on this site come from `firstOrFail()` inside a controller —
   that exception becomes a response *above* every route middleware. Moved to
   `terminate()`, which sees the status the visitor actually got.
3. **Laravel's stock `public/robots.txt` shadowed the generated route.** The web
   server serves the static file first, so the carefully generated robots.txt was
   never reached. Deleted the file.
4. **`Dealer` had no `masked_mobile` accessor** although the Phase 4 admin list
   already called it — a restricted role saw an empty cell rather than a masked
   number. Extracted a `MasksMobile` trait and applied it to all three models.
5. **The translation loader was being overwritten.** Laravel's own
   `TranslationServiceProvider` registers after the app's, so a `singleton()`
   binding was silently replaced. `extend()` is the correct hook.
6. **Titles were truncated mid-separator**, leaving `… Review |`. The trim now
   cuts on a word boundary and strips trailing separators, and the product
   template is short enough to fit its own suffix.
7. **Multi-line `@json()` in a Blade directive fails to compile.** Blade's
   argument parser cannot handle a nested array across lines. Schema is now built
   in a `@php` block first.
8. **Empty chart frames read as broken.** A period with no leads rendered an
   empty box; it now renders a sentence.

## Design decisions worth knowing

| # | Decision | Why |
|---|---|---|
| D1 | Fonts are self-hosted, not loaded from a CDN | A third-party font host costs a DNS lookup, a TLS handshake and a round-trip before any text paints. On rural 3G that is the slowest thing on the page. 548 KB of Latin, Latin-Extended and Devanagari subsets. |
| D2 | Analytics is denied by default | Consent Mode is set to denied *before* the tag loads, so an undecided visitor's first pageview is already cookieless rather than retroactively excused. No GA ID configured means no tag and no banner at all. |
| D3 | Analytics never loads in the admin panel | Staff activity is not audience data, and a consent banner over a working screen is just in the way. |
| D4 | A schema field we cannot state truthfully is omitted | A price or rating that does not match the page is a manual action waiting to happen, not a ranking win. `offers` appears only with a real price; `aggregateRating` only with approved reviews. |
| D5 | An empty FAQ page emits no schema | An empty `FAQPage` is a structured-data error, not an empty section. |
| D6 | The interface language is editable from the admin panel | Files stay the source of truth so a new string ships and works immediately; a `language_lines` row overrides it and survives the next deploy. |
| D7 | Editor pages claim root slugs, with a reserved list | `/about-us` reads better than `/pages/about-us`, but an editor must not be able to publish `/tractors` and shadow the catalogue. |
| D8 | Five small masters share one controller | Eight near-identical CRUD controllers is five hundred lines of copy with five places to forget an activity log. Anything with real behaviour keeps its own. |
| D9 | The API has no password endpoint | Farmers sign in with a mobile number and a code. A second credential is only a second thing to steal. |
| D10 | Contact numbers are never in an API list response | The same rule the website enforces: a number is revealed only after the person asking has verified their own. |
| D11 | A query budget is a test, not a dashboard | An N+1 does not fail anything — it just makes the page slower as the catalogue grows. `PerformanceBudgetTest` makes that a failing build instead. |
| D12 | `robots.txt` disallows everything outside production | A staging site indexed is worse than a staging site invisible. |

## Schema addition

`language_lines` (locale, group, key, value) — specified in the ERD alongside
`translations` but never migrated. `translations` carries content fields (a
product's Hindi name); this carries interface copy.

## Known gaps, carried forward

- **Model content is not translated.** The `translations` table and the UI-string
  editor both exist, but product names, specification labels and category names
  still render in English on Hindi pages. That is data entry, not code — the
  filter panel in the screenshot shows it plainly.
- **CSP still allows `unsafe-inline` for scripts.** Every inline block would need
  a nonce. Worth doing; not a silent change.
- No refund flow, no lender portal, no insurance quote engine — unchanged from
  Phase 4 and listed in the launch checklist.
- Comment replies are stored and rendered but there is no reply box for readers.
- The blog editor is a plain HTML textarea. A WYSIWYG was not in scope and would
  need a vendored editor.

## Try it

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan kj:warm
php artisan serve
```

- `/news`, `/faq`, `/videos`, `/offers`, `/contact`, `/about-us`
- `/hi` — the whole site in Hindi
- `/robots.txt`, `/sitemap.xml`
- `/admin/reports` — the dashboard
- `/admin/seo/redirects` — redirects and the 404 log
- `/admin/seo/translations` — edit any interface string
- `/api/v1/products`, `/api/v1/emi` — no token needed
