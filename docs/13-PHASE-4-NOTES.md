# Phase 4 — Dealers, finance, inspections & monetisation

**Status: complete.** 172 tests passing (59 new), verified in a browser against the
seeded network: 4 dealers, 3 loan applications, an inspection report and demo reviews.

---

## What shipped

| # | Deliverable | Detail |
|---|---|---|
| 4.1 | Dealer onboarding | Public "become a dealer" form, KYC documents on a private disk, `pending → verified / rejected ⇄ suspended` with reason-required decisions and an audit row |
| 4.2 | Dealer directory | Server-rendered `/dealers`, `/dealers/{state}`, `/dealers/{state}/{district}` and a profile page carrying inventory, used stock, reviews and an enquiry form |
| 4.3 | Dealer panel | Dashboard, lead inbox with status machine and notes, inventory management, plan and billing |
| 4.4 | Plans & billing | Free / Silver / Gold for dealers, two promotion packages for private sellers; checkout opens an order, and nothing is granted until a verified callback settles it |
| 4.5 | Reviews & ratings | Owner reviews on models and dealers, per-aspect scores, verified-owner badge, helpful voting, a moderation queue and a cached star average |
| 4.6 | EMI calculator | Monthly, quarterly, half-yearly and yearly repayment; amortisation by year; a quick eligibility check before the long form |
| 4.7 | Loan applications | Four-step wizard, masked PAN/Aadhaar, private documents, `draft → submitted → under_review ⇄ docs_pending → sent_to_lender → sanctioned → disbursed`, lender matching and per-lender decisions |
| 4.8 | Insurance | Partner panel filtered by state, cover explainer, OTP-verified enquiry that opens both an insurance record and a routable lead |
| 4.9 | Inspections | Request, schedule, 25-item weighted checklist, derived grade A–D, valuation band from the inspected grade, and a separate approval step that earns the verified badge |
| 4.10 | Private document vault | Everything KYC on a non-web-readable disk, served only through 5-minute signed routes behind a policy check, with every access logged |

## Verified in a browser

- **Admin** — dealers (4 rows, one pending), loans (3 rows), inspections, reviews.
- **Loan detail** — PAN renders as `XXXXXX234F`; the KYC note reads *"Only the last four digits are stored"*.
- **Inspection report** — 25 checklist sliders; dropping one from 10 to 0 moved the live score 84.0 → 81.2 and the grade stayed B, matching what the server then computed.
- **Model page** — 2 approved reviews shown, the pending one absent, guests offered a login prompt rather than a form.
- **Dealer profile** — 2 reviews, 4.5 average.
- **Dealer billing** — bought a plan through the log gateway: Free → Gold.
- **Seller promotion** — bought a package on a live listing; the Promoted badge appeared.
- **Insurance** — 4 partner cards, 10-field enquiry form.
- No JavaScript errors and no failed requests on any page.

## Bugs caught before shipping

1. **An abandoned checkout hid the dealer's live plan.** `activeSubscription()` was
   `->where('status','active')->latestOfMany()`, which picks the newest row of *any*
   status and only then filters — so a `pending_payment` row from an abandoned
   upgrade made the relation return null, and with it the dealer's lead caps. The
   constraint now lives inside the aggregate subquery via `ofMany()`.
2. **A paid promotion never ended.** Checkout set `is_featured` and nothing ever
   cleared it, so a seven-day package would have promoted a listing forever. Added
   `boosts:expire`, scheduled hourly, which also respects a second package still
   running on the same listing.
3. **`LoanApplication` had no `state` relation.** The admin loan table 500'd on it,
   and — worse and silently — `matchingLenders()` read `$application->state?->code`
   as `null`, so the "lenders who operate in your state" filter never ran. Relations
   added, with a regression test that asserts the relation resolves *and* that a
   lender outside the state is dropped.
