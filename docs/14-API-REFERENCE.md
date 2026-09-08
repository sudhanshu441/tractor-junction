# REST API v1

For the Krishi Junction mobile app. Versioned in the path so a shipped app keeps
working when v2 arrives — an app on a farmer's phone may not be updated for years.

**Base URL:** `https://krishijunction.com/api/v1`

## Envelope

Every response uses the same shape as the website's AJAX layer, so one client
helper handles both:

```json
{ "status": "ok", "message": "…", "data": {}, "meta": {} }
```

`status` is `ok` or `error`. On a validation failure the HTTP status is 422 and
`errors` carries Laravel's field map.

## Authentication

Mobile number and OTP — the same flow as the website. There is deliberately no
password endpoint: adding a second credential only adds a second thing to steal.

```http
POST /auth/otp        { "mobile": "9812345678" }
POST /auth/verify     { "mobile": "9812345678", "otp": "123456", "device_name": "Redmi 12" }
```

`verify` returns a bearer token valid for 90 days:

```json
{ "status": "ok", "data": { "token": "12|abc…", "user": { "id": 4, "name": "…" } } }
```

Send it as `Authorization: Bearer <token>`.

`device_name` is required and one token is kept per device name — signing in
again on the same handset replaces the old token rather than leaving it valid
forever.

## Rate limits

| Group | Limit |
|---|---|
| `/auth/*` | 10 per minute per IP |
| Public reads | 60 per minute per IP |
| Authenticated | 60 per minute per token |
| Enquiries | 20 per hour per token |

A limit returns 429 with `Retry-After`.

---

## Public endpoints

No token needed. A farmer should be able to compare prices before creating anything.

| Method | Path | Notes |
|---|---|---|
| GET | `/products` | `type`, `brand_id`, `hp_min`, `hp_max`, `price_min`, `price_max`, `page`, `per_page` (max 50) |
| GET | `/products/{brand}/{model}` | Full specifications |
| GET | `/brands` | |
| GET | `/categories` | `type` to narrow |
| GET | `/filters` | Facet values with counts, for building the filter UI |
| GET | `/listings` | Used machines. Same filters plus `state_id`, `district_id`, `year_min`, `verified` |
| GET | `/listings/{slug}` | |
| GET | `/dealers` | `state_id`, `district_id`, `brand_id` |
| POST | `/emi` | `price`, `down_payment`, `interest_rate`, `tenure_months`, `frequency` |

### Contact numbers are never in a list response

A seller's number is not in `/listings` or `/listings/{slug}`, and a dealer's
number is masked in `/dealers`. That is the same rule the website enforces: a
number is revealed only after the person asking has verified their own.

---

## Authenticated endpoints

| Method | Path | Notes |
|---|---|---|
| GET | `/me` | The signed-in profile |
| POST | `/logout` | Revokes the current token only |
| GET | `/account/listings` | The caller's own used listings |
| GET | `/account/enquiries` | Enquiries the caller has made |
| POST | `/account/enquiries` | `type`, `about_type`, `about_id`, `message` |
| GET | `/account/wishlist` | |
| POST | `/account/wishlist` | `type` (`product`\|`used_listing`\|`dealer`), `id` |
| DELETE | `/account/wishlist/{id}` | Only the owner's own row |

An enquiry from the app needs no second OTP — the token already proves the number.

---

## Errors

| Status | Meaning |
|---|---|
| 401 | Missing, expired or revoked token |
| 403 | Authenticated, but not the owner of that resource |
| 404 | No such record, or one that is not public |
| 422 | Validation failed — read `errors` |
| 429 | Rate limited — read `Retry-After` |

---

## Example

```bash
curl -s https://krishijunction.com/api/v1/products?type=tractor&hp_min=40&per_page=5

curl -s -X POST https://krishijunction.com/api/v1/emi \
  -H "Content-Type: application/json" \
  -d '{"price":700000,"down_payment":150000,"interest_rate":12,"tenure_months":60}'

curl -s https://krishijunction.com/api/v1/me \
  -H "Authorization: Bearer 12|abc…"
```

## Not in v1

Listing creation, photo upload, loan applications and payments are web-only for
now. Each needs a document-upload and consent flow that has to be designed for a
small screen rather than ported from the website.
