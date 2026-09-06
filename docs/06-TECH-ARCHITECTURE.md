# Krishi Junction — Technical Architecture

Stack fixed by the client: **Laravel · PHP · Bootstrap · jQuery · AJAX · HTML · CSS**.
Everything below is built inside that constraint — no SPA framework, no Node-based
front-end build required for the app to run.

## 1. Stack

| Layer | Choice | Why |
|---|---|---|
| Framework | **Laravel 11** (PHP 8.2+) | Your stack; queues, auth, ORM, scheduler out of the box |
| Templating | **Blade** → server-rendered HTML | Every page is crawlable without JS |
| CSS | **Bootstrap 5.3** + one `custom.css` of brand overrides | Grid, components, responsive utilities; brand applied via Sass variables |
| JS | **jQuery 3.7** + Bootstrap 5 JS bundle | Client requirement; enough for everything below |
| Interactivity | **AJAX** (`$.ajax` / `fetch` wrappers) returning JSON or rendered Blade partials | Filters, pagination, compare, wizards, admin tables, OTP |
| DB | **MySQL 8.0** (InnoDB, utf8mb4) | Relational fit; JSON columns; FULLTEXT for v1 search |
| Cache / queue / session | **Redis 7** (file/database driver acceptable on shared hosting) | Facet caching, queues, rate limits |
| Search | **MySQL FULLTEXT + indexed filter table** in v1; Meilisearch optional in phase 5 | Keeps v1 deployable on any LAMP host |
| Admin panel | **Custom Blade + Bootstrap + DataTables** | Client stack; no Filament/Livewire |
| Auth | Laravel session guards (`web`, `dealer`, `admin`) + custom OTP flow + **Sanctum** for the API | Multi-panel, one users table |
| Media | Intervention Image + Laravel filesystem → local or S3-compatible | WebP conversions, private disk for KYC |
| Assets | Laravel Vite **or** plain concatenated CSS/JS in `public/assets` | Deployable on hosts without Node |
| Charts | Chart.js 4 (CDN or bundled) | Admin/dealer dashboards |
| Tables | DataTables 1.13 with server-side processing | Admin list screens, exports |
| Editor | TinyMCE / Summernote (jQuery) | CMS content |
| Queue worker | `queue:work` under Supervisor | SMS, images, indexing, notifications |
| PDF | dompdf | Inspection reports, compare export, invoices |
| Testing | PHPUnit feature tests | Critical flows |
| Style | PSR-12 via Laravel Pint | Consistency |
| Errors | Sentry (or Laravel log + mail on exception) | Monitoring |

### Composer packages
```
spatie/laravel-permission        roles & permissions
spatie/laravel-activitylog       audit trail
spatie/laravel-sitemap           XML sitemaps
spatie/laravel-sluggable         slugs
intervention/image               image conversions
maatwebsite/excel                imports & exports
barryvdh/laravel-dompdf          PDFs
propaganistas/laravel-phone      Indian mobile validation
mews/purifier                    sanitise CMS HTML
laravel/sanctum                  API tokens for the mobile app
razorpay/razorpay                dealer plans & listing boosts (phase 4)
```

### Front-end libraries (CDN-pinned or vendored into `public/assets/vendor`)
```
bootstrap 5.3        css + js bundle (popper included)
jquery 3.7
datatables 1.13      + bootstrap5 theme, server-side mode
select2 4.1          searchable brand/model/district selects
chart.js 4           dashboards
sweetalert2          confirm dialogs for destructive admin actions
dropzone / custom    multi-image upload with preview & reorder
noUiSlider           price and HP range sliders
lightbox2            listing photo gallery
toastr               AJAX success/error toasts
```

---

## 2. How AJAX is used (and where it is not)

**Server-rendered first.** Every indexable page — home, listings, model detail, used
listings, dealer pages, blog — is a full Blade response. Search engines and low-end phones
never depend on JS to see content.

**AJAX enhances, never replaces:**

