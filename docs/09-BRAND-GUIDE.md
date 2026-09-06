# Krishi Junction — Brand & UI Guide

White surfaces, green brand. Green is the only brand colour; everything else is either
neutral or a state signal.

---

## 1. Logo

The mark is a **tractor wheel with a sprout at the hub** — machinery and farming in one
shape, and a "junction" where the two meet. It stays legible down to 16 px because the
tread ring drops away before the rim and sprout do.

| File | Use |
|---|---|
| `assets/brand/logo-mark.svg` | App icon / avatar — white art on a green tile |
| `assets/brand/logo-mark-green.svg` | Mark alone on white or light surfaces |
| `assets/brand/logo-horizontal.svg` | Primary lockup — header, letterhead, invoices |
| `assets/brand/logo-horizontal-white.svg` | Lockup on green or photographic backgrounds |
| `assets/brand/favicon.svg` | Browser tab, 32 px and below (simplified, no tread ring) |

**Rules**
- Clear space on all sides = the height of the sprout (¼ of the mark).
- Minimum sizes: mark 20 px, horizontal lockup 120 px wide.
- Wordmark is two-tone: *Krishi* in `--green-900`, *Junction* in `--green-700`.
- Never: stretch it, rotate it, add a drop shadow, recolour it outside the palette, or
  place the green-on-white mark on a green ground (use the white version).
- PNG exports for email and app stores: 512, 192, 180, 48, 32, 16 px, generated from the SVG.

## 2. Colour

Green carries the brand; white carries the content.

| Token | Hex | Where |
|---|---|---|
| `--green-900` | `#0B3D20` | Wordmark, headings on tinted panels, secondary CTA |
| `--green-800` | `#10552B` | Hover state of the primary button |
| `--green-700` | `#15703A` | **Primary** — buttons, links, active nav, logo |
| `--green-600` | `#1B8C48` | Charts, secondary emphasis |
| `--green-400` | `#4FB273` | Second chart series, illustrations |
| `--green-100` | `#E4F2E8` | Active chips, selected rows, hero wash |
| `--green-50` | `#F1F8F3` | Highlighted table rows, subtle fills |
| `--surface` | `#FFFFFF` | Cards, tables, modals, header |
| `--ground` | `#F4F8F5` | Page background (a whisper of green, not grey) |
| `--sunk` | `#E9F1EB` | Inset fields, image placeholders |
| `--ink` | `#0E1B13` | Body text |
| `--ink-2` | `#415447` | Secondary text |
| `--ink-3` | `#6D8175` | Labels, captions, placeholders |
| `--line` | `#DCE8E0` | Borders, dividers |

**State colours are not brand colours.** They exist so a farmer can tell "approved" from
"pending" from "rejected", and appear only in badges, alerts and validation:

| Token | Hex | Meaning |
|---|---|---|
| `--danger` `#B42318` / soft `#FDECEA` | Rejected, blocked, overdue, destructive |
| `--warn` `#B54708` / soft `#FCF0E4` | Pending review, SLA running out, docs missing |
| `--info` `#175CD3` / soft `#E8F0FD` | Assigned, informational notes |

Success reuses `--green-700` / `--green-100`, so "good" always reads as the brand colour.

**Contrast:** `--green-700` on white is 5.1:1 (AA for text and UI). White on `--green-700`
is the same. `--ink` on white is 16:1. Never put `--green-400` behind white text.

## 3. Type

| Role | Family | Use |
|---|---|---|
| Display | **Archivo** 600/700 | Headings, prices, KPI numbers, wordmark |
| Body / UI | **IBM Plex Sans** 400/500/600 | Everything readable |
| Data | **IBM Plex Mono** 400/500 | Specs, HP, hours, reference numbers, prices in tables |
| Hindi | **Noto Sans Devanagari** | Mirrors the body weights |

Scale: 30 / 22 / 19 / 15 / 14 / 12.5 / 11.5 / 10.5 px. Uppercase labels get `.07em`
letter-spacing. Every column of digits gets `font-variant-numeric: tabular-nums`.

## 4. Components (Bootstrap 5 mapping)

The UI is Bootstrap 5 with the brand applied through Sass variable overrides, not by
fighting Bootstrap with `!important`:

```scss
$primary:        #15703A;
$secondary:      #0B3D20;
$success:        #15703A;
$danger:         #B42318;
$warning:        #B54708;
$info:           #175CD3;
$body-bg:        #F4F8F5;
$body-color:     #0E1B13;
$border-color:   #DCE8E0;
$border-radius:  .625rem;
$font-family-sans-serif: "IBM Plex Sans", system-ui, sans-serif;
$headings-font-family:   "Archivo", system-ui, sans-serif;
```

| Prototype element | Bootstrap component |
|---|---|
| Product / listing card | `.card` with a 4:3 `.ratio` image |
| Filter chip | `.btn.btn-sm.btn-outline-success`, active = `.btn-success` |
| Status badge | `.badge` with brand/state utility classes |
| Filter sidebar (mobile) | `.offcanvas` |
| Compare table | `.table` in `.table-responsive`, sticky first column |
| Sell / loan wizard | Custom stepper + `.tab-pane`, AJAX-saved per step |
| Admin list screens | `.table` + DataTables (server-side) |
| Toasts, modals, tooltips | Bootstrap JS plugins |

House rules on top of Bootstrap: one radius (10 px), one shadow, borders instead of
shadows for separation, and no gradients except the single hero wash.

## 5. Imagery

Machinery photography on white or a very light green ground. No stock photos of farmers
with saturated sunset filters. Placeholder art is the inline SVG machinery outline used in
the prototype. Full asset plan in [08-CONTENT-MEDIA-PLAN.md](08-CONTENT-MEDIA-PLAN.md).
