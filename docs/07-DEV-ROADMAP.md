# Krishi Junction — 5-Phase Development Plan

Stack: **Laravel · PHP · MySQL · Bootstrap 5 · jQuery · AJAX · HTML · CSS**.
Estimates are working days for **one full-time developer**; two developers can run
phases 2 and 3 largely in parallel and roughly halve the calendar time.

Each phase ends with: migrations + seeders, working screens on staging, feature tests,
a short changelog, and screenshots. You review and sign off before the next phase starts.

---

## Phase 0 — Discovery & design ✅ *complete, awaiting your approval*

PRD, ERD (94 tables), 15 user flows, sitemap/IA, admin specification, technical
architecture, brand guide + logo set, and a 15-screen clickable prototype.
Nothing below starts until you approve this.

---

## Phase 1 — Foundation, admin shell & masters
**8–10 days**

Everything later phases stand on.

| # | Deliverable |
|---|---|
| 1.1 | Laravel 11 install, folder structure, environments, git workflow, Pint + PHPUnit, staging deploy |
| 1.2 | Bootstrap 5 theme: Sass variable overrides for the white/green palette, `custom.css`, Archivo + IBM Plex fonts, logo assets wired in, favicons |
| 1.3 | Base layouts — public, admin, dealer, customer — plus the shared Blade component library (card, badge, pagination, breadcrumb, stepper, empty state, toast) |
| 1.4 | All 94 migrations, Eloquent models, relationships, factories |
| 1.5 | Geography master + seeder: 36 states → ~780 districts → ~4 000 cities; dependent AJAX selects |
| 1.6 | Auth: mobile OTP (send/verify/throttle), email+password, forgot password, 4 guards, profile |
| 1.7 | RBAC: 10 roles, ~180 permissions, policies, middleware, staff user CRUD |
| 1.8 | Admin shell: sidebar, dashboard placeholders, DataTables server-side base class, reusable CRUD scaffolding, activity log |
| 1.9 | Settings module, media/upload service with WebP conversions, notification template scaffolding, SMS + email drivers |

**Done when:** `migrate:fresh --seed` builds the whole schema; a user logs in by OTP; a
super-admin creates a staff user, assigns a role, and permissions are enforced on every route.

---

## Phase 2 — Catalogue & public website
**12–14 days**

The SEO engine of the business — the part Google indexes.

| # | Deliverable |
|---|---|
| 2.1 | Admin CRUD: brands, categories (tree), spec groups, spec attributes, category↔attribute mapping |
| 2.2 | Admin product editor, 8 tabs: basics, variants, specifications (form auto-built from the category mapping), prices, media, features, FAQs, competitors + SEO |
| 2.3 | State-wise price manager with effective dates, price history, bulk CSV import, on-road breakdown |
| 2.4 | `product_filter_cache` denormalisation + facet-count engine with Redis caching |
| 2.5 | Home page — all 17 admin-managed blocks |
| 2.6 | Listing template (tractors / implements / harvesters / tyres) with AJAX filters, sort, load-more, `history.pushState` URLs that also render server-side |
| 2.7 | Model detail page: gallery, key specs, full specs, on-road price by city, EMI widget, dealers, reviews placeholder, FAQs, brochure, videos |
| 2.8 | Brand, category, HP-band, budget and state price-list pages |
| 2.9 | Search: FULLTEXT + synonyms, AJAX type-ahead, results page, search logging |
| 2.10 | Compare: add from anywhere, floating bar, 2–4 column table with differences highlighted, shareable URL, PDF export |
| 2.11 | Demo seeder: ~60 tractors + ~40 implements with specs, prices and placeholder imagery |

**Done when:** a visitor filters to 40–50 HP Mahindra tractors, opens a model, sees the
on-road price for their city, and compares three models — every page server-rendered and indexable.

---

## Phase 3 — Used marketplace, leads & customer panel
**12–14 days**

Where supply and demand actually meet.

| # | Deliverable |
|---|---|
| 3.1 | Sell wizard: 6 AJAX steps, server-side draft autosave, guided multi-photo upload with preview/reorder, OTP verification, duplicate detection |
| 3.2 | Used listing lifecycle — draft → pending → live → sold/expired/rejected/blocked — with status logs, 60-day expiry job, renew and repost |
| 3.3 | Public used listing grid: geo + distance filters, brand/year/hours/price facets, verified badge |
| 3.4 | Used listing detail: gallery, condition data, catalogue-derived specs, fair-price band, masked seller contact |
| 3.5 | Admin moderation queue: side-by-side photo review, price-vs-valuation flag, checklist, approve/reject/request-changes with reasons, keyboard shortcuts, bulk actions, SLA counter |
| 3.6 | Report-a-listing flow and admin handling |
| 3.7 | Lead engine: capture forms across the site, OTP verification, dedupe, `leads` + `lead_assignments` + `lead_activities` |
| 3.8 | Routing rules engine with geo/brand/category scope, caps, round-robin, escalation job |
| 3.9 | Admin leads board: filters, assignment and bulk assignment, activity timeline, duplicate merge, CSV export, PII masking by permission |
| 3.10 | Customer panel: dashboard, my listings, leads on my listings, my enquiries, wishlist, saved searches, notifications, profile |
| 3.11 | Notifications on every listing and lead event — SMS, email, in-app — with admin-editable templates |