| Interaction | Pattern |
|---|---|
| Listing filters & sort | `GET /tractors/filter` returns a rendered Blade partial of the result grid + facet counts; jQuery swaps `#result-grid` and pushes the SEO-friendly URL with `history.pushState`. Direct hits on that URL still render server-side. |
| Infinite scroll / "load more" | Same endpoint, `?page=n`, appends rows |
| Compare bar | `POST /compare/add` → JSON `{count, items}`; the compare page itself is a normal server-rendered route |
| OTP send / verify | `POST /auth/otp/send` and `/verify` → JSON; throttled server-side |
| Sell wizard | Each step `POST`s to `/sell/step/{n}` → JSON `{ok, draft_id, errors}`; the draft is saved server-side so a dropped connection loses nothing |
| Image upload | `POST /sell/photo` multipart, one file per request, returns thumbnail URL |
| EMI calculator | Calculated in JS for instant feedback, then re-computed server-side on submit — the server value is the one stored |
| Dependent selects | state → district → city, brand → model: `GET /api/geo/districts/{state}` |
| Admin tables | DataTables server-side: `POST /admin/{module}/data` returns `{draw, recordsTotal, recordsFiltered, data}` |
| Admin inline actions | approve / reject / toggle / assign → JSON, row updated in place, toast confirmation |
| Notifications bell | Polls `/account/notifications/unread` every 60 s |

**Rules:** every AJAX route is CSRF-protected (`$.ajaxSetup` sends `X-CSRF-TOKEN`),
authorised by the same policies as its non-AJAX equivalent, rate-limited, and returns a
consistent envelope `{status, message, data, errors}`.

---

## 3. Application structure

```
app/
├── Console/Commands/       ExpireListings, GenerateSitemap, RecalculateAggregates,
│                           SendFollowUpReminders, EscalateLeads, ImportGeography, ImportCatalog
├── Domain/
│   ├── Catalog/            Services, Actions, Filters, DTOs
│   ├── Marketplace/        UsedListing, Inspection, Valuation
│   ├── Dealer/
│   ├── Lead/               LeadService, RoutingEngine
│   ├── Finance/            EmiCalculator, LoanApplicationService, LenderMatcher
│   ├── Content/  Seo/  Notification/  Geo/
├── Http/
│   ├── Controllers/
│   │   ├── Web/            public site (full page responses)
│   │   ├── Ajax/           partial/JSON endpoints used by jQuery
│   │   ├── Account/        customer panel
│   │   ├── Dealer/         dealer panel
│   │   ├── Admin/          admin panel
│   │   └── Api/V1/         mobile API
│   ├── Middleware/         SetLocale, DetectGeo, PanelGuard, TrackPageView, MaintenanceMode
│   ├── Requests/           one FormRequest per action, reused by web + AJAX + API
│   └── Resources/          API JSON resources
├── Jobs/  Events/  Listeners/  Observers/  Policies/  Notifications/
├── Models/
└── Services/               Sms, Whatsapp, Payment, Storage — interface + driver
resources/views/
├── layouts/                app, admin, dealer, account, mail, pdf
├── components/             card, badge, pagination, stepper, filter-panel, empty-state
├── web/                    home, tractors, used, dealers, loan, content
├── partials/ajax/          result-grid, facet-counts, compare-bar, lead-row  ← AJAX responses
├── account/  dealer/  admin/
public/assets/
├── css/  bootstrap.min.css, custom.css
├── js/   app.js, filters.js, wizard.js, admin.js
└── vendor/ jquery, datatables, chartjs, select2, nouislider
database/migrations|seeders|factories
routes/  web.php  ajax.php  account.php  dealer.php  admin.php  api.php
```

**Layering rule:** Controller → FormRequest → Action/Service → Model. A web controller and
its AJAX counterpart call the *same* service; only the response format differs.

---

## 4. Key engineering designs

### 4.1 Spec/EAV query strategy
Specs live in an EAV structure (see the ERD) so admins can add attributes without a
migration. Filtering does **not** query EAV directly: a denormalised `product_filter_cache`
table (product_id, hp, wheel_drive, cylinders, fuel, price, lift_capacity, …) is rebuilt by
a model observer on every save, and all facet queries hit that single flat, indexed table.
Facet counts are cached in Redis for 15 minutes.

### 4.2 Search without a search server
v1 uses MySQL `FULLTEXT` on `products(name)`, `used_listings(title)`, `dealers(business_name)`
plus a `search_synonyms` table mapping Hindi/transliterated terms ("महिंद्रा", "mhindra" →
"mahindra") applied before the query. Type-ahead is an AJAX endpoint returning the top 8
matches per type. Meilisearch can be dropped in later behind the same `SearchService`
interface without touching controllers.

### 4.3 Lead routing engine
`RoutingEngine::route(Lead $lead)` walks ordered `routing_rules`; first match wins. Eligible
dealers are ranked by (plan tier, response-time score, leads-today ascending) and picked
round-robin. Every decision is written to `lead_assignments` with the rule id, so routing is
explainable. Unrouted leads fall back to the state's sales executive and raise an alert.

