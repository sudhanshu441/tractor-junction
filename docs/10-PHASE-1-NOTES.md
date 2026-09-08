# Phase 1 — Foundation, admin shell & masters

**Status: complete.** 24 feature tests passing, application verified running in a browser.

---

## Running it

```bash
composer install
cp .env.example .env && php artisan key:generate

# MySQL (production target)
#   set DB_CONNECTION=mysql and the DB_* credentials in .env, then:
php artisan migrate --seed

php artisan serve      # http://localhost:8000
```

**Seeded super admin:** `admin@krishijunction.com` / mobile `9000000001`
Local password `KrishiAdmin@2026` — in `production` the seeder generates a random
password and prints it once. Change it on first login either way.

Mobile OTP login works without an SMS account: with `SMS_DRIVER=log` and
`KJ_OTP_DEBUG_LOG=true` the code is written to `storage/logs/laravel.log`.

## What shipped

| # | Deliverable | Detail |
|---|---|---|
| 1.1 | Laravel install, structure, tooling | Laravel 12.69, PHP 8.2+, Pint, PHPUnit, `config/kj.php` for business rules |
| 1.2 | Bootstrap 5 brand theme | `public/assets/css/custom.css` — white/green tokens over Bootstrap variables, Archivo + IBM Plex, logo and favicons wired in |
| 1.3 | Layouts & components | `base`, `app` (public), `admin`, `panel` (customer/dealer) + header, footer, stat tiles, badges, toasts |
| 1.4 | Database | 15 migrations → **106 tables** (94 domain + Laravel/vendor), 86 Eloquent models, relationships on every core model, `UserFactory` |
| 1.5 | Geography master | 36 states/UTs, 310 districts, 310 cities seeded; dependent AJAX selects; `geo:import` CSV command for the full national dataset |
| 1.6 | Authentication | Mobile OTP (hashed, single-use, 10-min TTL, rate-limited per mobile and per IP) + email/password for staff and dealers; login throttling; blocked-account handling |
| 1.7 | RBAC | 11 roles, **134 permissions** (`module.action`), per-route enforcement, staff CRUD, permission matrix editor |
| 1.8 | Admin shell | Sidebar with phase markers, dashboard with live counts, DataTables server-side staff list with inline block/activate, activity log surface |
| 1.9 | Services & masters | 27 settings, 25 notification templates, SMS gateway abstraction (log + MSG91 drivers), media table, notification logging |

## Verified

- `php artisan migrate:fresh --seed` builds the whole schema and seeds it.
- OTP signup → login round-trip in a real browser: user created, `customer` role assigned, mobile marked verified, redirected to the account dashboard.
- Geography cascade over AJAX: Uttar Pradesh → 76 districts → cities.
- Permission enforcement: a moderator is refused `admin/staff/create`, an admin is allowed; a customer and a dealer are both refused the admin panel; a blocked staff user is logged out.
- Password login as super admin lands on `/admin/dashboard`.
- Contact numbers render masked (`90XXXXXX01`) for roles without `leads.view_contact`.

## Three bugs the tests caught

1. **First-time signup returned 403.** A freshly created user's `is_active` was `null`
   in memory (the DB default only applies on read), so the "is this account active"
   guard rejected every new user. Fixed by setting the flag explicitly on create.
2. **500 on login without a name.** `name` is optional, so it was absent from the
   validated array rather than empty. Fixed with a null-coalesce.
3. Laravel's stock `ExampleTest` hit `/` with no database. Removed — `SmokeTest` covers it.

## Deviations from the approved documents

Each of these is a deliberate call, not drift. Say the word and any can be changed.

| # | Document said | Built | Why |
|---|---|---|---|
| D1 | Laravel 11 | **Laravel 12.69** | Laravel 11 is past its security-patch window and `composer audit` reported 3 open advisories against it, one rated high. Laravel 12 is current, still receiving security patches, and is the newest release that runs on PHP 8.2 — Laravel 13 requires 8.3. Same Blade/Eloquent APIs; nothing in the design changes. |
| D2 | Four auth guards (`web`, `dealer`, `staff`, `sanctum`) | One session guard + `user.type` middleware + Spatie roles | One `users` table backs every panel, so separate guards would duplicate auth config without adding a boundary. The panel gate is enforced in middleware and covered by tests. Sanctum still arrives with the API in phase 5. |
| D3 | ~180 permissions | **134 permissions** | That is what the real module × action matrix yields. The estimate was approximate; the matrix is the source of truth. |
| D4 | ~780 districts, ~4 000 cities | 310 districts / 310 cities across 8 states | Fabricating a national district list risks wrong data in a production dropdown. The 8 highest-volume agri states are seeded (UP, MP, RJ, MH, PB, HR, GJ, BR) and `php artisan geo:import <csv>` loads the authoritative dataset without a code change. **This needs the real dataset before launch.** |
| D5 | Bootstrap/jQuery from CDN | Vendored into `public/assets/vendor/` | A CDN dependency is a bad fit for rural connectivity and restricted networks. Local files also keep the app deployable with no Node runtime. |
| D6 | Meilisearch | MySQL FULLTEXT + `search_synonyms` | Already agreed in the architecture rewrite; the `search_synonyms` table is migrated and ready. |

## Known gaps, carried into later phases

- Fonts still load from Google Fonts. Self-host them in phase 5 if offline resilience matters.
- `TrackPageView` middleware is wired but records nothing until phase 2 attaches the viewable model.
- Media conversions (thumb/card/detail WebP) have a table and a service seam; the queue job lands with the first real uploads in phase 2.
- No Redis in this environment, so caching ran on the database driver. Set `CACHE_STORE=redis` in production.
- Password reset via email is scaffolded by Laravel but has no branded template yet.

## Phase 1 file map

```
app/
├── Console/Commands/ImportGeography.php     geo:import CSV
├── Domain/Auth/OtpService.php               issue + verify, rate limits
├── Http/
│   ├── Controllers/{Auth,Web,Admin,Account,Dealer,Ajax}/
│   ├── Middleware/{SetLocale,EnsureUserType,TrackPageView}.php
│   └── Requests/Admin/StaffRequest.php
├── Models/                                  86 models
├── Providers/KrishiJunctionServiceProvider.php
└── Services/Sms/{SmsGateway,LogSmsGateway,Msg91SmsGateway,SmsManager}.php
config/kj.php                                OTP, SMS, listing and lead rules
database/
├── data/india-states.php                    geography dataset
├── migrations/                              15 files, 106 tables
└── seeders/                                 roles, geography, settings, templates, admin
public/assets/{css,js,vendor,brand}
resources/views/{layouts,partials,web,auth,admin,account,dealer}
routes/{web,ajax,account,dealer,admin}.php
tests/Feature/{Auth,Admin,Ajax}/             24 tests
```

## Ready for Phase 2

Phase 2 (catalogue and public website, 12–14 days) builds on this directly: the
`products`/`spec_attributes`/`product_prices` tables, the `product_filter_cache`
denormalisation and the `media` table are already migrated, and the admin CRUD
scaffolding and DataTables base are in place to hang the product editor on.
