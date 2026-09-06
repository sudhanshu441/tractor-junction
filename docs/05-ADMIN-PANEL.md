# Krishi Junction — Admin Panel Specification

Route prefix `/admin`, guard `staff`, all routes behind `auth` + `permission:` middleware.

---

## 1. Menu tree

```
📊 Dashboard
🚜 Catalogue
   ├── Brands
   ├── Categories
   ├── Products (Tractors / Implements / Harvesters / Tyres / Farm tools)
   │     └── Product editor → Basics · Variants · Specs · Prices · Media · Features · FAQs · Competitors · SEO
   ├── Spec groups
   ├── Spec attributes
   ├── Category ↔ attribute mapping
   ├── Price manager (bulk state-wise)
   └── Price change history
🏷️ Used Marketplace
   ├── All listings
   ├── Moderation queue        ⬤ badge = pending count
   ├── Reported listings
   ├── Expiring soon (7 days)
   ├── Inspections
   │     ├── Requests · Schedule · Reports
   ├── Inspection checklist
   └── Valuation rules
🏪 Dealers
   ├── All dealers
   ├── Verification queue      ⬤
   ├── Dealer inventory
   ├── Branches
   ├── Documents
   ├── Subscriptions & plans
   └── Dealer performance
📞 Leads
   ├── All leads
   ├── Unassigned              ⬤
   ├── My leads
   ├── Follow-ups due today    ⬤
   ├── Routing rules
   ├── Lead sources
   └── Duplicates
💰 Finance
   ├── Loan applications
   ├── Docs pending            ⬤
   ├── Lenders
   ├── Application ↔ lender tracker
   ├── Insurance enquiries
   ├── Insurance partners
   └── EMI calculator logs
⭐ Reviews
   ├── Moderation queue        ⬤
   ├── All reviews
   └── Review replies
📰 Content
   ├── Pages
   ├── Blog / News  (+ categories, tags, comments)
   ├── Videos
   ├── FAQs
   ├── Banners & sliders
   ├── Testimonials
   ├── Offers
   └── Menu builder
🔍 SEO
   ├── Meta manager
   ├── Redirects
   ├── 404 log
   ├── Sitemap generator
   └── Schema templates
🌍 Masters
   ├── States / Districts / Cities / Pincodes
   └── Languages & translations
👥 Users & Access
   ├── Customers
   ├── Staff users
   ├── Roles
   ├── Permissions
   └── Blocked users
🔔 Notifications
   ├── Templates
   ├── Delivery logs
   └── Broadcast / campaign
📈 Reports
   ├── Leads · Listings · Dealers · Loans · Users · Reviews
   ├── Traffic & page views
   ├── Search terms
   └── Conversion funnel
⚙️ Settings
   ├── General · SEO defaults · Email · SMS · WhatsApp · Payment
   ├── Social & analytics
   ├── Business rules
   ├── Maintenance mode
   └── Cache & queue tools
📜 Activity log
```

---

## 2. Dashboard

**Stat tiles (today / 7d / 30d toggle):** visitors · new leads · leads converted ·
new used listings · pending moderation · dealers pending verification · loan applications ·
loans sanctioned · new users · reviews pending.

**Charts:** leads by day (line, split by type) · leads by status (donut) · listings by
status (stacked bar) · loan pipeline (funnel) · top 10 districts (bar) · top 10 viewed
models (bar) · traffic source split.

**Action queues (clickable):** pending listings · unassigned leads · follow-ups due ·
dealer verifications · docs-pending loans · pending reviews · reported listings.

**Recent activity feed** from `activity_logs`.

---

## 3. Screen inventory

Every list screen has: search, column filters, date range, bulk actions, per-page selector,
CSV/Excel export, saved views, and column visibility toggles. Every form screen has:
validation with inline errors, unsaved-changes guard, autosave for long forms, and an
audit trail tab.