### 4.4 Caching
| Layer | TTL | Invalidated by |
|---|---|---|
| Home blocks | 30 min | banner/product/offer observers |
| Product detail fragments | 60 min | product observer |
| Facet counts | 15 min | product save |
| Geography, spec attributes, settings | 24 h | master save |
Blade fragments cached with `Cache::remember`, tagged per entity, flushed precisely by observers.

### 4.5 Images
Upload → queue job → `thumb 300w`, `card 600w`, `detail 1200w` in WebP + JPEG fallback →
local disk or S3 → served through the CDN. `loading="lazy"`, `srcset`, explicit
width/height to protect CLS. Used-listing photos get EXIF stripped and a perceptual hash
stored for duplicate detection.

### 4.6 Localisation
UI strings in `lang/{en,hi}`; translatable content fields stored as JSON
(`{"en": "...", "hi": "..."}`) with an accessor. `SetLocale` middleware resolves from the
URL prefix → user preference → cookie → `Accept-Language`. Routes registered twice (root and
`/hi`) through a route macro; `hreflang` pairs emitted on every mirrored page.

### 4.7 Security
- OTP hashed, single-use, 10-minute TTL. Rate limits: OTP 5/hr/mobile and 20/hr/IP, enquiry 10/hr/mobile, login 5/min/IP.
- CSRF on every form and every AJAX request; CMS HTML sanitised on save; uploads validated by MIME + extension + size and stored outside the webroot.
- KYC and loan documents on a **private** disk, reachable only through 5-minute signed URLs behind a policy check; every download logged.
- PAN/Aadhaar stored masked (`XXXXXX1234`) — full values are never persisted in v1.
- Headers: CSP, HSTS, X-Content-Type-Options, Referrer-Policy, X-Frame-Options.
- Admin: 30-minute session timeout, optional IP allowlist, 2FA for super-admin.
- No PII in application logs.

---

## 5. Environments & deployment

| Env | Notes |
|---|---|
| local | Laravel Sail or XAMPP/Herd — MySQL + Redis |
| staging | Same shape as production, seeded demo data, `noindex` |
| production | India-region VPS |

**Topology:** Cloudflare (DNS, CDN, WAF) → Nginx or Apache → PHP-FPM 8.2 → MySQL 8 + Redis;
media on local disk or S3; Supervisor for queue workers; cron for the scheduler. The app is
stateless apart from DB/Redis, so a second web node behind a load balancer is a drop-in step.

**Deploy:** git pull → `composer install --no-dev -o` → `migrate --force` →
`config:cache route:cache view:cache` → `queue:restart`. GitHub Actions runs Pint and the
test suite on every push.

**Scheduled jobs:** `listings:expire` hourly · `listings:expiry-reminders` daily 09:00 ·
`leads:followup-reminders` daily 09:30 · `leads:escalate-unresponded` every 30 min ·
`sitemap:generate` daily 02:00 · `aggregates:recalculate` hourly · `reports:daily-digest`
daily 08:00 · `backup:run` daily 01:00.

---

## 6. Third-party integrations

| Service | Purpose | Phase |
|---|---|---|
| MSG91 / Twilio | Transactional SMS + OTP | 1 |
| SMTP / Amazon SES | Email | 1 |
| S3-compatible storage | Media + private documents | 1 |
| Google reCAPTCHA v3 | Spam control | 2 |
| GA4 + GTM | Analytics | 2 |
| Google Maps | Dealer locator, geocoding | 3 |
| Razorpay | Dealer plans, listing boosts | 4 |
| WhatsApp Business (Gupshup/Interakt) | Lead and status alerts | 4 |
| Exotel / Knowlarity | Masked calling, call logs | 4 |
| Firebase Cloud Messaging | Web/app push | 5 |
| Meilisearch (optional) | Upgrade from FULLTEXT search | 5 |

---

## 7. Quality gates

- Every PR passes Pint and the PHPUnit suite; no `dd()` or `dump()`.
- Feature tests required for: OTP auth, listing create → approve → live, lead create →
  route, loan submit → status change, EMI maths, per-role permission enforcement,
  state-wise price resolution, sitemap generation.
- Every AJAX endpoint has a test asserting its JSON envelope and its authorisation.
- Lighthouse checked on home, listing and detail templates before each release.
