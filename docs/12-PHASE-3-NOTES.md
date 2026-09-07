# Phase 3 — Used marketplace, leads & customer panel

**Status: complete.** 113 feature tests passing (56 new), verified in a browser against
a seeded marketplace of 18 listings.

---

## What shipped

| # | Deliverable | Detail |
|---|---|---|
| 3.1 | Sell wizard | Six steps, each saved server-side as a draft; brand→model narrowing over AJAX; one photo per request with a guided angle; OTP verification; duplicate detection at submit |
| 3.2 | Listing lifecycle | `draft → pending → live → sold/expired/rejected/blocked` enforced by a single `transition()` that writes an audit row and fires the matching notification; hourly expiry command; renew and repost |
| 3.3 | Public used grid | AJAX filters on budget, year, engine hours, brand, condition and inspected-only; geo URLs at `/used/tractors/{state}/{district}` |
| 3.4 | Listing detail | Gallery, condition panel, catalogue-derived specifications, fair-price band, gated seller contact |
| 3.5 | Moderation queue | One listing at a time with photos, a five-point check panel, price-vs-valuation flag, duplicate-photo detection, seller history, reason-required decisions, `A`/`C`/`R`/`→` keys, bulk approve |
| 3.6 | Report a listing | Buyer-facing modal, one open report per person, admin dismiss or block |
| 3.7 | Lead engine | Nine enquiry types in one table, OTP-verified capture, 7-day dedupe window, full activity timeline |
| 3.8 | Routing engine | Ordered rules, first match wins; dealers ranked by plan tier, response score and today's volume; daily caps; SLA escalation command that also docks the dealer's score |
| 3.9 | Admin lead board | Server-side DataTable, type/status/state filters, bulk routing, assignment, status machine, notes with follow-up dates, duplicate merge, streamed CSV export |
| 3.10 | Customer panel | Dashboard, my listings with renew/mark-sold, buyers received, enquiries sent, per-listing timeline |
| 3.11 | Notifications | Template-driven dispatcher covering submitted, approved, rejected, expiring, expired, sold, and every lead assignment |

## Verified in a browser

- Used grid: 15 listings narrowed to 3 with "inspected only", URL updated to a shareable `?verified=1`.
- Listing detail: fair-price band rendered; the seller's number stays hidden behind the verify form.
- Sell wizard: category → step 2, six Mahindra models loaded over AJAX.
- Moderation queue: "1 of 3", four photos, five checks — all passing — and a price flag reading *58.3% above the estimated band of ₹227,100 – ₹266,600*. Pressing `A` approved it and the queue moved to "1 of 2".
- No JavaScript errors on any page.

## Five bugs caught before shipping

1. **A draft could not be saved.** `title`, `slug`, `manufacturing_year` and
   `expected_price` were `NOT NULL`, but a seller has told us none of them at step 1.
   Those columns are now nullable, and `submit()` refuses to send an incomplete
   listing to moderation.
2. **The moderation queue returned a 500 on a warm cache.** The valuation rule set
   was cached as a Collection; a persistent cache store returns that as
   `__PHP_Incomplete_Class`. This is the same trap as Phase 2 and my tests missed it
   because the array cache driver never serialises — so there is now a
   `CacheSerialisationTest` that forces the database store and exercises every
   cached page and endpoint cold *and* warm.
3. **Two more instances of the same hazard**, found by auditing every
   `Cache::remember` in the codebase: the geography selects (used on the home page,
   the sell wizard and every lead form) and trending searches. Both now cache arrays.
4. **A new dealer's response score read as zero.** Database defaults do not populate
   an in-memory model, so the routing engine sorted freshly created dealers to the
   bottom. Same class of bug as the Phase 1 signup failure; `Dealer` and `UsedListing`
   now declare `$attributes` defaults.
5. **Demo listings had no photos**, which made the moderation queue — a photo-review
   screen — impossible to evaluate. The seeder now generates labelled placeholders.

## Design decisions worth knowing

| # | Decision | Why |
|---|---|---|
| D1 | One `transition()` owns every listing status change | No status can move without an audit row and a notification. Illegal transitions throw rather than silently corrupting state. |
| D2 | Each wizard step POSTs and saves | A dropped connection in a field loses one step, not the whole listing. |
| D3 | One photo per request | Kinder to a weak connection than a single large multipart POST. |
| D4 | A used-listing enquiry always routes to that listing's owner | It is their machine; no rule should be able to send it elsewhere. |
| D5 | Duplicate enquiries note the original instead of opening a second thread | Dealers should never be charged twice for the same buyer. |
| D6 | The winning rule id is stored on the assignment | Any routing decision can be explained afterwards instead of guessed from logs. |
| D7 | Contact reveal requires the buyer's own OTP | It is the anti-spam mechanism and the quality guarantee in one. |
| D8 | Valuation is always a **range** | It is an estimate from public data, not an appraisal, and should not read like one. |
| D9 | Rejection reasons are shown to the seller verbatim | So the reason field is validated as required and written for that audience. |

## Known gaps, carried forward

- **Dealer panel is Phase 4.** Leads route to dealers correctly and dealers can be
  created, but there is no dealer-facing UI yet; dealer-assigned leads are worked from
  the admin board.
- **Inspections are Phase 4.** The `is_verified` badge, checklist tables and valuation
  hooks all exist; the inspector workflow does not.
- Saved searches, wishlist and profile editing are still stubs in the customer panel.
- Distance filtering is by district, not radius — `latitude`/`longitude` are captured
  but the "within 25 km" filter needs the Maps integration in Phase 4.
- WhatsApp and web-push channels are declared in templates but only SMS and in-app are dispatched.
- Demo listings and their placeholder photos are **sample data** — remove before launch.

## Try it

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

- `/used` — grid with live filters
- `/sell` — the six-step wizard
- `/admin/used-listings/moderation` — the queue (`A` approve, `C` changes, `R` reject, `→` skip)
- `/admin/leads` — the lead board
- `/account/listings` — the seller's view

Admin `admin@krishijunction.com` / `KrishiAdmin@2026`; demo sellers use `KrishiDemo@2026`.
