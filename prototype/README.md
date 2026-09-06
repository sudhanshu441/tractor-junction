# Krishi Junction — clickable prototype

A single self-contained HTML file. No build step, no dependencies (Google Fonts only).

```bash
python3 -m http.server 8080     # from this folder, then open http://localhost:8080
# or just: open index.html
```

**Hosted version:** https://claude.ai/code/artifact/f61c8ee1-8f7d-40bc-a29d-07af56ecb979

## Screens

| # | Screen | Route |
|---|---|---|
| 01 | Home | `/` |
| 02 | Tractor listing + filters | `/tractors/hp/40-50-hp` |
| 03 | Model detail | `/tractors/mahindra/575-di-xp-plus` |
| 04 | Compare (3 models) | `/compare/{a}-vs-{b}-vs-{c}` |
| 05 | Used marketplace | `/used/tractors/uttar-pradesh/sitapur` |
| 06 | Used listing detail + inspection | `/used/listing/{slug}-{ref}` |
| 07 | Sell wizard (mobile) | `/sell` |
| 08 | Dealer locator | `/dealers/uttar-pradesh/sitapur` |
| 09 | EMI calculator | `/loan/emi-calculator/{brand}/{model}` |
| 10 | Loan application | `/loan/apply` |
| 11 | Customer dashboard | `/account/dashboard` |
| 12 | Dealer panel — leads inbox | `/dealer/leads` |
| 13 | Admin dashboard | `/admin/dashboard` |
| 14 | Listing moderation queue | `/admin/used-listings/moderation` |
| 15 | Leads board | `/admin/leads` |

Deep-link to any screen with its hash, e.g. `index.html#moderation`.

## What this is and isn't

- **Is:** layout, hierarchy, states, real routes, the white/green brand theme and logo,
  and realistic Indian agri-machinery data (HP, engine hours, ex-showroom vs on-road,
  ₹ lakh pricing, reference numbers).
- **Isn't:** final visual design, real photography, or working logic. All figures are
  illustrative samples. No third-party imagery is used — the machinery graphics are
  inline SVG drawn for this prototype.

Colours, type and the Bootstrap 5 component mapping are specified in
[../docs/09-BRAND-GUIDE.md](../docs/09-BRAND-GUIDE.md).