| Screen | Key columns / fields | Actions |
|---|---|---|
| **Brands** | logo, name, slug, category count, product count, popular, status | create, edit, reorder, toggle, delete (blocked if products exist) |
| **Categories** | tree view, type, icon, product count | create, nest, reorder, toggle |
| **Products list** | image, brand, name, category, HP, price range, status, views, leads, updated | create, edit, duplicate, publish/unpublish, bulk price update, export |
| **Product editor** | 8 tabs (see menu tree) | save draft, publish, preview on site, view history |
| **Spec attributes** | group, name, data type, unit, filterable, comparable, key-spec | create, map to categories, reorder |
| **Price manager** | product × state matrix, ex-showroom, RTO, insurance, on-road, effective from | inline edit, bulk import CSV, copy national → states |
| **Used listings** | ref, photo, title, seller, city, year, hours, price, status, views, leads, expiry | view, approve, reject, request changes, block, feature, extend, mark sold, delete |
| **Moderation queue** | side-by-side photo review, checklist, price-vs-valuation flag | approve / reject with reason / request changes — keyboard shortcuts |
| **Inspections** | ref, listing, inspector, scheduled, grade, score, status | schedule, assign inspector, open report, approve report, download PDF |
| **Dealers** | code, name, type, brands, city, verification, rating, leads, plan | verify, reject, suspend, feature, impersonate (logged), edit |
| **Verification queue** | documents viewer, GSTIN check, checklist | approve / reject with remarks, request more docs |
| **Leads board** | ref, type, name, mobile (masked by permission), context, city, status, quality, assignee, follow-up, age | assign, bulk assign, change status, add activity, merge duplicate, export, call (click-to-call) |
| **Routing rules** | priority, geo scope, brand, category, assignee type, daily cap, active | create, reorder priority, simulate |
| **Loan applications** | ref, applicant, amount, tenure, machinery, status, lender(s), assigned, submitted | open, verify docs, change status, send to lender, record sanction/disbursal, add remark |
| **Lenders** | logo, name, type, rate range, tenure, amount range, states, status | CRUD |
| **Reviews queue** | entity, user, rating, title, excerpt, flags | approve, reject with reason, edit, reply, delete |
| **Blog/News** | cover, title, category, author, status, published, views | create, schedule, publish, feature, duplicate |
| **Banners** | preview, position, device, schedule, impressions, clicks, CTR | CRUD, reorder, activate |
| **Offers** | banner, title, brand/models, discount, validity, status, views, leads | CRUD |
| **SEO meta manager** | entity type filter, URL, title length, description length, robots, warnings | inline edit, bulk template apply |
| **Redirects** | from, to, code, hits, status | CRUD, import CSV |
| **Geography** | state → district → city drill, counts | CRUD, merge, import |
| **Staff users** | name, email, role, scope (states), last login, status | create, assign role & geo scope, reset password, block |
| **Roles** | name, users count, permission count | create, edit permission matrix |
| **Notification templates** | event key, channels, last edited | edit body per channel, send test, toggle |
| **Settings** | grouped tabs of key/value fields | save, clear cache |
| **Activity log** | user, module, action, subject, IP, time | filter, view before/after diff |
| **Reports** | filters + table + chart | export CSV/Excel/PDF, schedule email **[SHOULD]** |

---

## 4. Customer panel screens

| Screen | Contents |
|---|---|
| Dashboard | Stat tiles (my listings, leads received, enquiries sent, loan status), recent activity |
| My listings | Cards with status badge, views, leads; edit / mark sold / renew / boost |
| Listing leads | Buyer name, city, date, status; reveal contact; mark contacted/sold |
| My enquiries | Enquiries I sent, with entity, dealer assigned and status |
| Loan applications | Ref, amount, status timeline, pending documents uploader |
| Wishlist | Saved models, listings, dealers |
| Saved searches | Named filters + alert toggle |
| My reviews | Written reviews with status |
| Notifications | In-app list, mark read |
| Profile | Details, KYC-lite, language, notification preferences, delete-account request |

## 5. Dealer panel screens

