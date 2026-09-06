# Krishi Junction — Sitemap, Information Architecture & URL Plan

URL rules: lowercase, hyphenated, no trailing `.html`, no query strings for indexable
facets (they become path segments where SEO matters), Hindi mirrored under `/hi/…`.

---

## 1. Public website tree

```
/                                       Home
│
├── /tractors                           All new tractors (listing + filters)
│   ├── /tractors/brand/{brand}                     e.g. /tractors/brand/mahindra
│   ├── /tractors/{brand}/{model}                   Model detail  (mahindra/575-di-xp-plus)
│   ├── /tractors/{brand}/{model}/specifications
│   ├── /tractors/{brand}/{model}/price             On-road price by city
│   ├── /tractors/{brand}/{model}/reviews
│   ├── /tractors/{brand}/{model}/dealers
│   ├── /tractors/{brand}/{model}/videos
│   ├── /tractors/popular
│   ├── /tractors/latest
│   ├── /tractors/upcoming
│   ├── /tractors/mini-tractors
│   ├── /tractors/4wd
│   ├── /tractors/ac-cabin
│   ├── /tractors/hp/{range}                        /tractors/hp/40-50-hp
│   ├── /tractors/price/{range}                     /tractors/price/under-5-lakh
│   └── /tractors/price-list/{state}                /tractors/price-list/rajasthan
│
├── /implements                         Farm implements
│   ├── /implements/category/{category}             /implements/category/rotavator
│   ├── /implements/brand/{brand}
│   └── /implements/{brand}/{model}
│
├── /harvesters
│   └── /harvesters/{brand}/{model}
│
├── /tractor-tyres
│   ├── /tractor-tyres/brand/{brand}
│   └── /tractor-tyres/{brand}/{model}
│
├── /farm-tools
│   └── /farm-tools/{brand}/{model}
│
├── /used                               Used marketplace
│   ├── /used/tractors
│   ├── /used/implements
│   ├── /used/harvesters
│   ├── /used/tractors/{state}                      /used/tractors/uttar-pradesh
│   ├── /used/tractors/{state}/{district}
│   ├── /used/tractors/brand/{brand}
│   ├── /used/listing/{slug}-{ref}                  Listing detail
│   └── /sell                                       Sell your machinery (multi-step)
│
├── /compare                            Compare landing
│   └── /compare/{slug-vs-slug[-vs-slug]}
│
├── /dealers                            Dealer locator
│   ├── /dealers/{state}
│   ├── /dealers/{state}/{district}
│   ├── /dealers/brand/{brand}
│   ├── /dealers/brand/{brand}/{state}
│   └── /dealers/{slug}                             Dealer detail
│
├── /loan                               Tractor loan hub
│   ├── /loan/apply
│   ├── /loan/emi-calculator
│   ├── /loan/emi-calculator/{brand}/{model}
│   ├── /loan/lenders
│   └── /loan/eligibility
│
├── /insurance
│   ├── /insurance/enquiry
│   └── /insurance/partners
│
├── /offers
│   └── /offers/{slug}
│
├── /videos
│   ├── /videos/category/{category}
│   └── /videos/{slug}
│
├── /news                               Agriculture & industry news
│   ├── /news/category/{category}
│   └── /news/{slug}
├── /blog
│   ├── /blog/category/{category}
│   ├── /blog/tag/{tag}
│   └── /blog/{slug}
│
├── /reviews                            All user reviews feed
├── /faq
├── /about-us
├── /contact-us
├── /become-a-dealer
├── /privacy-policy
├── /terms-and-conditions
├── /disclaimer
├── /sitemap                            HTML sitemap
│
├── /login   /register   /logout   /forgot-password
├── /account                            Customer panel (auth)
│   ├── /account/dashboard
│   ├── /account/listings
│   ├── /account/listings/{id}/edit
│   ├── /account/enquiries
│   ├── /account/loan-applications
│   ├── /account/wishlist
│   ├── /account/saved-searches
│   ├── /account/reviews
│   ├── /account/notifications
│   └── /account/profile
│
├── /dealer                             Dealer panel (auth: dealer)
│   ├── /dealer/dashboard
│   ├── /dealer/inventory
│   ├── /dealer/used-listings
│   ├── /dealer/leads
│   ├── /dealer/leads/{id}
│   ├── /dealer/branches
│   ├── /dealer/staff
│   ├── /dealer/reviews
│   ├── /dealer/subscription
│   ├── /dealer/reports
│   └── /dealer/profile
│
├── /admin                              Admin panel (auth: staff) — see doc 05
│
├── /hi/…                               Hindi mirror of every public route
├── /sitemap.xml  /sitemap-{n}.xml  /robots.txt
└── /api/v1/…                           REST API
```

