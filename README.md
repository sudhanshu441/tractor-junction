# Krishi Junction

**India's digital marketplace for tractors, farm implements and agri-machinery.**

Krishi Junction is a Laravel-based platform where farmers can research, compare, buy,
sell, finance and insure new & used tractors, harvesters, implements, tyres and farm
tools — plus a full dealer network, content/news hub and a role-based admin panel.

> Reference/benchmark product: tractorjunction.com. This project is an independent
> implementation with its own branding, data model and codebase.

---

## Project status

| Phase | Deliverable | Days | Status |
|---|---|---|---|
| 0 | Discovery: PRD, ERD, flows, IA, admin spec, brand + logo, prototype | — | ✅ Approved |
| 1 | Foundation, admin shell, masters, auth, RBAC | 8–10 | ✅ **Complete** — [notes](docs/10-PHASE-1-NOTES.md) |
| 2 | Catalogue & public website (SEO engine) | 12–14 | ✅ **Complete** — [notes](docs/11-PHASE-2-NOTES.md) |
| 3 | Used marketplace, leads, customer panel | 12–14 | ✅ **Complete** — [notes](docs/12-PHASE-3-NOTES.md) |
| 4 | Dealers, finance, monetisation | 12–14 | ✅ **Complete** — [notes](docs/13-PHASE-4-NOTES.md) |
| 5 | Content, SEO, multilingual, API, launch | 10–12 | ✅ **Complete** — [notes](docs/17-PHASE-5-NOTES.md) |

Total ≈ **54–64 dev days** (11–13 weeks solo, 7–8 with two developers). Full breakdown in
[docs/07-DEV-ROADMAP.md](docs/07-DEV-ROADMAP.md).

**All five phases are built and tested.** 236 tests passing: OTP auth and RBAC, a
catalogue with live filters and comparison, a used marketplace with a sell wizard,
moderation queue and lead routing engine, a dealer network with plans, loans,
insurance, inspections and reviews, and a bilingual content and SEO layer with a
REST API for the mobile app. Setup instructions are in
[docs/10-PHASE-1-NOTES.md](docs/10-PHASE-1-NOTES.md);
**read [docs/15-LAUNCH-CHECKLIST.md](docs/15-LAUNCH-CHECKLIST.md) before going live.**

