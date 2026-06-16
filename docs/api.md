# Melaz Motors — Public API

This API powers the public car marketplace website. It is **read-only** and **unauthenticated** — visitors browse cars, filter them, and contact the seller via WhatsApp. There is no customer login.

All admin actions happen inside the Filament panel at `/admin` and are not part of this API.

---

## Table of contents

1. [Base URL](#1-base-url)
2. [Endpoints](#2-endpoints)
3. [Query parameters](#3-query-parameters)
4. [Sorting options](#4-sorting-options)
5. [Pagination](#5-pagination)
6. [Example requests](#6-example-requests)
7. [Example responses](#7-example-responses)
8. [Filter options endpoint](#8-filter-options-endpoint)
9. [Image URLs](#9-image-urls)
10. [WhatsApp integration](#10-whatsapp-integration)
11. [Error response shape](#11-error-response-shape)
12. [Rate limiting](#12-rate-limiting)
13. [CORS](#13-cors)
14. [Versioning & headers](#14-versioning--headers)

---

## 1. Base URL

| Environment | URL |
|---|---|
| Local (Laragon) | `http://melaz-motors.test/api` *(or whatever Laragon assigns)* |
| Staging | `https://staging.melazmotors.com/api` |
| Production | `https://melazmotors.com/api` |

All examples below use `{BASE_URL}` as a placeholder.

Every response is JSON (`Content-Type: application/json; charset=utf-8`).

### Localization

The API is bilingual (Arabic + English). Choose the active locale with **any** of these (first match wins):

| Source | Example | Notes |
|---|---|---|
| Query param | `?lang=ar` | Highest priority — easiest from the frontend. |
| Header | `X-Locale: ar` | |
| Header | `Accept-Language: ar,en;q=0.8` | Standard browser header. |
| Default | — | Falls back to `APP_LOCALE` (`ar`). |

Allowed values: `ar`, `en`. Any other value is silently ignored.

Free-text fields (`title`, `description`, `trim`, `brand`, `model`, `engine_size`) are returned **as the admin typed them** — they are stored once and not translated server-side.

Enumerated fields (`color`, `origin`, `city`, `body_type`, `transmission`, `fuel_type`, `condition`, `drivetrain`, `status`) return both a stable machine value (English key, e.g. `"black"`) and a `*_label` field translated for the active locale:

```json
{ "color": "black", "color_label": "أسود" }
```

The frontend should always **filter / sort by the `value`** and **display the `label`**.

---

## 2. Endpoints

| Method | Path | Purpose |
|---|---|---|
| GET | `/cars` | Paginated list of visible cars with filters + sort |
| GET | `/cars/{slug}` | Full details of a single car (gallery + WhatsApp) |
| GET | `/car-filters` | Filter dropdown data + value ranges + sort labels |

A car is **visible** to the public API when:
- `status = available`, **and**
- `published_at` is set and not in the future.

Sold, hidden, draft (no `published_at`), scheduled-for-future, and soft-deleted cars are never returned.

---

## 3. Query parameters

All parameters are optional. Every value is server-validated; unknown values return `422`.

### `GET /api/cars`

| Param | Type | Notes |
|---|---|---|
| `search` | string (≤100) | Matches `title`, `brand`, `model`, `trim`, `description`. `%` and `_` are treated as literal characters. |
| `brand` | string (≤80) | Exact brand name. |
| `model` | string (≤80) | Exact model name. |
| `body_type` | enum | One of `sedan`, `suv`, `hatchback`, `coupe`, `convertible`, `pickup`, `van`, `wagon`, `crossover`. |
| `color` | enum | One of `black`, `white`, `silver`, `gray`, `blue`, `red`, `green`, `brown`, `beige`, `pearl_white`, `gold`, `other`. |
| `origin` | enum | One of `gcc`, `american`, `european`, `japanese`, `korean`, `canadian`, `other`. |
| `city` | enum | KSA / UAE city keys (e.g. `riyadh`, `jeddah`, `dubai`). See `GET /api/car-filters` for the full live list. |
| `year_min` | int (≥1900) | Inclusive lower bound. Must be ≤ `year_max`. |
| `year_max` | int (≥1900) | Inclusive upper bound. |
| `price_min` | number (≥0) | Inclusive lower bound. Must be ≤ `price_max`. |
| `price_max` | number (≥0) | Inclusive upper bound. |
| `mileage_min` | int (≥0) | Inclusive lower bound. Must be ≤ `mileage_max`. |
| `mileage_max` | int (≥0) | Inclusive upper bound. |
| `transmission` | enum | One of `automatic`, `manual`, `cvt`, `semi_automatic`. |
| `fuel_type` | enum | One of `petrol`, `diesel`, `hybrid`, `electric`, `lpg`. |
| `condition` | enum | One of `new`, `used`. |
| `is_featured` | bool | `1`/`0` or `true`/`false`. |
| `sort` | enum | See [Sorting options](#4-sorting-options). Defaults to `newest`. |
| `per_page` | int (1–60) | Page size. Defaults to **12**, hard-capped at **60**. |
| `page` | int (≥1) | Page number. Defaults to **1**. |

All filters are **AND-combined**. Pass each filter at most once (no `brand[]=...&brand[]=...` arrays).

### `GET /api/cars/{slug}`

No query parameters. The `{slug}` segment must match `^[A-Za-z0-9\-]+$`.

### `GET /api/car-filters`

No query parameters.

---

## 4. Sorting options

| `sort` value | Order |
|---|---|
| `newest` *(default)* | Most recently published first |
| `oldest` | Least recently published first |
| `price_low` | Cheapest first |
| `price_high` | Most expensive first |
| `mileage_low` | Lowest mileage first |
| `mileage_high` | Highest mileage first |
| `year_new` | Newest model year first |
| `year_old` | Oldest model year first |

Any other value returns `422`.

---

## 5. Pagination

`GET /api/cars` always returns paginated results using Laravel's standard pagination envelope:

```json
{
  "data": [ /* car summaries */ ],
  "links": {
    "first": "https://…/api/cars?page=1",
    "last":  "https://…/api/cars?page=9",
    "prev":  null,
    "next":  "https://…/api/cars?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "to": 12,
    "per_page": 12,
    "last_page": 9,
    "total": 104,
    "path": "https://…/api/cars",
    "links": [
      { "url": null,                       "label": "&laquo; Previous", "active": false },
      { "url": "https://…/api/cars?page=1", "label": "1",                "active": true  },
      { "url": "https://…/api/cars?page=2", "label": "2",                "active": false },
      { "url": "https://…/api/cars?page=2", "label": "Next &raquo;",     "active": false }
    ]
  }
}
```

The `links.next` / `links.prev` already include the user's current filters (query-string preserved), so the frontend can navigate by following them directly.

---

## 6. Example requests

```bash
# Browse default page
curl "{BASE_URL}/cars"

# Search by free text
curl "{BASE_URL}/cars?search=land+cruiser"

# Filter: Toyota SUVs with automatic transmission
curl "{BASE_URL}/cars?brand=Toyota&body_type=suv&transmission=automatic"

# Range filters with sort
curl "{BASE_URL}/cars?year_min=2020&year_max=2024&price_max=80000&sort=price_low"

# Featured cars only, 24 per page
curl "{BASE_URL}/cars?is_featured=1&per_page=24"

# Single car
curl "{BASE_URL}/cars/2023-toyota-land-cruiser-7f9k2x"

# Filter UI bootstrap
curl "{BASE_URL}/car-filters"
```

### JavaScript (frontend)

```js
const BASE_URL = 'https://melazmotors.com/api';

const params = new URLSearchParams({
  brand: 'Toyota',
  body_type: 'suv',
  year_min: 2020,
  year_max: 2024,
  sort: 'price_low',
  per_page: 24,
});

const res = await fetch(`${BASE_URL}/cars?${params}`, {
  headers: { Accept: 'application/json' },
});

if (!res.ok) {
  const err = await res.json();
  throw new Error(err.message);
}

const { data, meta, links } = await res.json();
console.log(`Showing ${meta.from}-${meta.to} of ${meta.total}`);
```

---

## 7. Example responses

### `GET /api/cars` (car summary)

```json
{
  "data": [
    {
      "id": 42,
      "slug": "2023-toyota-land-cruiser-7f9k2x",
      "title": "2023 Toyota Land Cruiser",
      "brand": "Toyota",
      "model": "Land Cruiser",
      "trim": "Limited",
      "body_type": "suv",
      "body_type_label": "SUV",
      "year": 2023,
      "color": "pearl_white",
      "color_label": "Pearl White",
      "price": 78500.0,
      "currency": "USD",
      "mileage": 18500,
      "transmission": "automatic",
      "transmission_label": "Automatic",
      "fuel_type": "petrol",
      "fuel_type_label": "Petrol",
      "condition": "used",
      "condition_label": "Used",
      "city": "riyadh",
      "city_label": "Riyadh",
      "is_featured": true,
      "locale": "en",
      "primary_image": {
        "url": "https://melazmotors.com/storage/cars/42/9f3a8b2c.jpg",
        "alt": "Front three-quarter view of a Pearl White Land Cruiser"
      }
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta":  { "current_page": 1, "from": 1, "to": 12, "per_page": 12, "last_page": 9, "total": 104, "path": "...", "links": [ ... ] }
}
```

Fields **omitted on purpose** from the list (smaller payload, faster cards): `description`, `whatsapp_number`, `whatsapp_link`, `engine_size`, `drivetrain`, `origin`, `published_at`, `status`, `images`. Use the detail endpoint for those.

Switching language is as simple as adding `?lang=ar`:

```json
{
  "data": [{
    "id": 42, "title": "تويوتا لاند كروزر 2023",
    "color": "pearl_white", "color_label": "أبيض لؤلؤي",
    "city": "riyadh", "city_label": "الرياض",
    "locale": "ar"
  }]
}
```

### `GET /api/cars/{slug}` (full detail)

```json
{
  "data": {
    "id": 42,
    "title": "2023 Toyota Land Cruiser",
    "slug": "2023-toyota-land-cruiser-7f9k2x",

    "brand": "Toyota",
    "model": "Land Cruiser",
    "trim": "Limited",

    "body_type": "suv",
    "body_type_label": "SUV",

    "year": 2023,
    "color": "pearl_white",
    "color_label": "Pearl White",

    "price": 78500.0,
    "currency": "USD",

    "origin": "gcc",
    "origin_label": "GCC",
    "mileage": 18500,

    "transmission": "automatic",
    "transmission_label": "Automatic",

    "fuel_type": "petrol",
    "fuel_type_label": "Petrol",

    "engine_size": "4.0L V6",

    "drivetrain": "four_wd",
    "drivetrain_label": "4WD (Four-Wheel Drive)",

    "condition": "used",
    "condition_label": "Used",

    "city": "riyadh",
    "city_label": "Riyadh",
    "description": "Full service history. Single owner. Bluetooth, leather seats, …",

    "status": "available",
    "is_featured": true,

    "whatsapp_number": "+966512345678",
    "whatsapp_link": "https://wa.me/966512345678?text=Hi%2C%20I%27m%20interested%20in%20your%202023%20Toyota%20Land%20Cruiser.",

    "published_at": "2026-04-12T10:23:00+00:00",

    "images": [
      {
        "id": 311,
        "url": "https://melazmotors.com/storage/cars/42/9f3a8b2c.jpg",
        "alt_text": "Front three-quarter view",
        "sort_order": 0,
        "is_primary": true
      },
      {
        "id": 312,
        "url": "https://melazmotors.com/storage/cars/42/c81d99af.jpg",
        "alt_text": "Interior dashboard",
        "sort_order": 1,
        "is_primary": false
      }
    ]
  }
}
```

`images` are returned in ascending `sort_order` (with the primary image always first because admins promote it to `sort_order = 0` when reordering).

---

## 8. Filter options endpoint

`GET /api/car-filters` returns everything the frontend needs to build its filter UI on first load. Hit it once on page mount; cache the result client-side for the session.

```json
{
  "data": {
    "brands": ["Audi", "BMW", "Toyota", "..."],

    "models_by_brand": {
      "Toyota": ["Camry", "Corolla", "Land Cruiser", "RAV4"],
      "BMW":    ["3 Series", "5 Series", "X5"]
    },

    "body_types":   [{ "value": "sedan",    "label": "Sedan" }, { "value": "suv", "label": "SUV" }],
    "transmissions":[{ "value": "automatic","label": "Automatic" }],
    "fuel_types":   [{ "value": "petrol",   "label": "Petrol" }],
    "conditions":   [{ "value": "new",      "label": "New" }, { "value": "used", "label": "Used" }],
    "drivetrains":  [{ "value": "fwd",      "label": "FWD (Front-Wheel Drive)" }],

    "colors":  [{ "value": "black", "label": "Black" }, { "value": "white", "label": "White" }],
    "origins": [{ "value": "gcc", "label": "GCC" }, { "value": "japanese", "label": "Japanese" }],
    "cities":  [{ "value": "riyadh", "label": "Riyadh" }, { "value": "dubai", "label": "Dubai" }],

    "year_range":    { "min": 2015, "max": 2025 },
    "price_range":   { "min": 8000, "max": 180000 },
    "mileage_range": { "min": 0,    "max": 250000 },

    "sort_options": [
      { "value": "newest",     "label": "Newest" },
      { "value": "oldest",     "label": "Oldest" },
      { "value": "price_low",  "label": "Price: Low to High" },
      { "value": "price_high", "label": "Price: High to Low" },
      { "value": "mileage_low","label": "Mileage: Low to High" },
      { "value": "mileage_high","label": "Mileage: High to Low" },
      { "value": "year_new",   "label": "Year: Newest First" },
      { "value": "year_old",   "label": "Year: Oldest First" }
    ]
  }
}
```

### How the frontend should consume it

- **Brand `<select>`** ← `data.brands`.
- **Model `<select>`** ← `data.models_by_brand[selectedBrand]` (cascading; updates whenever brand changes).
- **Body type, transmission, fuel type, condition, drivetrain, color, origin, city** → use the `{value,label}` pairs to render selects/chips. Submit the `value` field as the query parameter; display the `label` (already translated to the active locale).
- **Year, price, mileage** → render a range slider with min/max from `*_range`. Submit `*_min` / `*_max` query parameters.
- **Sort `<select>`** ← `data.sort_options`. Submit `sort=<value>`.

The endpoint is **cached for 10 minutes server-side** (and `Cache-Control: public, max-age=600, s-maxage=1200` lets browsers and CDNs cache too). It's automatically invalidated whenever an admin saves or deletes a car, so dropdowns are never stale for long.

---

## 9. Image URLs

All `url` fields in API responses are **absolute** and ready for `<img src>` without any transformation:

- Local public disk (default): `https://yourdomain.com/storage/cars/{car_id}/{file}` (via the `php artisan storage:link` symlink).
- S3 / R2 / Spaces (when `CARS_IMAGES_DISK=s3` in the backend `.env`): `https://bucket.endpoint/cars/{car_id}/{file}`.

The frontend never needs to know which storage backend is in use.

Uploads pass through Filament's client-side resize (1600 × 1067 max, aspect-ratio preserved, JPEG/PNG/WebP only), so listing card grids stay fast on slow connections.

---

## 10. WhatsApp integration

Each car detail response includes:

- `whatsapp_number` — raw E.164-style number as the admin entered it (e.g. `+966512345678`).
- `whatsapp_link` — ready-made `https://wa.me/...?text=...` URL the frontend can drop straight into an `<a href>`.

The default prefilled message is `Hi, I'm interested in your {title}.` If you want a custom message, ignore `whatsapp_link` and build your own:

```js
const phone = car.whatsapp_number.replace(/\D+/g, '');
const text  = encodeURIComponent('Hello! Is this car still available?');
const href  = `https://wa.me/${phone}?text=${text}`;
```

`whatsapp_number` and `whatsapp_link` are **not** returned on the list endpoint, only on detail — keep that in mind if your card UI needs a quick CTA (you'll have to fetch the detail or use a slug-only link to the detail page).

---

## 11. Error response shape

Every error response is a JSON object with at least a `message` field.

| Status | Shape | When |
|---|---|---|
| **422 Unprocessable Entity** | `{ "message": "The given data was invalid.", "errors": { "field": ["…"] } }` | A query parameter failed validation (bad enum, range outside limits, `min > max`, etc.) |
| **404 Not Found** | `{ "message": "Resource not found." }` | Unknown slug, or any unmatched route under `/api`. |
| **429 Too Many Requests** | `{ "message": "Too many requests. Please try again shortly." }` (with `Retry-After` header) | Rate limit exceeded for that IP. |
| **500 Server Error** | `{ "message": "Server Error" }` | Unhandled exception (production hides stack traces; logs hold the detail). |

Example 422:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "year_min": ["year_min cannot be greater than year_max."],
    "sort":     ["The selected sort is invalid."]
  }
}
```

Example 429:

```json
{
  "message": "Too many requests. Please try again shortly."
}
```
*(Headers: `Retry-After: 38`)*

---

## 12. Rate limiting

| Endpoint | Limit |
|---|---|
| `GET /api/cars`, `GET /api/cars/{slug}` | **60 req / minute / IP** |
| `GET /api/car-filters` | **120 req / minute / IP** *(also CDN/browser cached for 10 min)* |

Exceeding the limit returns `429` with a `Retry-After` header indicating seconds to wait.

---

## 13. CORS

CORS is **env-driven** on the backend. The frontend's origin must be added to `CORS_ALLOWED_ORIGINS` in the backend `.env`:

```env
# Local development — Vite default + a couple of common ports
CORS_ALLOWED_ORIGINS="http://localhost:5173,http://127.0.0.1:5173,http://localhost:3000"

# Production
CORS_ALLOWED_ORIGINS="https://melazmotors.com,https://www.melazmotors.com"
```

| Property | Value |
|---|---|
| `allowed_methods` | `GET, HEAD, OPTIONS` only (no public writes) |
| `allowed_headers` | `Accept`, `Content-Type`, `Authorization`, `X-Requested-With`, `Origin` |
| `supports_credentials` | `false` (the API is fully public; no cookies/auth tokens) |
| `max_age` | 86400 (browsers cache the preflight for 24 h) |

If `CORS_ALLOWED_ORIGINS` is left empty the backend falls back to `['*']` — convenient for early local development, **never** acceptable in production.

---

## 14. Versioning & headers

- **No URL versioning yet.** The current public surface is small enough that breaking changes are unlikely. If we ever need one, future endpoints will be exposed under `/api/v2/...` while `/api/...` stays compatible.
- **Compression**: enable gzip / brotli at the web server for `application/json` — typically 70–80% size reduction on these responses.
- **Caching headers**:
  - `/api/car-filters` → `Cache-Control: public, max-age=600, s-maxage=1200`
  - All other endpoints → no `Cache-Control` (let the client revalidate).

The frontend doesn't need to send any custom headers. `Accept: application/json` is recommended (Laravel auto-detects it for error responses anyway).

---

## Quick reference: copy-paste cheatsheet

```js
// 1. Bootstrap filter dropdowns (once per page mount)
const filters = await fetch(`${BASE_URL}/car-filters`).then(r => r.json()).then(j => j.data);

// 2. Browse cars
const params = new URLSearchParams({
  brand: 'Toyota', body_type: 'suv',
  year_min: 2020, price_max: 100000,
  sort: 'price_low', per_page: 24, page: 1,
});
const list = await fetch(`${BASE_URL}/cars?${params}`).then(r => r.json());
// list.data           => Car[]
// list.meta.total     => total matching cars
// list.links.next     => URL for next page (filters preserved) or null

// 3. Open one car
const car = await fetch(`${BASE_URL}/cars/${slug}`).then(r => r.json()).then(j => j.data);

// 4. WhatsApp CTA
window.open(car.whatsapp_link, '_blank');
```
