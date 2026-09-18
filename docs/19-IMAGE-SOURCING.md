# Product photographs

## The position, plainly

The site ships with **no photographs of the specific tractor models it lists**,
and that is deliberate rather than unfinished.

Photographs of a Mahindra 575 DI or a Swaraj 744 FE are the manufacturer's
copyright. Downloading them from a brand website or a competitor's listing and
publishing them on a commercial marketplace is copyright infringement, and the
exposure lands on Krishi Junction, not on whoever copied the file. Indian
manufacturers do enforce this.

## The placeholder

Wherever a model has no photograph, the catalogue shows one supplied stand-in
photograph — `public/assets/brand/machines/tractor-*.webp`, with a `.jpg`
fallback. It reads as "no photo yet" rather than as a broken page.

Two things about it that operations needs to know, because both are live risks
rather than theory:

1. **It shows a branded machine.** The stand-in is a red Massey Ferguson 241 DI.
   It therefore appears on Mahindra, Swaraj and John Deere models too, until
   those models get their own photographs. A buyer who does not read the caption
   can reasonably think the picture is the machine. That is a commercial reason
   to finish the catalogue, not only an aesthetic one.
2. **The client supplied it and is asserting the rights to it.** It was not
   sourced by the build. Confirm in writing that Krishi Junction may publish it
   commercially before launch; everything in the table below applies to this
   file exactly as it applies to any other manufacturer photograph.

Two guards keep the stand-in from becoming a claim about the machine:

- The alt text says *"No photograph uploaded yet for &lt;model&gt;"* — a screen
  reader and an image crawler both read it as absent, not as this model's photo.
- **It is never emitted in JSON-LD.** `JsonLd` leaves `image` null when a model
  has no real photograph, so Google is never told that a Massey Ferguson picture
  depicts a Swaraj.

The admin moderation queue deliberately does **not** use it. A moderator must
see that a listing arrived with no photos; filling the gap with a stand-in would
hide the thing they are reviewing for.

## Where real photographs legitimately come from

| Source | How to get it | Notes |
|---|---|---|
| **Manufacturer dealer pack** | Ask the brand's regional marketing contact for the dealer media kit | The normal route. Usually free to an authorised listing partner, with a written usage permission. Get that permission in writing. |
| **Your own photography** | Shoot at dealer showrooms | Costs a day per showroom and you own the result outright. Best long-term answer. |
| **Dealer-submitted** | Dealers upload photos of their own stock | Already supported: Dealer panel → Inventory. Make them confirm they hold the rights. |
| **Seller-submitted** | Used listings carry the seller's own photos | Already supported and already how the used marketplace works. |
| **Stock libraries** | Shutterstock, Adobe Stock | Generic farm machinery only. A stock photo is rarely the exact model, and showing the wrong machine beside a price is worse than showing none. |

**Do not** use Google Images results, manufacturer brochures scanned from PDFs,
or photographs lifted from other marketplaces.

## Loading them once you have them

For one model, use the admin panel: **Products → edit → Images**.

For a whole library, use the bulk command. Name each file after the model and it
matches automatically:

```bash
# See what would match, change nothing
php artisan catalog:import-images /path/to/photos --dry-run

# Attach them
php artisan catalog:import-images /path/to/photos

# Replace what a model already has rather than adding to it
php artisan catalog:import-images /path/to/photos --replace
```

### Naming

Three shapes work, checked most specific first:

```
mahindra-575-di-xp-plus.jpg      brand slug + model slug
mahindra/475-di-xp-plus.jpg      brand folder + model slug
744-fe.jpg                       model slug alone, if only one brand uses it
575-di-xp-plus-2.jpg             a second photo for the same model
```

Anything that matches nothing is listed at the end of the run rather than
silently skipped, so a typo is visible.

Uploads are resized and converted to WebP automatically — original, card and
thumbnail. Feed it the largest files you have; do not shrink them first.

## Before launch

`docs/15-LAUNCH-CHECKLIST.md` lists the catalogue content as a blocking item.
Photographs are part of that: a marketplace where every machine shows the same
borrowed stand-in converts badly, and on a rival's model it invites a complaint.
