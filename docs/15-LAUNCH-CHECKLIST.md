# Launch checklist

Nothing here is optional. An unticked box is a decision someone has to make out
loud, not a task to skip quietly.

---

## Blocking — the site must not go live without these

### Legal and content
- [ ] **A lawyer has reviewed `/privacy-policy` and `/terms-and-conditions`.**
      The seeded wording is placeholder text that describes what the software
      actually does. It is a starting point for that review, not a substitute.
- [ ] Company name, registered address, GSTIN and support number filled in under
      **Settings** — they appear in the footer and in the Organization schema.
- [ ] The refund and cancellation position for dealer plans and listing
      promotions is written down and published. There is no refund flow in the
      code; whatever is promised must be something operations can honour by hand.

### Secrets and configuration
- [ ] `APP_ENV=production`, `APP_DEBUG=false`. **Check this twice** — `APP_DEBUG=true`
      in production exposes environment variables on every error page.
- [ ] `APP_KEY` generated for production and stored in the password manager, not
      copied from staging.
- [ ] `APP_URL` is the real https URL. Signed document links are built from it,
      and a wrong value silently breaks every KYC download.
- [ ] Database user has no `DROP` or `GRANT` rights.
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`.
- [ ] `PAYMENT_DRIVER=razorpay` with live keys — the default `log` driver marks
      every payment successful without taking money.
- [ ] `KJ_SMS_DRIVER=msg91` with a live key — the default `log` driver sends no
      OTP at all, so nobody can sign in.
- [ ] `MAIL_MAILER` configured and a test email delivered.

### Security
- [ ] `composer audit` clean.
- [ ] `storage/` and `bootstrap/cache` writable; **nothing else** writable by the
      web user.
- [ ] `storage/app/private-documents` is outside the web root and returns 404
      when requested directly. Verify by URL, not by reading the config.
- [ ] The super admin password from the seeder has been changed.
- [ ] Demo data removed: `DemoProductSeeder`, `DemoListingSeeder`,
      `DemoDealerSeeder`, `DemoFinanceSeeder` and their placeholder photos.
- [ ] HTTPS enforced at the edge; HSTS header confirmed present.
- [ ] Rate limits reviewed for the real traffic shape (OTP: 5/hour per mobile).

### Data
- [ ] The authoritative district dataset imported: `php artisan geo:import <csv>`.
- [ ] Real brands, categories and specification attributes loaded.
- [ ] At least one routing rule with `lead_type` NULL exists — without a
      catch-all, some leads route to nobody.
- [ ] Plans priced as the business intends, in both the `plans` table and any
      published price list.

### Operations
- [ ] `php artisan schedule:work` (or a system cron running `schedule:run`) is
      supervised and restarts on failure.
- [ ] Queue worker supervised: `php artisan queue:work --tries=3`.
- [ ] **A restore rehearsal has been completed and logged** — see
      `docs/16-RUNBOOKS.md` §3. A backup nobody has restored is a rumour.
- [ ] Off-site backup copy configured. A dump on the same disk as the database
      survives a bad deploy, not a dead disk.
- [ ] Error tracking (Sentry or equivalent) receiving events from production.
- [ ] Uptime monitoring on `/up`, the home page and one model page.

---

## Should be done before launch

### SEO
- [ ] `php artisan seo:sitemap` run and `/sitemap.xml` returns the index.
- [ ] `/robots.txt` shows `Allow: /` in production. **If it shows `Disallow: /`,
      `APP_ENV` is not `production`** — that guard is deliberate and it will keep
      the whole site out of Google.
- [ ] Search Console and Bing Webmaster verified; sitemap submitted.
- [ ] Rich Results Test passes on a model page, a used listing, a dealer profile,
      an article and the FAQ page.
- [ ] Canonical URLs correct on paginated and filtered listing pages.
- [ ] Redirects loaded for any URL that existed on a previous site.

### Performance
- [ ] Lighthouse on 4G throttling: performance ≥ 90, SEO ≥ 95 on the home page,
      a listing page and a model page.
- [ ] `php artisan kj:warm` in the deploy script.
- [ ] Redis configured for cache and sessions (`CACHE_STORE=redis`).
      **The array driver never serialises**, which is how two cache bugs reached
      staging in earlier phases — the database or Redis store is what production
      must use, and `CacheSerialisationTest` guards it.
- [ ] Images served with far-future cache headers.
- [ ] Gzip or Brotli enabled at the web server.

### Analytics
- [ ] GA4 or GTM ID entered in **Settings**. Until one is set, no analytics and
      no consent banner load at all.
- [ ] Consent banner tested: analytics must not fire before a choice is made.
- [ ] Conversion events agreed with the business (enquiry submitted, listing
      published, loan application submitted).

### Content
- [ ] Header and footer menus reviewed under **Menus**.
- [ ] FAQ set reviewed by someone who answers the phone.
- [ ] At least six articles published — a news section with three posts reads
      abandoned.
- [ ] Hindi interface strings reviewed by a native speaker under
      **Interface language**. Machine-adjacent phrasing in a farmer's own
      language is worse than English.

---

## First week after launch

- [ ] Watch **Redirects & 404s** daily. Every logged 404 is a redirect waiting
      to be written.
- [ ] Watch **Searches that found nothing** in Reports — it is a content brief.
- [ ] Confirm the first real OTP, the first real lead and the first real payment
      each end to end, by hand.
- [ ] Check the moderation queue is being worked inside its SLA.
- [ ] Confirm the nightly backup ran and the dump is the size you expect.

---

## Known gaps at launch

These are deliberate, documented, and should be agreed rather than discovered.

| Gap | Impact | Owner decision needed |
|---|---|---|
| No refund flow | A refund is a manual gateway action plus a note in the operations log | Publish a refund policy operations can honour by hand |
| No lender portal | Lender decisions are keyed in by staff | Acceptable while the lender panel is small |
| Insurance quotes are a callback, not a rate table | An advisor phones back rather than quoting live | Fine at launch; a quote engine is a later phase |
| Distance filtering is by district, not radius | "Within 25 km" is unavailable | Needs a Maps integration |
| WhatsApp and web push declared but not dispatched | Only SMS and in-app notifications send | Wire a provider when the volume justifies it |
| CSP still allows `unsafe-inline` for scripts | Weaker XSS protection than a nonce-based policy | Worth doing; needs every inline block moved or nonced |
| Product content is placeholder | Specifications and prices are sample data | Replace with manufacturer data before launch |
