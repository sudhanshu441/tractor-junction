# Krishi Junction — Product Requirements Document (PRD)

| Field | Value |
|---|---|
| Product | Krishi Junction |
| Version | 1.0 (Discovery / pre-development) |
| Owner | sudhanshu441 |
| Benchmark | tractorjunction.com |
| Stack | Laravel 11, PHP 8.3, MySQL 8, Redis, Tailwind, Alpine/Livewire |
| Status | **For approval** |

---

## 1. Executive summary

Krishi Junction is a rural-machinery marketplace and information portal for India. It
solves three problems for a farmer buying agricultural machinery:

1. **Price opacity** — no reliable public price/spec reference for tractors & implements.
2. **Trust in used machinery** — used tractors are sold informally with no inspection,
   no history and no price benchmark.
3. **Access to finance** — loan and insurance options are hard to compare and apply for.

The platform therefore has four commercial pillars:

| Pillar | What it is | How it earns |
|---|---|---|
| **Discovery** | New tractor/implement catalogue, specs, prices, compare, reviews | Ads, brand promotions, SEO traffic |
| **Used marketplace** | C2C + B2C listings of used tractors/implements with inspection | Listing fees, featured plans, commission |
| **Dealer network** | Verified dealer directory, dealer inventory, lead routing | Dealer subscriptions, per-lead pricing |
| **Finance (FinJ-style)** | Loan lead-gen, EMI tools, insurance enquiries | Commission from lenders/insurers |

---

## 2. Goals & non-goals

### 2.1 Goals (v1)
- G1 — Publish a complete, SEO-first catalogue of tractors, implements, harvesters, tyres and farm tools.
- G2 — Let any user list a used tractor/implement in under 3 minutes from a phone.
- G3 — Capture, qualify and route buyer leads to the right dealer within 60 seconds.
- G4 — Offer EMI calculation, loan application and insurance enquiry inside the buying journey.
- G5 — Ship a full-featured admin panel where non-technical staff run the entire business.
- G6 — Hindi + English from day one; architecture ready for 6 more Indian languages.
- G7 — Mobile-web first (most rural traffic is Android + 4G).

### 2.2 Non-goals (explicitly out of v1)
- Online payment/checkout for machinery (high-ticket offline sale; only booking amounts later).
- In-house loan underwriting (we are a lead originator, not an NBFC).
- Native Android/iOS apps (v1 ships the REST API that they will consume; apps are v2).
- Real-time chat between buyer and seller (v1 uses masked-call + callback requests).
- Logistics/transport booking.

---

## 3. Personas

| # | Persona | Profile | Primary need | Key screens |
|---|---|---|---|---|
| P1 | **Farmer-buyer (Ramesh, 38, Sitapur)** | Low digital literacy, Hindi, Android phone, buys once in 8 yrs | "Which 45 HP tractor fits my budget and land?" | Home, category, model detail, compare, EMI |
| P2 | **Used-tractor buyer (Vijay, 29)** | Price-sensitive, wants a 5-yr-old tractor near him | "Show verified used tractors under ₹4L within 50 km" | Used listing, filters, detail, contact seller |
| P3 | **Seller (individual)** | Wants to sell his old tractor at a fair price | "List fast, get genuine buyers, know the fair price" | Sell form, my listings, leads inbox |
| P4 | **Dealer (Krishi Motors, Jaipur)** | Authorised dealer for 1–3 brands, 2–20 staff | "Send me buyers in my district; show my stock" | Dealer panel, inventory, leads, plan |
| P5 | **Financier / insurer** | NBFC/bank partner | "Send me qualified loan applications" | Loan partner console (admin-mediated in v1) |
| P6 | **Content editor** | Marketing team | "Publish news, blogs, videos, offers, SEO meta" | Admin CMS |
| P7 | **Ops / moderator** | Internal | "Approve listings, verify dealers, assign leads" | Admin moderation queues |
| P8 | **Super admin** | Founder/CTO | "Configure everything, see the numbers" | Admin settings + reports |

---

## 4. System actors & panels

