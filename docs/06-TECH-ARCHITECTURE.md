# Krishi Junction — Technical Architecture

## 1. Stack

| Layer | Choice | Why |
|---|---|---|
| Framework | **Laravel 11** (PHP 8.3) | Your stack; mature ecosystem for exactly these modules |
| DB | **MySQL 8.0** (InnoDB) | Relational fit, window functions, JSON columns, wide hosting support |
| Cache / queue / session | **Redis 7** | Facet caching, queues, rate limiting, locks |
| Search | **Meilisearch** via Laravel Scout | Typo tolerance + Hindi synonyms; falls back to MySQL FULLTEXT if unavailable |
| Front-end (website) | **Blade + Tailwind CSS 3 + Alpine.js** | Server-rendered = SEO + fast on 4G; no SPA weight |
| Interactive widgets | **Livewire 3** where state is complex (filters, compare, wizards) | Avoids a separate API+SPA for admin-ish interactivity |
| Admin panel | **Filament v3** (recommended) or Blade+Livewire custom | Filament gives CRUD, tables, filters, RBAC hooks out of the box — see PRD Q1 |
| Auth | Laravel session (web) + **Sanctum** (API) + custom OTP guard | Multi-panel guards: `web`, `dealer`, `staff`, `sanctum` |
| Media | Spatie Media Library + Intervention Image → S3-compatible (Cloudflare R2 / AWS S3) | Conversions, WebP/AVIF, private disk for KYC |
| Build | Vite | Laravel default |
| Queue worker | Horizon (Redis) | Visibility into notification/indexing jobs |
| Scheduler | Laravel Scheduler via cron | Expiry, sitemap, reminders, aggregates |
| PDF | dompdf / Browsershot | Inspection reports, brochures, compare export |
| Testing | Pest + PHPUnit, Laravel Dusk for critical flows | |
| Static analysis | Larastan (PHPStan lvl 5), Pint (PSR-12) | |
| Error tracking | Sentry | |

### Composer packages
```
spatie/laravel-permission          RBAC
spatie/laravel-medialibrary        media + conversions
spatie/laravel-activitylog         audit trail
spatie/laravel-sitemap             XML sitemaps
spatie/laravel-sluggable           slugs
spatie/laravel-translatable        per-locale content fields
laravel/scout + meilisearch-php    search
laravel/sanctum                    API tokens
laravel/horizon                    queues
intervention/image                 image processing
maatwebsite/excel                  exports/imports
barryvdh/laravel-dompdf            PDFs
propaganistas/laravel-phone        Indian mobile validation
stevebauman/purify                 HTML sanitising for CMS
mews/captcha  or  google recaptcha v3
razorpay/razorpay                  plans & boosts (phase 5)
```

---

## 2. Application structure

Domain-oriented modules inside a standard Laravel skeleton:

```
app/
├── Console/Commands/            ExpireListings, GenerateSitemap, RecalculateAggregates,
│                                SendFollowUpReminders, ReindexSearch, ImportGeography
├── Domain/
│   ├── Catalog/                 Models, Services, Actions, DTOs, Filters
│   ├── Marketplace/             UsedListing, Inspection, Valuation
│   ├── Dealer/
│   ├── Lead/                    LeadService, RoutingEngine, AssignmentPolicy
│   ├── Finance/                 EmiCalculator, LoanApplicationService, LenderMatcher
│   ├── Content/
│   ├── Seo/                     MetaResolver, SchemaBuilder, RedirectHandler
│   ├── Notification/            Channels, TemplateRenderer
│   └── Geo/
├── Http/
│   ├── Controllers/
│   │   ├── Web/                 public site
│   │   ├── Account/             customer panel
│   │   ├── Dealer/              dealer panel
│   │   ├── Admin/               admin (or Filament Resources)
│   │   └── Api/V1/              REST API
│   ├── Middleware/              SetLocale, DetectGeo, PanelGuard, MaintenanceMode, TrackPageView
│   ├── Requests/                FormRequest per action
│   ├── Resources/               API JSON resources
│   └── ViewComposers/
├── Jobs/                        SendSms, SendWhatsapp, ProcessListingImages, RouteLead,
│                                GenerateInspectionPdf, IndexModel
├── Listeners/  Events/  Observers/  Policies/  Notifications/
├── Services/                    Sms, Whatsapp, Payment, Storage gateways (interface + driver)
└── Support/                     Helpers, Enums, Traits
database/
├── migrations/                  ~94 tables, grouped by domain prefix
├── seeders/                     Geography, Brands, Categories, SpecAttributes, DemoProducts,
│                                Roles, Settings, NotificationTemplates
└── factories/
resources/
├── views/{web,account,dealer,admin,components,emails,pdf}
├── js/  css/
└── lang/{en,hi}/
routes/  web.php  account.php  dealer.php  admin.php  api.php  channels.php
tests/{Feature,Unit,Browser}
```

**Layering rule:** Controller → FormRequest → Action/Service → Model. No business logic in
controllers or Blade. Query filtering goes through dedicated `Filters` classes so the same
filter set serves web, API and admin.

---

## 3. Key engineering designs

### 3.1 Spec/EAV query strategy
Filtering on EAV can be slow. Mitigations:
- `product_spec_values.value_number` indexed with `(spec_attribute_id, value_number)`.
- Only `is_filterable` attributes participate in facets (~15 of 120).
- A denormalised `product_filter_cache` table (product_id, hp, wheel_drive, cylinders,
  fuel, price_min, lift_capacity, …) rebuilt by an observer on save — filters read this
  single flat table; the EAV stays the source of truth for display.
- Facet counts cached in Redis per filter combination for 15 minutes.