---

## 2. Navigation

### Header (desktop)
`Logo` · **New Tractors** ▾ · **Used Tractors** ▾ · **Implements** ▾ · **Compare** ·
**Dealers** · **Loan & EMI** ▾ · **News** ▾ · `Search` · `EN/हिं` · `Sell` (primary CTA) · `Login`

Mega-menu contents:

| Menu | Columns |
|---|---|
| New Tractors | By brand (logos) · By HP (20–30, 30–40, 40–50, 50–60, 60+) · By budget · Popular / Latest / Upcoming / Mini / 4WD |
| Used Tractors | By brand · By state · By budget · Verified only · Sell your tractor |
| Implements | Rotavator, Cultivator, Plough, Seed drill, Thresher, Sprayer, Trailer, Baler, Leveller · By brand · Harvesters · Tyres |
| Loan & EMI | EMI calculator · Apply for loan · Eligibility · Lenders · Insurance |
| News | Agriculture news · Tractor news · Blog · Videos · Govt schemes · Mandi prices |

### Mobile
Sticky bottom bar: **Home · Search · Sell (raised) · Compare · Account**.
Hamburger for the full tree; language toggle pinned at the top.

### Footer
4 columns — Company (About, Contact, Careers, Become a dealer) · Explore (New/Used/Implements/Dealers/Compare/Offers) ·
Popular (top 10 brands, top 10 models, top 10 cities) · Support (FAQ, Privacy, Terms, Disclaimer, Sitemap) +
app-store badges, social icons, newsletter, `© Krishi Junction`.

---

## 3. Home page composition (top → bottom)

1. Search bar + quick intent chips (Buy new / Buy used / Sell / Loan)
2. Hero slider (admin-managed banners)
3. Browse by brand (logo grid, 12 + "view all")
4. Browse by budget & HP (chip grid)
5. Popular new tractors (carousel, 8 cards)
6. Used tractors near you (geo-detected, 8 cards) + "Sell yours" CTA
7. EMI calculator strip (inline mini-calculator)
8. Implements & harvesters (carousel)
9. Latest offers
10. Compare widget ("Compare two tractors")
11. Find dealers near you (state/district picker)
12. Latest videos
13. Agriculture news & blog (6 cards)
14. User reviews / testimonials
15. Stats strip (models, dealers, listings, users)
16. App download banner
17. SEO content block (editable long-form text) + FAQ accordion

---

## 4. Page template inventory (Blade views to build)

| # | Template | Notes |
|---|---|---|
| T01 | Home | 17 blocks above |
| T02 | Product listing (tractors/implements/harvesters/tyres) | Shared, filter sidebar + grid |
| T03 | Product detail | Tabbed sections, sticky CTA bar on mobile |
| T04 | Brand landing | Brand story + models + dealers + news |
| T05 | Category landing | |
| T06 | Price list | Table-first, state selector |
| T07 | Compare | Sticky columns |
| T08 | Used listing grid | Distance + verified badges |
| T09 | Used listing detail | Gallery + inspection report |
| T10 | Sell (multi-step wizard) | Autosave |
| T11 | Dealer locator | State/district drill-down |
| T12 | Dealer detail | LocalBusiness schema |
| T13 | EMI calculator | Chart + amortisation |
| T14 | Loan hub / apply wizard | |
| T15 | Insurance | |
| T16 | Offers list + detail | |
| T17 | Blog/news list + detail | |
| T18 | Video hub + detail | |
| T19 | Reviews feed | |
| T20 | Static/CMS page | |
| T21 | Auth screens | OTP-first |
| T22 | Customer panel (9 screens) | |
| T23 | Dealer panel (11 screens) | |
| T24 | Search results | |
| T25 | 404 / 500 / maintenance | With useful links |