```
                        ┌───────────────────────────────┐
                        │        KRISHI JUNCTION        │
                        └───────────────────────────────┘
   ┌──────────────┬──────────────────┬───────────────────┬──────────────────┐
   │  WEBSITE     │  CUSTOMER PANEL  │   DEALER PANEL    │   ADMIN PANEL    │
   │  (public)    │  (auth: user)    │  (auth: dealer)   │  (auth: staff)   │
   ├──────────────┼──────────────────┼───────────────────┼──────────────────┤
   │ Catalogue    │ My listings      │ Inventory         │ Catalog mgmt     │
   │ Used market  │ My enquiries     │ Leads inbox       │ Used moderation  │
   │ Compare      │ Wishlist         │ Branches & staff  │ Dealer mgmt      │
   │ Dealers      │ Loan apps        │ Subscription/plan │ Leads & routing  │
   │ Loan/EMI     │ Saved searches   │ Reviews           │ Finance module   │
   │ News/Videos  │ Reviews          │ Reports           │ CMS + SEO        │
   │ Sell form    │ Profile/KYC      │ Enquiry to admin  │ Users & roles    │
   │              │ Notifications    │                   │ Reports/settings │
   └──────────────┴──────────────────┴───────────────────┴──────────────────┘
                              │
                        REST API (Sanctum) → future Android/iOS apps
```

---

## 5. Module catalogue

Numbered modules — every one of them is expanded into functional requirements in §6.

| ID | Module | Panel(s) |
|---|---|---|
| M01 | Authentication & user management (OTP + password + social) | All |
| M02 | Roles, permissions & staff management | Admin |
| M03 | Geography master (state/district/city/pincode) | Admin |
| M04 | Brand & category master | Admin |
| M05 | New tractor catalogue (models, variants, specs, prices, media) | Admin + Web |
| M06 | Implements, harvesters, tyres, farm-tools catalogue | Admin + Web |
| M07 | Search, filters & comparison engine | Web |
| M08 | Used machinery marketplace (list, moderate, buy) | All |
| M09 | Inspection & valuation (fair-price engine) | Admin + Web |
| M10 | Dealer network & dealer panel | All |
| M11 | Leads & enquiry engine (capture → route → track) | All |
| M12 | Finance: EMI calculator, loan applications, lenders | All |
| M13 | Insurance enquiries & partners | All |
| M14 | Reviews & ratings | Web + Admin |
| M15 | CMS: pages, blog, news, videos, FAQ, banners, testimonials | Admin + Web |
| M16 | Offers & promotions | Admin + Web |
| M17 | SEO engine (meta, schema, sitemap, redirects) | Admin + Web |
| M18 | Multilingual / localisation | All |
| M19 | Notifications (email, SMS, WhatsApp, web-push, in-app) | All |
| M20 | Reports & analytics dashboard | Admin + Dealer |
| M21 | Settings, configuration & audit log | Admin |
| M22 | Public REST API for mobile apps | API |

---

## 6. Functional requirements

Notation: **[MUST]** v1 · **[SHOULD]** v1 if time · **[LATER]** v2.

### M01 — Authentication & user management
- FR-01.1 **[MUST]** Mobile-number + OTP login/registration (primary method for rural users). OTP: 6 digits, 10-min TTL, max 5/hour/number, rate-limited by IP too.
- FR-01.2 **[MUST]** Email + password login as a secondary path, with forgot/reset password.
- FR-01.3 **[MUST]** Single `users` table with a `user_type` (customer, dealer, staff) plus Spatie roles; separate guards per panel.
- FR-01.4 **[MUST]** Profile: name, mobile (verified), email, state/district/city, language preference, avatar, land-holding (acres), farming type.
- FR-01.5 **[SHOULD]** Google sign-in.
- FR-01.6 **[MUST]** Soft-delete + account block by admin, with a reason and audit trail.
- FR-01.7 **[MUST]** Session/device list; logout from all devices.
- FR-01.8 **[MUST]** Guest actions (enquiry, EMI calc) allowed; identity captured via mobile+OTP at the point of submitting a lead.

