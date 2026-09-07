# Phase 2 — Catalogue & public website

**Status: complete.** 57 feature tests passing (33 new), verified in a browser against
a seeded catalogue of 58 products.

---

## What shipped

| # | Deliverable | Detail |
|---|---|---|
| 2.1 | Admin CRUD for the catalogue schema | Brands (DataTable + form), categories with tree view and per-category specification mapping, specification groups and attributes |
| 2.2 | Product editor | 8 tabs — Basics, Specifications, Prices, Images, Features, FAQs, Videos, Competitors. The spec form is generated from the category↔attribute mapping, so a new attribute appears with no code change |
| 2.3 | Price manager | 37-row state matrix with inline AJAX save, on-road calculated from ex-showroom + RTO + insurance + other, price history, CSV bulk import that reports bad rows rather than skipping silently |
| 2.4 | Filter cache + facets | `product_filter_cache` rebuilt by observers on product and spec-value writes; facet counts computed against it and cached, versioned so any catalogue write invalidates them |
| 2.5 | Home page | Hero + search, brands, popular tractors, HP bands, budget bands, implements, state price lists, stats |
| 2.6 | Listing template | Shared by tractors/implements/harvesters/tyres/farm-tools. AJAX filters, sort, pagination, `history.pushState` URLs — and the same URL renders server-side on a direct hit |
| 2.7 | Model detail | Gallery, key specs, full specs by group, on-road price breakdown, state price table, features, FAQs, videos, competitors, Product + AggregateOffer JSON-LD |
| 2.8 | Landing pages | Brand, HP band, budget band, popular/latest/upcoming collections, per-state price list |
| 2.9 | Search | Multi-word matching across model, brand and category, a synonym table for Hindi and misspellings, AJAX type-ahead, search logging and trending terms |
| 2.10 | Compare | 2–4 models, session-backed, shareable `/compare/a-vs-b` URL, differing rows highlighted, sticky first column |
| 2.11 | Demo catalogue | 25 brands, 30 categories, 39 specifications, 40 tractors + 18 implements, 378 price rows across 8 states |

## Verified in a browser

- Filtering HP 40–50 on `/tractors` narrowed 40 models to 21, updated the URL to a shareable one, and repainted the facet panel.
- Facet counts respond to the other active filters (6,5,4… → 3,2,2…), and picking any offered brand returns a non-empty result.
- Comparison: added two models from cards, the sticky bar produced `/compare/john-deere-5050-d-vs-farmtrac-60-powermaxx`, which renders 24 rows with 4 flagged as differing.
- Search: "mahindra 575" → 1 exact model; "mhindra 575" → same; "महिंद्रा" → 6 models.
- Admin: 25 products and 25 brands in server-side DataTables, 39 specifications, 30 categories, a product editor building 34 spec fields from its category, and a 37-row price matrix.
- No JavaScript errors on any page.

## Four bugs the tests and browser caught

1. **Two sources of truth for horsepower.** The Basics tab had `hp_min` and the
   Specifications tab had `Engine HP`; they could silently disagree, and the filter
   cache used one while sorting used the other. The Engine HP specification is now
   authoritative and `hp_min`/`hp_max` are derived from it on save.
2. **Caching Eloquent collections broke on a warm cache.** A serialised collection
   came back as `__PHP_Incomplete_Class` and took search down with a fatal error.
   Synonyms and filterable attributes now cache plain arrays; the home page caches
   counts and queries its small model sets live.
3. **Search did not match how people search.** A product row holds only the model
   name, so "mahindra 575" matched nothing. Search now requires every word to match
   the model, its brand or its category — so extra words narrow instead of failing,
   and "rotavator" finds all four rotavators, not one.
4. **The demo seeder was not idempotent.** Its lookup used a brand-prefixed slug
   that never matched the stored slug, so a re-seed would have duplicated all 58
   products. Verified: seeding twice leaves 58.

## Design decisions worth knowing

| # | Decision | Why |
|---|---|---|
| D1 | Product slugs are unique **per brand**, not globally | The URL is `/tractors/{brand}/{slug}`, so two makers can both sell a "242" without one becoming `242-2`. Changed the unique index while there is no production data. |
| D2 | Facet counts exclude their own facet | Counting each facet with every *other* filter applied is what makes the numbers mean "what you would get", and stops a user picking an option that returns nothing. |
| D3 | The AJAX filter endpoint returns the same Blade partial the page uses | A shared URL and an in-page filter cannot drift apart, and the no-JS path is the same code. |
| D4 | Zero-count facets render disabled, not hidden | The option still exists; hiding it makes the panel jump as filters change. |
| D5 | `on_road_price` is calculated on save, never entered | Four inputs, one derived total — it cannot be inconsistent. |
| D6 | Price history records only real movements | Re-saving an unchanged price does not pad the audit trail. |

## Known gaps, carried forward

- **No product images.** The pipeline (upload → WebP conversions → `media` rows) is
  built and wired into the editor, but the demo seeder ships no photography, so cards
  show the placeholder outline. Real images need the content team or the CSV importer.
- **Enquiry buttons are inert** on the detail page — the lead engine is Phase 3.
- **Dealers, reviews and EMI blocks** are absent from the detail page for the same reason (Phases 3–4).
- SEO meta is templated from product fields; the per-entity `seo_meta` editor is Phase 5.
- Search runs on `LIKE` against small tables. It is correct but will need the FULLTEXT
  index (already in the migration for MySQL) or a search server past ~50k products.
- Demo prices are plausible but **sample data** — replace before launch.

## Try it

```bash
php artisan migrate:fresh --seed
php artisan serve
```

- `/` — home
- `/tractors` — listing with live filters
- `/tractors/mahindra/575-di-xp-plus` — model detail
- `/tractors/hp/40-50-hp`, `/tractors/price/5-7-lakh` — band pages
- `/compare` — build a comparison
- `/search?q=mhindra 575` — synonym search
- `/admin/products` — the editor (admin@krishijunction.com / KrishiAdmin@2026)