| Screen | Contents |
|---|---|
| Dashboard | Leads today/month, plan usage bar, inventory views, conversion rate, response-time score |
| Inventory | New-stock products with offer price & availability; add/remove; bulk |
| Used listings | Dealer-posted used stock (same lifecycle as customer listings) |
| Leads inbox | Filterable list; accept/reject; call, note, status; SLA countdown |
| Lead detail | Buyer info, context (model/listing), full activity timeline |
| Branches | CRUD with geo |
| Staff | Add staff users with scoped access |
| Reviews | Received reviews, reply |
| Subscription | Current plan, usage, upgrade, invoices |
| Reports | Leads by status/day, conversion, top models enquired |
| Profile | Business details, brands, documents, photos, working hours |

---

## 6. Roles & permission matrix

Permissions are `module.action` — actions: `view`, `create`, `edit`, `delete`, `approve`,
`assign`, `export`, `configure`.

| Module | super-admin | admin | catalog-manager | content-editor | moderator | sales-exec | finance-exec | dealer-owner | dealer-staff | customer |
|---|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|:--:|
| Dashboard | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | own | own | own |
| Catalogue (brands, products, specs) | ✔ | ✔ | ✔ | view | view | view | view | view | view | – |
| Prices | ✔ | ✔ | ✔ | – | – | view | view | view | – | – |
| Used listings | ✔ | ✔ | view | – | ✔ approve | view | – | own | own | own |
| Inspections | ✔ | ✔ | – | – | ✔ | – | – | view own | – | view own |
| Dealers | ✔ | ✔ | – | – | verify | view | – | own | own | – |
| Dealer inventory | ✔ | ✔ | ✔ | – | – | view | – | own | own | – |
| Leads | ✔ | ✔ | – | – | view | ✔ assigned | ✔ finance types | own | own | own |
| Lead routing rules | ✔ | ✔ | – | – | – | – | – | – | – | – |
| Loan applications | ✔ | ✔ | – | – | – | view | ✔ | view own | – | own |
| Lenders / insurance partners | ✔ | ✔ | – | – | – | – | ✔ | – | – | – |
| Reviews | ✔ | ✔ | – | ✔ | ✔ approve | – | – | reply own | – | own |
| Content (blog, pages, videos, FAQ) | ✔ | ✔ | – | ✔ | – | – | – | – | – | – |
| Banners & offers | ✔ | ✔ | ✔ | ✔ | – | – | – | – | – | – |
| SEO | ✔ | ✔ | edit own entities | ✔ | – | – | – | – | – | – |
| Geography masters | ✔ | ✔ | – | – | – | – | – | – | – | – |
| Users & customers | ✔ | ✔ | – | – | view | view | view | – | – | – |
| Staff, roles, permissions | ✔ | limited | – | – | – | – | – | – | – | – |
| Notification templates | ✔ | ✔ | – | ✔ | – | – | – | – | – | – |
| Reports | ✔ | ✔ | catalogue | content | moderation | own leads | finance | own | own | – |
| Settings & integrations | ✔ | – | – | – | – | – | – | – | – | – |
| Activity log | ✔ | ✔ | – | – | – | – | – | – | – | – |
| Impersonate user | ✔ | – | – | – | – | – | – | – | – | – |

Legend: ✔ full · `own` limited to own records · `view` read-only · `–` no access.
Sales executives and finance executives are additionally scoped to assigned states/districts.

---

## 7. Admin UX rules

1. **Two clicks max** from dashboard to any action queue.
2. **Every destructive action** needs a typed confirmation and writes an audit entry.
3. **Reason required** on every reject/block — it is shown verbatim to the affected user.
4. **Bulk everything** on list screens: assign, approve, export, activate.
5. **Keyboard shortcuts** in the moderation queue (`A` approve, `R` reject, `→` next).
6. **Server-side pagination + filters** everywhere; no unbounded queries.
7. **Mobile-usable** for moderation and leads (ops staff work from phones).
8. **Impersonation** (super-admin only) is time-boxed, banner-flagged and fully logged.
9. **PII masking**: mobile numbers masked unless the role has `leads.view_contact`.
10. **Optimistic UI with rollback** on toggles; toasts confirm every write.