**Done when:** a seller lists in under three minutes, a moderator approves it, a buyer
enquires, and the lead reaches the right assignee with an SMS inside a minute.

---

## Phase 4 — Dealers, finance & monetisation
**12–14 days**

The revenue layer.

| # | Deliverable |
|---|---|
| 4.1 | Dealer entity, branches, brands, documents; "Become a dealer" public flow; admin verification queue |
| 4.2 | Dealer panel: dashboard, inventory, dealer-posted used listings, leads inbox with SLA countdown, lead detail, branches, staff, reviews, profile, reports |
| 4.3 | Public dealer locator: state → district drill-down, brand filter, near-me radius, Google Maps view, dealer detail with LocalBusiness schema and enquiry form |
| 4.4 | Lead routing extended to dealers: eligibility, daily caps, round-robin, response-time score feeding routing priority |
| 4.5 | Reviews and ratings for models and dealers, with sub-ratings, moderation queue, helpful voting and replies |
| 4.6 | EMI calculator: four repayment frequencies, amortisation table, per-model calculator pages, shareable/WhatsApp URL |
| 4.7 | Loan application wizard, private document upload with signed URLs, lender master, application↔lender tracker, status machine, applicant status view |
| 4.8 | Insurance partners and enquiry flow into the lead engine |
| 4.9 | Inspection module: request, schedule, inspector role, checklist scoring, grade, valuation rules, PDF report, verified badge |
| 4.10 | Plans + Razorpay: dealer subscriptions, listing boosts, payments, invoices |
| 4.11 | WhatsApp notifications and click-to-call integration |

**Done when:** a dealer is verified, receives a routed lead within their cap and converts
it; an applicant submits a loan with documents and watches it reach "sanctioned".

---

## Phase 5 — Content, SEO, multilingual, API & launch
**10–12 days**

Traffic, polish and go-live.

| # | Deliverable |
|---|---|
| 5.1 | CMS: static pages, blog/news with categories and tags, videos, FAQs, banners/sliders, testimonials, offers, menu builder, comments |
| 5.2 | SEO engine: meta manager with templated defaults, JSON-LD builders (Product, Offer, Review, FAQ, Breadcrumb, LocalBusiness, Article, VideoObject), chunked XML sitemaps, redirect manager, 404 log |
| 5.3 | Multilingual: full Hindi pass, `/hi` routes, `hreflang`, admin-editable translation strings |
| 5.4 | Reports and analytics: admin + dealer dashboards with Chart.js, all exports, GA4/GTM, consent banner |
| 5.5 | REST API v1 with Sanctum for the future mobile app + published documentation |
| 5.6 | Performance: caching layers, image pipeline, query budget, Lighthouse pass (≥ 90 performance, ≥ 95 SEO) |
| 5.7 | Security review, backups + restore rehearsal, monitoring, error tracking, runbooks |
| 5.8 | UAT fixes, content loading support, launch checklist, handover documentation |

**Done when:** the site is bilingual, schema and sitemaps validate, dashboards report real
numbers, backups are rehearsed, and the launch checklist is green.

---

## Summary

| Phase | Focus | Days | Cumulative |
|---|---|---|---|
| 1 | Foundation, admin shell, masters | 8–10 | 8–10 |
| 2 | Catalogue & public website | 12–14 | 20–24 |
| 3 | Used marketplace, leads, customer panel | 12–14 | 32–38 |
| 4 | Dealers, finance, monetisation | 12–14 | 44–52 |
| 5 | Content, SEO, multilingual, API, launch | 10–12 | **54–64** |

≈ **11–13 calendar weeks** solo, or roughly 7–8 weeks with two developers.

> These are larger than the earlier six-phase numbers for one reason: hand-building the
> admin panel in Bootstrap + jQuery + DataTables costs more than generating it with an
> admin framework. That is the trade for a fully bespoke UI on your chosen stack, and it
> is worth naming rather than hiding in the estimate.

**Want a demo sooner?** Phases 1 + 2 plus the used-listing and lead core of phase 3 give a
working, demo-able marketplace in about 5–6 weeks; dealers, finance and CMS follow.