---

## 5. SEO plan

| Page type | Title pattern | Priority | Change freq |
|---|---|---|---|
| Home | `New & Used Tractors in India — Price, Specs \| Krishi Junction` | 1.0 | daily |
| Model detail | `{Brand} {Model} Price 2026, Specifications, Mileage \| Krishi Junction` | 0.9 | weekly |
| Model price | `{Brand} {Model} On-Road Price in {City} 2026` | 0.8 | weekly |
| Brand page | `{Brand} Tractors — All Models, Price List 2026` | 0.8 | weekly |
| HP/budget page | `{Range} Tractors in India — Price & Models` | 0.7 | weekly |
| Compare | `{A} vs {B} — Compare Price, Specs & Mileage` | 0.7 | monthly |
| Used listing | `Used {Brand} {Model} {Year} in {City} — ₹{Price}` | 0.6 | daily |
| Used geo page | `Second Hand Tractors in {District}, {State}` | 0.7 | daily |
| Dealer detail | `{Dealer} — {Brand} Tractor Dealer in {City}` | 0.6 | monthly |
| Blog/news | `{Title} \| Krishi Junction` | 0.6 | monthly |

**Structured data:** Organization + WebSite/SearchAction (home), Product + Offer +
AggregateRating (models), Review, BreadcrumbList (all), FAQPage (model + FAQ page),
LocalBusiness (dealers), Article (blog/news), VideoObject (videos), ItemList (listings).

**Sitemaps:** index at `/sitemap.xml` → `sitemap-static.xml`, `sitemap-tractors.xml`,
`sitemap-implements.xml`, `sitemap-used-{n}.xml` (10 k URLs each), `sitemap-dealers.xml`,
`sitemap-blogs.xml`, `sitemap-compare.xml`. Regenerated nightly + on publish.

**Canonical & pagination:** filtered facet pages self-canonical only for whitelisted
facets (brand, HP band, price band, geo); everything else canonicalises to the base
listing and carries `noindex,follow`. `rel=next/prev` retired — paginated pages
self-canonicalise with distinct titles.

**hreflang:** `en-IN` ↔ `hi-IN` pairs plus `x-default` on every mirrored URL.

---

## 6. Design system (used by the prototype)

| Token | Value |
|---|---|
| Primary (agri green) | `#1F7A3D` — dark `#155C2C`, light `#E8F5EC` |
| Accent (harvest amber) | `#F2A900` |
| Secondary (soil brown) | `#8B5E34` |
| Text | `#1A1D1A` / muted `#5F6B62` |
| Surface | `#FFFFFF` / page `#F6F8F6` / border `#E2E8E4` |
| Success / Warning / Danger / Info | `#1F7A3D` / `#F2A900` / `#D64545` / `#2563EB` |
| Radius | 10 px cards, 8 px inputs, 999 px chips |
| Font | Inter / Noto Sans Devanagari (Hindi) |
| Grid | 12-col, 1200 px max, 16 px gutters; breakpoints 360 / 640 / 1024 / 1280 |
| Card | white, 1 px border, shadow on hover, 4:3 image |

Components: button (4 variants), input, select, chip/filter pill, range slider, card
(product/used/dealer/blog), badge, breadcrumb, tabs, accordion, modal, drawer (mobile
filters), toast, pagination, empty state, skeleton loader, star rating, comparison bar,
sticky CTA bar, OTP input, stepper, data table, stat tile.