### M02 — Roles, permissions & staff
- FR-02.1 **[MUST]** Roles: `super-admin`, `admin`, `content-editor`, `catalog-manager`, `moderator`, `sales-executive`, `finance-executive`, `dealer-owner`, `dealer-staff`, `customer`.
- FR-02.2 **[MUST]** Granular permissions per module × action (view/create/edit/delete/approve/export). Full matrix in [05-ADMIN-PANEL.md](05-ADMIN-PANEL.md#6-roles--permission-matrix).
- FR-02.3 **[MUST]** Admin can create staff users, assign roles, and scope a sales executive to states/districts.
- FR-02.4 **[MUST]** Every create/update/delete on business data is written to an activity log (who, what, before→after, IP).

### M03 — Geography master
- FR-03.1 **[MUST]** Seeded India dataset: 28 states + 8 UTs → districts → cities/tehsils → pincodes.
- FR-03.2 **[MUST]** Prices, dealers, listings and leads are all geo-tagged; a user's district drives default filtering.
- FR-03.3 **[MUST]** "Near me" search by pincode/lat-lng radius (10/25/50/100 km).
- FR-03.4 **[SHOULD]** Geography is admin-editable (add city, merge duplicates).

### M04 — Brand & category master
- FR-04.1 **[MUST]** Brands (Mahindra, Swaraj, Sonalika, John Deere, Massey Ferguson, Eicher, New Holland, Powertrac, Farmtrac, Kubota, VST, Force, Preet, Indo Farm, ACE, Captain, Solis, …) with logo, description, country, founded year, SEO fields, status.
- FR-04.2 **[MUST]** Categories are a tree: Tractor → (Mini, 4WD, AC-cabin, Orchard, Rotavator-ready…); Implement → (Rotavator, Cultivator, Plough, Seed drill, Thresher, Sprayer, Trailer, Baler, Laser leveller…); plus Harvester, Tyre, Farm-tool.
- FR-04.3 **[MUST]** Brand × category pivot so a brand page shows only what it makes.

### M05 — New tractor catalogue
- FR-05.1 **[MUST]** Model entity: brand, name, slug, category, HP, price range (ex-showroom min/max), launch year, status (available / upcoming / discontinued), short + long description, key highlights.
- FR-05.2 **[MUST]** Variants per model (e.g. 2WD/4WD, with/without power steering) with their own price & spec deltas.
- FR-05.3 **[MUST]** Specification engine: spec groups (Engine, Transmission, Brakes, Steering, PTO, Hydraulics, Fuel, Wheels & Tyres, Dimensions & Weight, Warranty, Other features) → attributes (typed: number/text/boolean/select, with unit) → values per model/variant. Admin can add a new attribute without a code change.
- FR-05.4 **[MUST]** State-wise / district-wise on-road price: ex-showroom + RTO + insurance + others = on-road, with an effective-from date and price history retained.
- FR-05.5 **[MUST]** Media: multiple images (with alt text, ordering, primary flag), 360° gallery **[SHOULD]**, YouTube videos, downloadable brochure PDF.
- FR-05.6 **[MUST]** Model detail page sections: hero + price + CTA, key specs, full specs, on-road price by city, EMI widget, similar/competitor models, dealer list, user reviews, FAQs, brochure, videos.
- FR-05.7 **[MUST]** Listing pages with filters: brand, HP range, price range, wheel-drive, category, fuel, cylinders, lift capacity, transmission, availability.
- FR-05.8 **[MUST]** Sorting: popularity, price ↑/↓, newest, HP.
- FR-05.9 **[MUST]** Curated collections: Popular, Latest, Upcoming, Mini (< 25 HP), 4WD, AC cabin, Under ₹5 lakh, 40–50 HP.
- FR-05.10 **[MUST]** "Tractor price list" pages by brand, by HP band, by state.

### M06 — Implements, harvesters, tyres, farm tools
- FR-06.1 **[MUST]** Same model/spec/price/media engine as M05, driven by category (a single polymorphic product core — see ERD).
- FR-06.2 **[MUST]** Implement-specific attributes (working width, number of blades, required HP, hitch type) come from the spec engine, not hard-coded columns.
- FR-06.3 **[MUST]** "Implements compatible with tractor X" — matching by required-HP band and hitch type.
- FR-06.4 **[MUST]** Tyre catalogue with size (e.g. 13.6 × 28), ply, position (front/rear), brand.

### M07 — Search, filters & comparison
- FR-07.1 **[MUST]** Global search across models, implements, used listings, dealers, articles — with type-ahead suggestions.
- FR-07.2 **[MUST]** Typo tolerance & Hindi/transliterated queries ("mahindra 575", "महिंद्रा 575", "mhindra 575").
- FR-07.3 **[MUST]** Faceted filters that update counts, are URL-encoded (shareable/indexable) and work without JS as a fallback.
- FR-07.4 **[MUST]** Compare up to 3 (mobile) / 4 (desktop) models side by side; highlight differences; sticky header; add-from-anywhere; shareable compare URL (`/compare/mahindra-575-di-vs-swaraj-744-fe`).
- FR-07.5 **[MUST]** Search-term logging to power "trending searches" and content strategy.
- FR-07.6 **[SHOULD]** Voice search on mobile web.

### M08 — Used machinery marketplace
- FR-08.1 **[MUST]** Sell flow (≤ 3 min, ≤ 6 steps): category → brand/model/year → condition & usage (hours, tyre condition, insurance/RC validity) → photos (min 4, max 12) → price expectation & location → mobile OTP verify → submit.
- FR-08.2 **[MUST]** Listing statuses: `draft → pending → approved → live → (sold | expired | rejected | blocked)`, each transition logged with actor & reason.
- FR-08.3 **[MUST]** Every listing is manually approved by a moderator before going live; rejection carries a reason shown to the seller.
- FR-08.4 **[MUST]** Auto-expire after 60 days with renew/repost; email+SMS reminder at day 53.
- FR-08.5 **[MUST]** Buyer sees: photos, specs pulled from the catalogue model, year, hours, condition score, seller type (owner/dealer), city, asking price, "verified" badge if inspected.
- FR-08.6 **[MUST]** Seller mobile is masked; buyer submits an interest → seller gets an SMS/notification → both see the lead in their panel.
- FR-08.7 **[MUST]** Filters: category, brand, model, year range, price range, HP, hours, state/district, verified-only, seller type, distance.
- FR-08.8 **[MUST]** Seller panel: my listings, views/leads per listing, edit, mark sold, boost/feature.
- FR-08.9 **[SHOULD]** Featured/premium listing plans (paid) with placement priority.
- FR-08.10 **[MUST]** Duplicate & fraud detection: same mobile + same model within 24 h flagged; image-hash duplicate check **[SHOULD]**.

### M09 — Inspection & valuation
- FR-09.1 **[MUST]** Admin-scheduled physical inspection for opted-in listings; inspector user role; checklist form (engine, transmission, hydraulics, tyres, body, documents) scored 0–10 each → overall condition grade A/B/C/D.
- FR-09.2 **[MUST]** Inspection report attached to the listing, PDF-exportable, drives the "Verified" badge.
- FR-09.3 **[SHOULD]** Fair-price estimate: rule-based from `base_model_price × age_depreciation × hours_factor × condition_factor × region_factor`, all factors admin-editable. Shown as a range, never a guarantee.
- FR-09.4 **[LATER]** ML-based pricing once ≥ 5 000 transactions exist.

### M10 — Dealer network & dealer panel
- FR-10.1 **[MUST]** Dealer entity: legal name, display name, owner, brands dealt, type (authorised / multi-brand / used-only), GSTIN, address, geo, contacts, working hours, logo, photos, verification status, rating.
- FR-10.2 **[MUST]** Multiple branches per dealer, each geo-located.
- FR-10.3 **[MUST]** Dealer onboarding: public "Become a dealer" form → admin verification (documents) → credentials issued → dealer panel access.
- FR-10.4 **[MUST]** Dealer panel: dashboard (leads, views, plan), inventory (new stock + used listings), leads inbox with status & notes, branches & staff, profile & documents, reviews, subscription/plan, reports.
- FR-10.5 **[MUST]** Public dealer locator: by state → district → city, by brand, "near me" radius, map view **[SHOULD]**, dealer detail page with inventory + reviews + enquiry form.
- FR-10.6 **[MUST]** Lead routing to dealers by (district, brand, category) with round-robin among eligible dealers and a daily cap per plan.
- FR-10.7 **[SHOULD]** Dealer subscription plans (Free / Silver / Gold) controlling lead volume, featured placement and inventory limits.

### M11 — Leads & enquiry engine
- FR-11.1 **[MUST]** One polymorphic `leads` table capturing every intent: new-model enquiry, used-listing interest, dealer enquiry, loan, insurance, callback, offer, general contact.
- FR-11.2 **[MUST]** Capture form is short (name, mobile, city) + OTP verification; context (model/listing/dealer) is attached automatically.
- FR-11.3 **[MUST]** Lead lifecycle: `new → assigned → contacted → qualified → (converted | lost | duplicate | invalid)` with reason codes.
- FR-11.4 **[MUST]** Auto-assignment rules (geo + category + brand + workload), manual re-assignment, bulk assignment.
- FR-11.5 **[MUST]** Activity timeline per lead: calls, notes, status changes, next-follow-up date with reminder.
- FR-11.6 **[MUST]** Admin lead board with filters, search, export to CSV/Excel, and a duplicate-merge action.
- FR-11.7 **[MUST]** Anti-spam: per-mobile and per-IP rate limits, honeypot, reCAPTCHA v3 on public forms.
- FR-11.8 **[SHOULD]** Masked calling / click-to-call integration (Exotel/Knowlarity) with call recording reference stored on the lead.

### M12 — Finance: EMI, loans, lenders
- FR-12.1 **[MUST]** EMI calculator: price, down payment, interest rate, tenure (12–84 months), repayment interval (monthly/quarterly/half-yearly/yearly) → EMI, total interest, total payable, amortisation table, shareable URL, per-model calculator pages.
- FR-12.2 **[MUST]** Loan application: applicant details, KYC (Aadhaar/PAN — stored as document references, masked in UI), land records, income, machinery being financed, requested amount & tenure.
- FR-12.3 **[MUST]** Document upload with type, size and MIME validation; documents stored privately (never public-web-readable) with signed, expiring URLs.
- FR-12.4 **[MUST]** Application lifecycle: `submitted → under-review → docs-pending → sent-to-lender → sanctioned | rejected | disbursed`, each with timestamps and remarks.
- FR-12.5 **[MUST]** Lender master (name, logo, interest range, max tenure, min/max amount, processing fee, states served) and admin-side routing of an application to one or more lenders.
- FR-12.6 **[MUST]** Applicant sees status in the customer panel; SMS/email on each status change.
- FR-12.7 **[SHOULD]** Eligibility pre-check (rule-based) before the full form.
- FR-12.8 **[LATER]** Direct lender API integration.

### M13 — Insurance
- FR-13.1 **[MUST]** Insurance partner master and an enquiry form (vehicle, RC details, previous policy, coverage type).
- FR-13.2 **[MUST]** Enquiries flow into the same lead engine with `type = insurance`, routed to the finance team.
- FR-13.3 **[SHOULD]** Static comparison table of partner plans and content pages on tractor insurance.

### M14 — Reviews & ratings
- FR-14.1 **[MUST]** Verified-user reviews on models, dealers and used sellers: 1–5 stars, title, body, optional photos, ownership duration, pros/cons.
- FR-14.2 **[MUST]** Sub-ratings for models: mileage, comfort, maintenance, performance, value-for-money → aggregate score cached on the model.
- FR-14.3 **[MUST]** Moderation queue; profanity filter; approve/reject with reason; admin reply.
- FR-14.4 **[MUST]** Helpful/not-helpful voting, one vote per user per review.
- FR-14.5 **[MUST]** Review schema.org markup for rich snippets.

### M15 — CMS
- FR-15.1 **[MUST]** Static pages (About, Contact, Privacy, Terms, Disclaimer, Sitemap page) with a WYSIWYG editor and per-page SEO.
- FR-15.2 **[MUST]** Blog & news with categories, tags, author, cover image, scheduled publishing, related posts, view count, comments **[SHOULD]**.
- FR-15.3 **[MUST]** Video hub: YouTube-embedded videos with category, model tagging, thumbnail, views.
- FR-15.4 **[MUST]** FAQ with categories, shown globally and per model.
- FR-15.5 **[MUST]** Home slider/banners with position, schedule (start/end), target URL, device targeting (mobile/desktop), click tracking.
- FR-15.6 **[MUST]** Testimonials, "as seen in" logos, statistics strip — all admin-editable.
- FR-15.7 **[MUST]** Menu builder for header/footer links.
- FR-15.8 **[SHOULD]** Agriculture extras: mandi prices, weather widget, government-scheme articles.

### M16 — Offers & promotions
- FR-16.1 **[MUST]** Offer entity: title, description, brand/model scope, discount type & value, validity window, states, banner image, T&C, CTA.
- FR-16.2 **[MUST]** Offers surface on home, model pages, a dedicated `/offers` page, and generate leads.

### M17 — SEO engine
- FR-17.1 **[MUST]** Per-entity SEO meta (title, description, keywords, canonical, OG/Twitter, robots) editable in admin, with sensible templated defaults.
- FR-17.2 **[MUST]** Clean, permanent URL patterns (see [04-SITEMAP-IA.md](04-SITEMAP-IA.md)); slugs are immutable once published, changes create a 301.
- FR-17.3 **[MUST]** Auto-generated, chunked XML sitemaps (models, implements, used listings, dealers, blogs) + `robots.txt` + sitemap index.
- FR-17.4 **[MUST]** JSON-LD: Product, Offer, AggregateRating, Review, BreadcrumbList, FAQPage, LocalBusiness (dealers), Article, Organization, WebSite+SearchAction.
- FR-17.5 **[MUST]** Admin-managed 301/302 redirect table + 404 log to catch broken links.
- FR-17.6 **[MUST]** Core Web Vitals budget: LCP < 2.5 s on 4G, CLS < 0.1, INP < 200 ms.
- FR-17.7 **[MUST]** `hreflang` for hi/en variants.

### M18 — Multilingual
- FR-18.1 **[MUST]** English + Hindi UI via Laravel localisation; language switcher persisted per user/cookie.
- FR-18.2 **[MUST]** Translatable content fields (model description, blog, page, FAQ, offer) stored per locale.
- FR-18.3 **[MUST]** URL strategy: `/` = English, `/hi/…` = Hindi, with `hreflang` pairs.
- FR-18.4 **[SHOULD]** Admin UI to manage translation strings without touching files.
- FR-18.5 **[LATER]** Marathi, Punjabi, Gujarati, Telugu, Tamil, Kannada.

### M19 — Notifications
- FR-19.1 **[MUST]** Channels: transactional SMS (OTP, lead, status), email, in-app/database, web-push **[SHOULD]**, WhatsApp Business **[SHOULD]**.
- FR-19.2 **[MUST]** Admin-editable templates per event with variable placeholders and per-channel toggles.
- FR-19.3 **[MUST]** All notifications dispatched through queues; delivery attempts and failures logged.
- FR-19.4 **[MUST]** User notification preferences + unsubscribe for marketing (transactional exempt).

### M20 — Reports & analytics
- FR-20.1 **[MUST]** Admin dashboard: today/7d/30d counts of visitors, leads by type, listings by status, loan applications by stage, top models, top districts, conversion funnel.
- FR-20.2 **[MUST]** Reports with date-range + filters, exportable to CSV/Excel: leads, listings, dealers, loan applications, users, reviews, search terms, page views.
- FR-20.3 **[MUST]** Dealer-facing report: leads received/contacted/converted, inventory views, plan usage.
- FR-20.4 **[MUST]** GA4 + Meta Pixel + GTM hooks with a consent banner.

### M21 — Settings & audit
- FR-21.1 **[MUST]** Settings groups: general (site name, logo, favicon, contact), SEO defaults, email/SMS/WhatsApp gateway creds, payment gateway (for plans), social links, business rules (listing expiry days, lead caps, OTP TTL), maintenance mode.
- FR-21.2 **[MUST]** Full activity/audit log, filterable by user, module, action and date; immutable.
- FR-21.3 **[MUST]** Backup policy & restore runbook documented; DB backup nightly, media weekly, 30-day retention.

### M22 — Public REST API
- FR-22.1 **[MUST]** Versioned `/api/v1/*`, JSON:API-ish envelope, Sanctum tokens, per-token rate limits.
- FR-22.2 **[MUST]** Endpoints covering catalogue, search, used listings (incl. create), dealers, leads, EMI, loan applications, content, auth/OTP, profile.
- FR-22.3 **[MUST]** OpenAPI 3 spec generated and published at `/docs/api`.

---

## 7. Non-functional requirements

| Area | Requirement |
|---|---|
| **Performance** | TTFB < 400 ms cached / < 800 ms uncached; listing pages < 2.5 s LCP on 4G; ≤ 60 SQL queries per page (target ≤ 25). |
| **Scale (yr-1 target)** | 2 M monthly page views, 300 k monthly users, 50 k used listings, 20 k dealers, 200 k leads/yr. |
| **Availability** | 99.5 % monthly; zero-downtime deploys; queue workers supervised. |
| **Mobile** | Mobile-first responsive; usable on a 360 px screen and a 3G connection; images lazy-loaded, WebP/AVIF with srcset. |
| **Security** | OWASP Top 10; CSRF on all forms; parameterised queries only; strict file-upload validation; private S3 for KYC; encrypted at rest for PII; 2FA for admin **[SHOULD]**; login throttling; security headers (CSP, HSTS, X-Frame-Options). |
| **Privacy / compliance** | DPDP Act 2023 alignment — consent capture, purpose limitation, data-deletion request flow, privacy policy, retention schedule; phone numbers masked in public UI. |
| **Accessibility** | WCAG 2.1 AA target: contrast, focus states, alt text, form labels, keyboard navigation. |
| **SEO** | Server-rendered HTML for every indexable page (no JS-only content). |
| **Browsers** | Chrome/Android WebView (last 2), Safari iOS 15+, Firefox, Edge. |
| **Backup/DR** | Nightly DB dump to offsite storage, RPO 24 h, RTO 4 h. |
| **Observability** | Centralised logs, error tracking (Sentry), uptime monitor, slow-query log, queue-failure alerts. |
| **Code quality** | PSR-12, Pint, PHPStan level 5+, feature tests for every critical flow, PR review required. |

---

## 8. Success metrics (KPIs)

| KPI | 6-month target |
|---|---|
| Organic sessions / month | 250 000 |
| Indexed pages | 40 000+ |
| Used listings created / month | 3 000 |
| Listing approval turnaround | < 6 working hours (P90) |
| Leads / month | 15 000 |
| Lead → dealer contact time | < 60 min (P90) |
| Loan applications / month | 800 |
| Verified dealers | 2 000 |
| Model-page → enquiry conversion | ≥ 3.5 % |
| Repeat visitor share | ≥ 25 % |

---

## 9. Assumptions

1. Catalogue content (models, specs, images) will be seeded by the content team; the platform ships with a seeder covering ~10 brands / ~60 models as demo data.
2. Third-party accounts (SMS gateway, WhatsApp BSP, S3, Maps, payment gateway) are provided by the client before the phase that needs them.
3. Machinery is transacted offline; the platform is lead-gen, not e-commerce checkout.
4. All product imagery used in the prototype/seed data is placeholder or licensed stock — no assets are copied from any competitor site. See [08-CONTENT-MEDIA-PLAN.md](08-CONTENT-MEDIA-PLAN.md).
5. Hosting is a single VPS/cloud VM in an India region for v1, with a CDN in front.

## 10. Risks

| Risk | Impact | Mitigation |
|---|---|---|
| Thin/duplicate content hurting SEO | High | Unique spec-driven copy per model, editorial review, canonical discipline |
| Fake/spam used listings | High | OTP-verified sellers, manual approval, duplicate detection, report-listing |
| Lead quality complaints from dealers | Medium | OTP-verified leads, qualification status, dealer feedback loop |
| KYC/PII breach | Critical | Private storage, encryption, least-privilege roles, audit log, no PII in logs |
| Scope creep from "clone everything" | High | Phased roadmap in [07-DEV-ROADMAP.md](07-DEV-ROADMAP.md); each phase separately signed off |
| Rural bandwidth | Medium | Aggressive image optimisation, minimal JS, server-side rendering |

---

## 11. Open questions for you

| # | Question | Why it matters | Default if unanswered |
|---|---|---|---|
| Q1 | Admin panel: Filament v3 (fast, batteries-included) or fully custom Blade+Livewire (fully bespoke UI)? | Changes phase-1 effort significantly | **Filament v3** |
| Q2 | Which SMS/WhatsApp provider (MSG91, Twilio, Gupshup)? | OTP + notifications | MSG91 (India-focused) |
| Q3 | Do dealers pay from day one (subscription plans + payment gateway)? | Adds Razorpay + billing module | Free in v1, plans in phase 5 |
| Q4 | Is the mobile app in scope after web, and on which stack? | Affects API shape | API built anyway; app out of scope |
| Q5 | Do you already have catalogue data (models/specs/prices) in a sheet? | Saves weeks of data entry | We build seeders for ~60 demo models |
| Q6 | Hosting preference — AWS / DigitalOcean / Hostinger VPS / shared? | Deployment scripting | Ubuntu VPS + Nginx + Cloudflare |
| Q7 | Logo, brand colours and fonts for Krishi Junction? | Prototype currently uses a green/amber agri palette | Green #1F7A3D + Amber #F2A900 |

---

## 12. Approval

Development starts only after sign-off on this PRD, the ERD, the flows and the prototype.
