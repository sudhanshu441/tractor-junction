# Krishi Junction

**India's digital marketplace for tractors, farm implements and agri-machinery.**

Krishi Junction is a Laravel-based platform where farmers can research, compare, buy,
sell, finance and insure new & used tractors, harvesters, implements, tyres and farm
tools — plus a full dealer network, content/news hub and a role-based admin panel.

> Reference/benchmark product: tractorjunction.com. This project is an independent
> implementation with its own branding, data model and codebase.

---

## Project status

| Phase | Deliverable | Status |
|---|---|---|
| 0 | Discovery, PRD, ERD, Flows, IA, Prototype | ✅ **Ready for review** |
| 1 | Laravel scaffold, auth, RBAC, master data | ⏸ Awaiting approval |
| 2 | Catalog (new tractors/implements) + Website | ⏸ |
| 3 | Used marketplace + Leads | ⏸ |
| 4 | Dealer panel + Customer panel | ⏸ |
| 5 | Finance (loan/EMI/insurance) | ⏸ |
| 6 | CMS, SEO, Multilingual, Mobile API | ⏸ |

**Nothing is coded yet — by design.** Documentation and prototype come first, as agreed.

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

## Tech stack (proposed)

Laravel 11 · PHP 8.3 · MySQL 8 · Redis · Blade + Alpine.js + Tailwind (website) ·
Livewire/Filament-style admin · Laravel Sanctum (mobile API) · Meilisearch/Scout ·
Spatie Permission + Media Library + Sitemap · S3-compatible storage.

Full rationale in [docs/06-TECH-ARCHITECTURE.md](docs/06-TECH-ARCHITECTURE.md).

---

## Approval gate

Please review the documents above and the prototype, then reply with either:

- ✅ **"Approved — start development"** (optionally naming the phase to start with), or
- 📝 change requests on any document.

Development begins only after that.