### 3.2 Lead routing engine
`RoutingEngine::route(Lead $lead)` evaluates ordered `routing_rules`; the first match wins.
Each rule: scope (state/district), brand, category, lead type, assignee type, daily cap.
Eligible dealers are ranked by (plan tier, response-time score, leads-today ascending) →
round-robin. All decisions written to `lead_assignments` with the rule id, so routing is
explainable. Unrouted leads fall back to the state's sales executive and raise an alert.

### 3.3 Search
Scout indexes: `products` (name, brand, category, specs summary, hp, price),
`used_listings` (title, brand, model, year, city, price), `dealers` (name, city, brands),
`blogs`. Hindi synonyms and transliteration pairs configured in Meilisearch settings.
Indexing happens on queue via model observers; a nightly `search:reindex` reconciles.

### 3.4 Caching & invalidation
| Layer | TTL | Invalidated by |
|---|---|---|
| Full-page cache (guest, static pages) | 1 h | content save |
| Home blocks | 30 min | banner/product/offer observers |
| Product detail fragments | 60 min | product observer |
| Facet counts | 15 min | product save |
| Geography, spec attributes, settings | 24 h | master save |
Cache tags per entity; observers flush precisely, never the whole store.

### 3.5 Images
Upload → queue job → generate `thumb (300px)`, `card (600px)`, `detail (1200px)` in WebP +
JPEG fallback → store on S3 → CDN. `<img loading="lazy" srcset sizes>` everywhere, explicit
width/height to protect CLS. Used-listing photos get an EXIF strip + perceptual hash for
duplicate detection.

### 3.6 Localisation
UI strings in `lang/{en,hi}`; content fields via `spatie/laravel-translatable` JSON columns
(`{"en": "...", "hi": "..."}`). `SetLocale` middleware resolves locale from the URL prefix →
user preference → cookie → `Accept-Language`. Every route is registered twice (root and
`/hi`) through a route macro.

### 3.7 API
`/api/v1`, JSON envelope `{data, meta, links, errors}`, Sanctum bearer tokens,
`throttle:api` (60/min guest, 120/min authed), API Resources for serialisation,
OpenAPI spec generated with `scribe` and served at `/docs/api`.

### 3.8 Security
- Bcrypt/Argon2 passwords; OTP stored hashed, single-use, TTL 10 min.
- Rate limits: OTP 5/hr/mobile & 20/hr/IP; enquiry 10/hr/mobile; login 5/min/IP.
- All forms CSRF-protected; CMS HTML sanitised on save; uploads validated by MIME + extension + size, stored outside the webroot, never executed.
- KYC/loan documents on a **private** disk; access only through signed, 5-minute URLs and a policy check; downloads logged.
- PAN/Aadhaar stored masked (`XXXXXX1234`) — full values never persisted in v1.
- Security headers: CSP, HSTS, X-Content-Type-Options, Referrer-Policy, X-Frame-Options.
- Admin: session timeout 30 min, IP allowlist option, 2FA (TOTP) for super-admin.
- No PII in application logs; log scrubbing configured in Sentry.

---

## 4. Environments & deployment

| Env | Purpose | Notes |
|---|---|---|
| local | Development | Laravel Sail / Herd, MySQL, Redis, Meilisearch in Docker |
| staging | Client review + UAT | Same infra shape as prod, seeded demo data, `noindex` |
| production | Live | India-region VM |

**Production topology (v1):** Cloudflare (DNS, CDN, WAF) → Nginx → PHP-FPM 8.3 →
MySQL 8 (same host or managed) + Redis + Meilisearch; S3/R2 for media; Supervisor running
Horizon workers; cron for the scheduler. Vertical scale first; the app is stateless apart
from Redis/DB so a second web node behind a load balancer is a drop-in step.

**Deploy:** GitHub Actions → tests + Pint + Larastan → build assets → zero-downtime deploy
(Deployer/Envoyer style symlink release) → `migrate --force` → `optimize` → queue restart.

**Scheduled jobs:**
| Command | Schedule |
|---|---|
| `listings:expire` | hourly |
| `listings:expiry-reminders` | daily 09:00 |
| `leads:followup-reminders` | daily 09:30 |
| `leads:escalate-unresponded` | every 30 min |
| `sitemap:generate` | daily 02:00 |
| `search:reindex` | daily 03:00 |
| `aggregates:recalculate` (ratings, counts) | hourly |
| `reports:daily-digest` (email to admin) | daily 08:00 |
| `backup:run` | daily 01:00 |
| `logs:archive` (page_views, activity_logs) | monthly |

---

## 5. Third-party integrations

| Service | Purpose | Phase |
|---|---|---|
| MSG91 / Twilio | Transactional SMS + OTP | 1 |
| Amazon SES / SMTP | Email | 1 |
| Cloudflare R2 / AWS S3 | Media + private documents | 1 |
| Meilisearch | Search | 2 |
| Google Maps / Mapbox | Dealer locator map, geocoding | 4 |
| Google reCAPTCHA v3 | Spam control | 2 |
| GA4 + GTM + Meta Pixel | Analytics | 2 |
| Firebase Cloud Messaging | Web push / app push | 6 |
| WhatsApp Business (Gupshup/Interakt) | Lead + status alerts | 5 |
| Razorpay | Dealer plans, listing boosts | 5 |
| Exotel / Knowlarity | Masked calling, call logs | 5 |
| Sentry | Error tracking | 1 |

---

## 6. Quality gates

- PR must pass: Pint, Larastan level 5, Pest suite, no `dd()`/`dump()`.
- Feature tests required for: OTP auth, listing create→approve→live, lead create→route,
  loan submit→status change, EMI math, permission enforcement per role, price resolution
  by state, sitemap generation.
- Seeded staging data for every demo.
- Lighthouse budget checked on home, listing and detail templates before each release.