```bash
git clone https://github.com/sudhanshu441/tractor-junction.git
cd tractor-junction && git checkout claude/krishi-junction-setup-pmlgom

composer install
cp .env.example .env && php artisan key:generate
# create the database, then set DB_* in .env
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Open <http://localhost:8000>. Sign in to the admin panel at `/login/password` with
`admin@krishijunction.com` / `KrishiAdmin@2026`.

Full walkthrough, every seeded login, mail and SMS configuration, and a
troubleshooting table: **[docs/18-LOCAL-SETUP.md](docs/18-LOCAL-SETUP.md)**.

> Local sign-in sends no SMS. The OTP is written to `storage/logs/laravel.log` —
> `tail -f storage/logs/laravel.log | grep "OTP DEBUG"`.

---

## Documentation index

| # | Document | What's inside |
|---|---|---|
| 01 | [Product Requirements (PRD)](docs/01-PRD.md) | Vision, personas, scope, module-by-module functional requirements, NFRs, KPIs |
| 02 | [Entity Relationship Diagram (ERD)](docs/02-ERD.md) | Full database design — 90+ tables, mermaid ERDs, data dictionary |
| 03 | [User Flows](docs/03-USER-FLOWS.md) | Buyer / seller / dealer / finance / admin journeys as flowcharts |
| 04 | [Sitemap & Information Architecture](docs/04-SITEMAP-IA.md) | Every page, URL pattern, SEO plan, navigation tree |
| 05 | [Admin Panel Specification](docs/05-ADMIN-PANEL.md) | Menu tree, every screen, roles & permission matrix |
| 06 | [Technical Architecture](docs/06-TECH-ARCHITECTURE.md) | Laravel stack, folder layout, packages, API design, infra, security |
| 07 | [Development Roadmap](docs/07-DEV-ROADMAP.md) | Phase/sprint plan, estimates, acceptance criteria, deliverables |
| 08 | [Content & Media Plan](docs/08-CONTENT-MEDIA-PLAN.md) | Images, image sourcing, naming, sizes, seed data plan |
| 09 | [Brand Guide](docs/09-BRAND-GUIDE.md) | Logo usage, white/green palette, type scale, Bootstrap mapping |
| 10 | [Phase 1 Notes](docs/10-PHASE-1-NOTES.md) | What shipped, how to run it, deviations, known gaps |
| 11 | [Phase 2 Notes](docs/11-PHASE-2-NOTES.md) | Catalogue, listing, detail, compare, search — and the bugs caught |
| 12 | [Phase 3 Notes](docs/12-PHASE-3-NOTES.md) | Sell wizard, moderation, lead engine, customer panel |
| 13 | [Phase 4 Notes](docs/13-PHASE-4-NOTES.md) | Dealers, plans and payments, loans, insurance, inspections, reviews |
| 14 | [API Reference](docs/14-API-REFERENCE.md) | REST API v1 for the mobile app |
| 15 | [Launch Checklist](docs/15-LAUNCH-CHECKLIST.md) | What must be true before go-live, and the known gaps |
| 16 | [Runbooks](docs/16-RUNBOOKS.md) | Deploy, backup, restore, and what to do when things break |
| 17 | [Phase 5 Notes](docs/17-PHASE-5-NOTES.md) | CMS, SEO engine, Hindi, reports, API |
| 18 | [Local Setup](docs/18-LOCAL-SETUP.md) | **Start here** — clone to running in a browser, with every login |

## Prototype

A clickable HTML wireframe prototype lives in [`prototype/`](prototype/).

```bash
cd prototype && python3 -m http.server 8080   # then open http://localhost:8080
```

**Hosted version:** https://claude.ai/code/artifact/f61c8ee1-8f7d-40bc-a29d-07af56ecb979

15 screens: Home · Tractor listing · Model detail · Compare · Used marketplace ·
Used listing detail · Sell wizard (mobile) · Dealer locator · EMI calculator ·
Loan application · Customer dashboard · Dealer panel · Admin dashboard ·
Listing moderation · Leads board.

---

## Brand

Logo, palette and type live in [`assets/brand/`](assets/brand/) and
[docs/09-BRAND-GUIDE.md](docs/09-BRAND-GUIDE.md).

The mark is a **tractor wheel with a sprout at the hub** — machinery and farming meeting at
a junction. White surfaces, green brand: primary `#15703A`, deep `#0B3D20`, tint `#E4F2E8`,
page ground `#F4F8F5`. Red, amber and blue appear only as state signals (rejected, pending,
assigned), never as brand colours.

| File | Use |
|---|---|
| `logo-mark.svg` | App icon — white art on a green tile |
| `logo-mark-green.svg` | Mark on white surfaces |
| `logo-horizontal.svg` | Primary lockup for light backgrounds |
| `logo-horizontal-white.svg` | Lockup for green backgrounds |
| `favicon.svg` | Browser tab, 32 px and below |

## Tech stack

**Laravel 11 · PHP 8.2+ · MySQL 8 · Redis · Blade · Bootstrap 5.3 · jQuery 3.7 · AJAX ·
HTML · CSS**, with DataTables (admin tables), Chart.js (dashboards), Select2, noUiSlider,
Spatie Permission / Activity Log / Sitemap, Intervention Image, dompdf, and Laravel Sanctum
for the future mobile API.

Server-rendered Blade for everything indexable; AJAX enhances filters, wizards, admin
tables and OTP without ever being required to see content. No SPA framework, no Node
runtime needed in production.

Full rationale in [docs/06-TECH-ARCHITECTURE.md](docs/06-TECH-ARCHITECTURE.md).

---

## Where this stands

All five phases are delivered. The remaining work before go-live is content loading,
legal review and the environment configuration listed in
[docs/15-LAUNCH-CHECKLIST.md](docs/15-LAUNCH-CHECKLIST.md) — none of it is code.