4. **A loan draft could not be saved before the machine was chosen.** `loan_amount`
   is `NOT NULL` and the derived-figures step returned early on a zero price. Same
   class as the Phase 3 draft bug.
5. **`review_ratings` has no timestamps**, so writing an aspect score threw. The
   model now declares `$timestamps = false`.
6. **`Inspection::request()` threw on an ambiguous column** — it queried through
   `UsedListing::inspection()`, a `latestOfMany` relation whose subquery join makes
   an unqualified `where` ambiguous.

## Design decisions worth knowing

| # | Decision | Why |
|---|---|---|
| D1 | Nothing is granted at checkout | An order is a promise. The plan or boost starts only when `settle()` accepts a verified callback, so an abandoned or forged payment leaves the buyer exactly where they were. |
| D2 | A payment is never settled twice | `settle()` returns early on an already-paid row, so a replayed callback cannot stack two subscriptions. |
| D3 | KYC identifiers are stored masked | `ABCDE1234F` is written as `XXXXXX234F`. The full value is never persisted, so there is nothing to leak. |
| D4 | Private documents are never public-disk files | Non-web-readable disk, 5-minute signed route, **and** a policy check inside the controller — a valid signature alone does not let another customer in. |
| D5 | Approval is separate from submitting an inspection | An inspector cannot self-publish a report; the verified badge is a second person's decision. |
| D6 | The grade comes from the weighted checklist, never an overall impression | Two inspectors scoring the same machine should land in the same band. |
| D7 | The EMI on a loan record is always the server's | The browser's figure is display only; a tampered field cannot become the recorded instalment. |
| D8 | Reviews are published by a moderator, never by their author | Everything lands in `pending`, and only approved reviews move the cached average. |
| D9 | An applicant is only ever offered lenders whose published criteria they meet | Showing a lender who will refuse wastes the applicant's week. |
| D10 | An insurance enquiry writes a lead as well as its own record | The insurance desk gets its working record; routing and the SLA reports get a lead. Neither can drift out of the other's sight. |
| D11 | A zero-interest scheme is handled explicitly | It is a real product in this market, and the standard amortisation formula divides by zero on it. |

## Payments without keys

`PAYMENT_DRIVER=log` (the default) records what a real gateway would have been asked
to do and treats every callback as valid, so plan upgrades and boosts can be exercised
end to end before live keys exist. Set `PAYMENT_DRIVER=razorpay` with
`RAZORPAY_KEY_ID` / `RAZORPAY_KEY_SECRET` to switch to the real driver, which verifies
the HMAC signature over `order_id|payment_id` before anything is granted.

## Known gaps, carried forward

- **No refunds.** A payment can be marked `refunded` in the schema; there is no flow.
- **Inspection PDF report** — `report_pdf_path` exists; generation is Phase 5.
- **Dealer branches** are seeded and stored but have no management screen yet.
- **Review replies** — the table and relation exist; a dealer cannot yet reply to a review.
- **Insurance quotes are a callback**, not a rate table; there is no quote engine.
- Loan lender decisions are recorded by staff. No lender-facing portal or API.
- Demo dealers, loans, reviews and the seeded inspection are **sample data** — remove before launch.

## Try it

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

- `/dealers` — the directory · `/dealers/become-a-dealer` — onboarding
- `/loan` — the finance hub · `/loan/emi-calculator` · `/loan/apply`
- `/tractor-insurance` — partners and enquiry
- `/tractors/mahindra/575-di-xp-plus#reviews` — owner reviews
- `/dealer/billing` — plans (log gateway, no keys needed)
- `/account/listings` → **Promote** — a paid listing boost
- `/admin/dealers`, `/admin/loans`, `/admin/inspections`, `/admin/reviews`

Admin `admin@krishijunction.com` / `KrishiAdmin@2026`.
Demo dealers `shri-balaji-tractors@example.com` and sellers `vijay-singh@example.com`,
both with `KrishiDemo@2026`.
