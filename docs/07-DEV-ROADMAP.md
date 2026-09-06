# Krishi Junction — Development Roadmap

Estimates assume **one full-time Laravel developer** (me, working in this repo).
Ranges are working days of build time; they scale down with parallel developers.

---

## Phase 0 — Discovery & design ✅ *complete, awaiting your approval*

| Deliverable | Status |
|---|---|
| PRD | ✅ `docs/01-PRD.md` |
| ERD + data dictionary (94 tables) | ✅ `docs/02-ERD.md` |
| User flows & state machines (15 flows) | ✅ `docs/03-USER-FLOWS.md` |
| Sitemap, IA, URL & SEO plan | ✅ `docs/04-SITEMAP-IA.md` |
| Admin panel spec + permission matrix | ✅ `docs/05-ADMIN-PANEL.md` |
| Technical architecture | ✅ `docs/06-TECH-ARCHITECTURE.md` |
| Clickable HTML prototype (13 screens) | ✅ `prototype/` |
| Content & media plan | ✅ `docs/08-CONTENT-MEDIA-PLAN.md` |

**Gate:** your written approval + answers to the 7 open questions in the PRD (§11).

---

## Phase 1 — Foundation (6–8 days)

**Scope**
- Laravel 11 install, Docker/Sail dev env, Tailwind + Vite, Pint + Larastan + Pest.
- All 94 migrations, models, relationships, factories.
- Geography seeder (36 states → ~780 districts → ~4 000 cities).
- RBAC: roles, ~180 permissions, seeders, policies, 4 auth guards.
- OTP auth (send/verify/throttle) + password auth + profile.
- Settings, activity log, media library, notification template scaffolding.
- Admin shell: layout, navigation, dashboard placeholders, user/role CRUD.
- CI pipeline, staging deploy script.

**Acceptance:** `php artisan migrate:fresh --seed` builds the full schema; login by OTP
works; a super-admin can create staff and assign roles; permissions are enforced.

---

## Phase 2 — Catalogue & public website (10–12 days)

- Brands, categories, spec groups/attributes, category↔attribute mapping (admin CRUD).
- Products + variants + spec values + prices (state-wise) + media + features + FAQs +
  competitors — full 8-tab editor, price history, bulk price import.
- `product_filter_cache` denormalisation + facet engine.
- Public: home, listing pages with filters/sort/pagination, model detail (all sections),
  brand pages, category pages, HP/budget pages, price-list pages, search with suggestions.
- Compare (2–4 models, shareable URL).
- Design system components in Blade; responsive; Hindi/English switcher.
- Demo seeder: ~60 tractors + ~40 implements with specs, prices and images.

**Acceptance:** a visitor can find a tractor by brand/HP/budget, view full specs and
state-wise on-road price, and compare 3 models — all server-rendered and indexable.

---

## Phase 3 — Used marketplace & leads (10–12 days)

- Sell wizard (6 steps, autosave, OTP, guided photo capture, duplicate check).
- Used listing lifecycle, expiry job, renew/boost, reporting.
- Public used listing grid + filters (geo, distance) + detail page.
- Moderation queue with keyboard shortcuts, reasons, bulk actions, SLA counter.
- Lead engine: capture forms everywhere, OTP verification, dedupe, `leads` +
  `lead_assignments` + `lead_activities`, routing rules engine, escalation job.
- Admin lead board with filters, assignment, activity timeline, export.
- Customer panel: dashboard, my listings, listing leads, enquiries, wishlist, saved
  searches, notifications, profile.
- Notifications: SMS + email + in-app on all listing/lead events.

**Acceptance:** a seller lists in < 3 min; a moderator approves it; a buyer enquires;
the lead reaches the right assignee with SMS inside a minute; both sides see it.

---

## Phase 4 — Dealers (7–9 days)

- Dealer entity, branches, brands, documents, verification workflow.
- "Become a dealer" public flow + admin verification queue.
- Dealer panel: dashboard, inventory, used listings, leads inbox with SLA, lead detail,
  branches, staff, reviews, profile, reports.
- Public dealer locator (state/district/brand/near-me) + dealer detail with
  LocalBusiness schema + enquiry form.
- Lead routing extended to dealers (eligibility, caps, round-robin, performance score).
- Reviews & ratings for models and dealers, with moderation.

**Acceptance:** a dealer signs up, is verified, receives a routed lead within its cap,
and updates it to converted; the public locator finds them by city and brand.

---

## Phase 5 — Finance, inspection & monetisation (8–10 days)

- EMI calculator (all frequencies, amortisation, per-model pages, share URL).
- Loan application wizard, document upload (private disk, signed URLs), lender master,
  application↔lender tracker, status machine, applicant status view, notifications.
- Insurance partners + enquiry flow.
- Inspection module: request, schedule, inspector role, checklist form, scoring, grade,
  valuation rules, PDF report, verified badge.
- Plans + Razorpay: dealer subscriptions and listing boosts, payments table, invoices.
- WhatsApp + click-to-call integrations.

**Acceptance:** an applicant submits with documents, finance staff moves it to
sanctioned, the applicant sees each status change; an inspected listing shows a
verified badge and a downloadable report.

---

## Phase 6 — Content, SEO, API & hardening (8–10 days)

- CMS: pages, blog/news with categories & tags, videos, FAQs, banners, testimonials,
  offers, menu builder, comments.
- SEO engine: meta manager, templated defaults, JSON-LD builders, sitemap generator,
  redirects, 404 log, hreflang.
- Full Hindi translation pass + translation admin.
- Reports & analytics dashboards (admin + dealer), exports, GA4/GTM.
- REST API v1 + OpenAPI docs + Sanctum tokens.
- Performance: caching layers, image pipeline, Lighthouse pass, query budget.
- Security review, backups, monitoring, runbooks, UAT fixes, launch checklist.

**Acceptance:** Lighthouse ≥ 90 performance / ≥ 95 SEO on home, listing and detail;
sitemap and schema validate; API docs published; backup + restore rehearsed.

---

## Summary

| Phase | Days | Cumulative |
|---|---|---|
| 1 Foundation | 6–8 | 6–8 |
| 2 Catalogue & website | 10–12 | 16–20 |
| 3 Used marketplace & leads | 10–12 | 26–32 |
| 4 Dealers | 7–9 | 33–41 |
| 5 Finance & monetisation | 8–10 | 41–51 |
| 6 Content, SEO, API, hardening | 8–10 | **49–61** |

≈ **10–12 calendar weeks** for the full v1 with a single developer, or roughly half that
with two working in parallel from phase 2 (catalogue/website vs marketplace/leads split
cleanly).

### Ways to reach a demo sooner
- **MVP-first cut (~3.5 weeks):** phases 1 + 2 + the used-listing and lead core of phase 3.
  That is a working, demo-able marketplace; dealers, finance and CMS follow.
- Defer inspection, boosts, WhatsApp, payments and the API to a post-launch phase.

### Per-phase working agreement
1. I develop on `claude/krishi-junction-setup-pmlgom` and push at the end of each phase.
2. Each phase ends with: migrations + seeders, tests, a short changelog, and screenshots.
3. You review on staging and sign off before the next phase starts.
