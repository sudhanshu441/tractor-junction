# Krishi Junction — working notes

Laravel marketplace for tractors, implements and farm machinery in India.
Read `docs/` before changing anything structural — the PRD, ERD and flows are the contract.

## Stack (fixed by the client)

Laravel 12 · PHP 8.2+ · MySQL 8 · Blade · **Bootstrap 5 · jQuery · AJAX** · HTML · CSS.
No SPA framework, no Livewire, no admin framework, no Node runtime in production.

## Rules

1. **Server-render anything indexable.** Home, listings, detail, dealer and content pages
   must work with JavaScript off. AJAX enhances; it never gates content.
2. **Layering:** Controller → FormRequest → Action/Service → Model. No business logic in
   controllers or Blade. A web controller and its AJAX twin call the same service.
3. **AJAX envelope** is always `{status, message?, data?, errors?}`. Use `KJ.request()`
   client-side; it resolves rather than throwing.
4. **Brand tokens live in `public/assets/css/custom.css` only.** White surfaces, green
   brand. Red/amber/blue are state signals, never brand colours. See `docs/09-BRAND-GUIDE.md`.
5. **Never query the EAV directly for filters.** Facets read `product_filter_cache`,
   rebuilt by an observer. `product_spec_values` is the source of truth for display.
6. **PII:** contact numbers render masked unless the role holds `leads.view_contact`.
   KYC and loan documents go on a private disk behind signed URLs. PAN/Aadhaar are stored
   masked. Never log PII.
7. **Business rules go in `config/kj.php`** with a matching `settings` row when operations
   should be able to tune them.
8. **Every status change on a listing, lead or loan writes a `*_status_logs` row.**
9. Front-end libraries are vendored in `public/assets/vendor/` — do not reintroduce CDNs.

## Commands

```bash
php artisan migrate --seed        # full schema + masters + super admin
php artisan test                  # feature suite
./vendor/bin/pint                 # format before committing
php artisan geo:import <csv>      # load the authoritative district dataset
```

## Phases

All five phases are complete — see `docs/10-` through `docs/13-` and `docs/17-PHASE-5-NOTES.md`.
Before go-live, work `docs/15-LAUNCH-CHECKLIST.md`; operations live in `docs/16-RUNBOOKS.md`.

Phase 4 rules that are easy to break:
- **Nothing is granted at checkout.** A plan or boost starts only when `BillingService::settle()`
  accepts a verified callback. Never set `is_featured` or activate a subscription from a
  checkout handler.
- **Never persist a full PAN or Aadhaar.** `DocumentVault::mask()` runs before the write.
- **Private documents are never served from a URL alone.** Signed route *and* a policy check
  inside the controller.
- **An inspector cannot publish their own report.** `approve()` is a separate permission.

Phase 5 rules that are easy to break:
- **Never reintroduce a font or script CDN.** Fonts are vendored in
  `public/assets/vendor/fonts/`; a third-party host costs a round-trip before text paints.
- **Analytics is denied until the visitor consents**, and never loads in the admin panel.
- **Never claim a price or rating in JSON-LD that the page does not show.**
- **Public routes are registered twice** (bare and `/hi`). Add new public routes to
  `routes/public.php`, never `routes/web.php`, or they will exist in English only.
- **`route()` is locale-aware** via `LocalizedUrlGenerator` — do not hard-code `/hi`.
- **Keep pages inside their query budget.** `PerformanceBudgetTest` fails on an N+1;
  raising a ceiling should be a decision, not a reflex.
