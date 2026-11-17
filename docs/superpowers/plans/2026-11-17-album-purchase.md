# Album Purchase Flow Implementation Plan

> **For agentic workers:** Read only the assigned task section, the **Interface Contracts** it references, and the files under **Context to load**. Do not scan the whole repository. Update the task checkboxes and the **Progress Tracker** before ending the session.

**Goal:** Add a guest album-purchase flow to the NewJeans fan site: session cart, worldwide city-based shipping estimate from Seoul, persisted order snapshots, simulated payment, and a login-protected admin Orders area.

**Design source:** `docs/superpowers/specs/2026-11-17-album-purchase-design.md`

**Status:** Plan ready. No application code has been changed yet.

**Reference implementation:** `docs/superpowers/plans/2026-11-09-php-backend-content.md` (project conventions, not scope).

---

## How To Use This Plan

1. Start a new session for one task only.
2. Read this task's section, the **Progress Tracker**, the **Interface Contracts** section, and only the files listed in **Context to load**.
3. Do not open unrelated admin modules or re-read the whole codebase.
4. Implement the smallest change that satisfies the task. Keep the existing project style.
5. Run the task's **Verify** commands and the relevant manual checks.
6. Tick the task checkboxes, set the task status to `done`, and add a one-line handoff note.
7. Do not start a dependent task until its predecessor's **Acceptance** passes.
8. Do not commit unless the user explicitly asks.

**New-session prompt template:**

```text
Kerjakan Task T5 dari docs/superpowers/plans/2026-11-17-album-purchase.md.
Baca hanya bagian T5, Progress Tracker, Interface Contracts, dan file di Context to load.
Jangan scan seluruh repo. Implementasikan, jalankan verifikasi, lalu update ceklis dan handoff note.
```

**Context budget rules:**

- The plan is the source of truth for interfaces and decisions. Prefer it over re-deriving behavior from old code.
- Read only files that the task will modify or call directly.
- If a shared interface changes, update **Interface Contracts** in this plan and mention it in the handoff note.
- Keep handoff notes to one or two lines. Do not paste diffs or long logs.

---

## Locked Decisions

| Topic | Decision |
|---|---|
| Checkout type | Guest checkout; no customer registration/login |
| Cart | PHP session only; no `carts` or `cart_items` tables |
| Currency | USD |
| Payment | Simulated `mock` gateway through a `PaymentGateway` interface |
| Shipping origin | Seoul, South Korea (`37.5665, 126.9780`) |
| Shipping formula | `base_fee_usd + distance_km * fee_per_km_usd`, rounded to 2 decimals |
| Shipping rates | `SHIPPING_BASE_FEE_USD=3.00`, `SHIPPING_FEE_PER_KM_USD=0.0015`; blank/missing disables checkout |
| Shipping scope | One shipping charge per order, not per item |
| Dataset | GeoNames `cities15000` + national capitals supplement; CC BY 4.0; ISO2 country codes + GeoNames city IDs |
| Order statuses | `PENDING_PAYMENT`, `PAID`, `CANCELLED` |
| Payment failure | Stays `PENDING_PAYMENT`, retryable |
| Stock unavailable at payment | Order becomes `CANCELLED`; available items are restored to cart |
| Seed data | All 7 albums are for sale with demo price/stock |
| Discography CTA | English; `Buy Album` / `Out of stock` / `Not for sale`; YouTube removed from cards, kept on album detail |
| Cart entry point | Header link with total item count |
| Admin orders | Read-only list/detail; pagination 20 per page |
| Guest URLs | Non-guessable 64-char hex token; separate human-readable invoice number |
| Invoice format | `NJ-YYYYMMDD-XXXXXXXX` (first 8 chars of token, uppercase) |
| Cart quantity cap | 1–99 per album, also capped by stock at checkout |
| Buy button on album detail | Not in scope; card CTA only |
| Tax/duties | Not in scope |
| Real payments/carriers | Not in scope |

---

## Global Constraints

- Use prepared SQL statements for every query. Never interpolate user input.
- Escape every database value in templates with `e()`.
- Every state-changing POST must validate CSRF with `Csrf::validate()`.
- Use `Response::redirect(..., 303)` after successful POST actions.
- Use server-side validation and re-read album sale/stock/price from the database. Never trust client-submitted prices.
- Wrap order creation and payment state changes in database transactions.
- Use `utf8mb4`, InnoDB, and `DECIMAL` for money.
- Do not add persistent cart tables.
- Do not add customer accounts, email notifications, coupons, payment proofs, or real carrier integrations.
- Keep the existing public design language: white paper, blue `#005bab`, dot texture, bold italic headings, rounded cards.
- Keep admin pages on AdminLTE; do not load public `theme.css` in admin.
- Run `php -l` on every changed PHP file.
- Run `node --check` on every changed JS file.

---

## Progress Tracker

Status values: `pending`, `in_progress`, `blocked`, `done`.

| Task | Title | Depends on | Size | Status | Handoff note |
|---|---|---|---|---|---|
| T1 | Config, env, shared helpers | — | S | `done` | `config/shipping.php` (currency/origin/rates/dataset_path), `money()`, `Response::json()`; rates absent in local `.env` so checkout stays disabled. No test bootstrap per user decision. |
| T2 | GeoNames shipping dataset | T1 | M | `done` | GeoNames 2026-11-16: 244 countries / 34,224 cities in 6.0 MB JSON; PW + TK supplemented from per-country dumps; AQ/BV/HM/IO/UM omitted (uninhabited); raw downloads gitignored. |
| T3 | Destination catalog + calculator | T1, T2 | M | `done` | Constructor injection for dataset path/rates/origin; 79 checks pass; distances Tokyo 1,152.62 / Jakarta 5,296.88 / LA 9,585.81 / London 8,857.18 km (±10 km). Local `.env` now has `$3.00 + $0.0015/km` so checkout is enabled. |
| T4 | Orders schema + seed updates | T1 | M | `done` | Disposable DB `newjeans_purchase_test`: `orders`/`order_items` created empty; 7 albums seeded for sale with the specified price/stock; existing content counts unchanged. |
| T5 | Cart service | T1 | S | `done` | Session key `cart`, cap 99; `items()` normalizes + sorts and treats corruption as empty; 31 checks pass. |
| T6 | Cart routes, controller, page | T4, T5 | M | `done` | `GET /cart` + POST add/update/remove; `findMany()`; `purchase.css`; 36 checks + HTTP smoke pass. Header count remains T7. |
| T7 | Discography CTA + header cart link | T4, T6 | M | `done` | `Buy Album` / `Out of stock` / `Not for sale`; `.rr-buy-form` display:contents, `.rr-price`, `.rr-availability--out/off`; header `.nav-cart-link` + `.nav-cart-count` (total qty, active on `/cart`). 7 buy buttons, add/remove/unavailable checks, search/filter/sort, Playwright 1280/768/390 clean. |
| T8 | Shipping city/estimate endpoints | T1, T3 | S | `done` | `GET /shipping/cities?country=KR` → 200 `{country, cities:[{id,name,region}]}`; `GET /shipping/estimate?country=ID&city=1642911` → 200 `{distance_km:5296.88, shipping_usd:10.95, currency:"USD"}`. `422 {"error"}` for bad/unknown country or city (city must belong to country); `503 {"error"}` when rates unconfigured; `Cache-Control: no-store`. Constructor injection `(DestinationCatalog, ShippingCalculator)` for T11. |
| T9 | Order repository + order creation service | T3, T4 | M | `done` | `createOrder()` payloads: recipient `{recipient_name, recipient_phone, shipping_address}`; destination `{country_code, country, city_id, city, region, latitude, longitude}`; quote `{distance_km, shipping_usd, currency}`. Returns the full order row plus `items`. Safe `RuntimeException` messages; `findAdmin`/`paginate` omit `access_token`. Tested on `newjeans_purchase_test`: 61 checks pass, DB left clean. |
| T10 | Checkout controller + page + validation | T3, T5, T6, T8, T9 | L | `done` | `CheckoutController` (`show`/`store`) + `templates/pages/checkout.php`; session keys `checkout_nonce` (16-byte hex, single-use, regenerated on error) and `last_order_token` (replayed nonce → payment redirect, else 419). Shipping is always recomputed server-side from the catalog city. Messages: required name/phone/address, `Choose a destination country.`, `Choose a destination city.`, `Choose a supported destination country.`, `Choose a city in the selected country.`, `Shipping estimates are unavailable for the selected destination.`; 64 HTTP + 11 direct checks pass on `newjeans_purchase_test`, DB left clean. Payment page itself is T13. |
| T11 | Checkout JS: city dropdown + estimate | T8, T10 | M | `done` | `public/assets/js/checkout.js` with `AbortController` + sequence guards, `data-cities-url`/`data-estimate-url`/`data-subtotal`/`data-selected-country`/`data-selected-city`; handles 422/503 and preserves selected city; `node --check` passes. |
| T12 | Payment gateway + payment service | T4, T5, T9 | L | `done` | `PaymentGateway`, `PaymentResult`, `MockPaymentGateway` (`mock`); `CheckoutService::pay()` returns `paid`/`pending`/`cancelled`/`unavailable` (with `restoreCart()` on stock failure, cancel reason `One or more albums are out of stock or no longer available.`); `tests/payment_service_test.php`: 25 checks pass on `newjeans_purchase_test`. |
| T13 | Payment + order status pages | T10, T12 | M | `done` | `PaymentController` (`show`/`pay`/`status`), `templates/pages/payment.php`, `templates/pages/order-status.php`, `purchase.css`; regex `^[a-f0-9]{64}$` token validation, `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex`, `Cache-Control: no-store`. |
| T14 | Admin orders list/detail | T4, T9 | M | `done` | `Admin\OrderController` (`index`/`show`), `templates/admin/orders/index.php` (20/page pagination, status badges) & `show.php` (snapshots, no `access_token`), Orders nav item in `admin.php`. |
| T15 | Dashboard + docs + spec sync | T11, T13, T14 | S | `done` | `DashboardController` adds Orders & Pending Orders cards; `README.md` and `docs/superpowers/specs/2026-11-17-album-purchase-design.md` synced with 11 tables, routes, GeoNames CC BY 4.0, and locked decisions. |
| T16 | Final end-to-end QA | T1–T15 | M | `done` | 105 PHP files `php -l` 0 failed; 8 JS files `node --check` 0 failed; all 4 test suites pass on `newjeans_purchase_test` (79+31+61+25 = 196 checks). |

**Parallelizable groups after T1:** T2, T4, and T5 are independent. T8 can start after T3. T14 can start after T9. Do not run T9 and T12 together because both modify `CheckoutService`; do not run T10 and T11 together because both modify the checkout template.

---

## Interface Contracts

These are the expected public shapes. Implementations may add private helpers, but avoid changing these signatures without updating this section.

### `App\Support\Cart`

```php
final class Cart
{
    /** @return array<int, int> albumId => quantity */
    public static function items(): array;

    public static function add(int $albumId, int $quantity = 1): void;

    public static function setQuantity(int $albumId, int $quantity): void;

    public static function remove(int $albumId): void;

    public static function clear(): void;

    public static function count(): int;

    public static function isEmpty(): bool;
}
```

- Session key: `$_SESSION['cart']`.
- Normalize IDs and quantities to integers.
- Maximum quantity per album: `99`.
- `setQuantity(..., 0)` removes the item.
- No database access.

### `App\Shipping\DestinationCatalog`

```php
final class DestinationCatalog
{
    public function __construct(?string $datasetPath = null);

    /** @return list<array{code: string, name: string}> */
    public function countries(): array;

    /** @return list<array{id: int, name: string, region: ?string, lat: float, lng: float, population: int}> */
    public function cities(string $countryCode): array;

    public function isCountry(string $countryCode): bool;

    /** @return array{id: int, name: string, region: ?string, lat: float, lng: float, population: int}|null */
    public function findCity(string $countryCode, int $cityId): ?array;
}
```

- Country codes are uppercase ISO 3166-1 alpha-2.
- The optional constructor path defaults to `config('shipping.dataset_path')` and exists for tests.
- Load `countries.json` once; load only the selected `cities/{ISO2}.json`.
- Throw `RuntimeException` if the dataset is missing or malformed.
- Cache decoded data in static properties for the request.

### `App\Shipping\ShippingCalculator`

```php
final class ShippingCalculator
{
    /** @param array<string, mixed>|null $rates @param array<string, mixed>|null $origin */
    public function __construct(?array $rates = null, ?array $origin = null);

    public function isConfigured(): bool;

    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float;

    /** @return array{distance_km: float, shipping_usd: float, currency: string}|null */
    public function quote(float $latitude, float $longitude): ?array;

    /** @return array{name: string, latitude: float, longitude: float} */
    public function origin(): array;
}
```

- `isConfigured()`: both rates must be numeric and `>= 0`.
- The optional constructor rates/origin default to config; they exist for tests that need unconfigured rates.
- `distanceKm()`: Haversine, Earth radius `6371.0088`, clamp the haversine `a` to `[0, 1]`.
- `quote()`: return `null` when not configured; round distance and shipping to 2 decimals.
- Origin comes from `config('shipping.origin')`.

### `App\Payments\PaymentGateway`

```php
interface PaymentGateway
{
    public function provider(): string;

    public function simulate(string $outcome): PaymentResult;
}
```

`App\Payments\PaymentResult`:

```php
final class PaymentResult
{
    public function successful(): bool;
    public function message(): string;
    public function reference(): ?string;
}
```

- `outcome` values: `success`, `failure`.
- `MockPaymentGateway::provider()` returns `mock`.
- A failure result must not change order status.

### `App\Models\OrderRepository`

Does **not** extend `App\Models\Repository` because orders do not use `deleted_at`.

```php
final class OrderRepository
{
    public function insertOrder(array $data): int;
    public function insertItems(int $orderId, array $items): void;
    public function findByToken(string $token): ?array;
    public function findAdmin(int $id): ?array;
    public function items(int $orderId): array;

    /** @return array{rows: array, total: int, page: int, pages: int, per_page: int} */
    public function paginate(int $page, int $perPage = 20): array;

    /** @return array|null */
    public function lockById(int $id): ?array;

    public function markPaid(int $id): void;
    public function markCancelled(int $id, string $reason): void;
}
```

- All methods use prepared statements.
- `findAdmin()` and `paginate()` omit `access_token`; use `findByToken()` when the guest token is needed.
- `lockById()` uses `SELECT ... FOR UPDATE`, must run inside an active transaction, and throws `LogicException` otherwise.
- `paginate()` orders by `created_at DESC, id DESC` and clamps `page` to `>= 1`, `perPage` to `1..100`.

### `App\Services\CheckoutService`

```php
final class CheckoutService
{
    /**
     * @param array<int, int> $cart albumId => quantity
     * @return array<string, mixed> order row with items
     */
    public function createOrder(
        array $cart,
        array $recipient,
        array $destination,
        array $quote
    ): array;

    /**
     * @return array{status: string, order: array<string, mixed>, restored: int, skipped: list<string>}
     * Status values: paid, pending, cancelled, unavailable
     */
    public function pay(array $order, PaymentResult $result): array;

    /**
     * @return array{restored: int, skipped: list<string>}
     */
    public function restoreCart(array $order): array;
}
```

- `createOrder()` validates sale status, stock, and price; creates the order and item snapshots in one transaction.
- Input shapes: `$recipient = ['recipient_name', 'recipient_phone', 'shipping_address']`; `$destination = ['country_code', 'country', 'city_id', 'city', 'region', 'latitude', 'longitude']`; `$quote = ['distance_km', 'shipping_usd', 'currency']`.
- It returns the stored order row (including `access_token`) plus an `items` list ordered by album ID.
- It does **not** clear the cart; the controller clears after success.
- It throws `RuntimeException` with a safe message on validation/stock failure.
- `pay()` owns the payment transaction and idempotency logic.
- `restoreCart()` adds available order items back to the session cart, capped by current stock.

---

## Data Contracts

### Shipping config

`config/shipping.php`:

```php
return [
    'currency' => 'USD',
    'origin' => [
        'name' => 'Seoul, South Korea',
        'latitude' => 37.5665,
        'longitude' => 126.9780,
    ],
    'rates' => [
        'base_fee_usd' => env('SHIPPING_BASE_FEE_USD'),
        'fee_per_km_usd' => env('SHIPPING_FEE_PER_KM_USD'),
    ],
    'dataset_path' => base_path('database/data/shipping'),
];
```

`.env.example`:

```dotenv
# Album purchase shipping simulation.
# Leave either value blank to disable checkout.
SHIPPING_BASE_FEE_USD=3.00
SHIPPING_FEE_PER_KM_USD=0.0015
```

### Shipping dataset files

`database/data/shipping/countries.json`:

```json
[
  {"code": "KR", "name": "South Korea"}
]
```

`database/data/shipping/cities/KR.json`:

```json
[
  {
    "id": 1835848,
    "name": "Seoul",
    "region": "Seoul",
    "lat": 37.5665,
    "lng": 126.9780,
    "population": 10349312
  }
]
```

`database/data/shipping/version.json`:

```json
{
  "source": "GeoNames",
  "source_url": "https://download.geonames.org/export/dump/",
  "source_date": "YYYY-MM-DD",
  "license": "CC BY 4.0",
  "license_url": "https://creativecommons.org/licenses/by/4.0/",
  "filter": "cities15000 + national capitals supplement",
  "country_count": 0,
  "city_count": 0
}
```

- Sort countries by `name`.
- Sort cities by `population DESC, name ASC`.
- Include a `README.md` with source links, license, filter criteria, rebuild command, and modification note.
- Include a `LICENSE` file with the CC BY 4.0 text or a durable link and attribution notice.
- Do not commit raw GeoNames downloads; add `database/data/shipping/raw/` to `.gitignore` if used.

### Orders schema

```sql
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    invoice_number VARCHAR(40) NOT NULL,
    access_token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    recipient_name VARCHAR(120) NOT NULL,
    recipient_phone VARCHAR(40) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    destination_country_code CHAR(2) NOT NULL,
    destination_country VARCHAR(120) NOT NULL,
    destination_city_id INT UNSIGNED NOT NULL,
    destination_city VARCHAR(160) NOT NULL,
    destination_region VARCHAR(160) NULL,
    destination_latitude DECIMAL(9,6) NOT NULL,
    destination_longitude DECIMAL(9,6) NOT NULL,
    distance_km DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    shipping_amount DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    payment_provider VARCHAR(40) NOT NULL DEFAULT 'mock',
    status ENUM('PENDING_PAYMENT', 'PAID', 'CANCELLED') NOT NULL DEFAULT 'PENDING_PAYMENT',
    paid_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    cancel_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_invoice_number (invoice_number),
    UNIQUE KEY uq_orders_access_token (access_token),
    KEY idx_orders_user_id (user_id),
    KEY idx_orders_status_created (status, created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    album_id INT UNSIGNED NOT NULL,
    album_name VARCHAR(160) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_items_order_id (order_id),
    KEY idx_order_items_album_id (album_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_album FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Seed prices and stock

All albums are `is_for_sale=1`.

| Album | Price | Stock |
|---|---:|---:|
| How Sweet | 24.99 | 40 |
| Get Up | 22.99 | 35 |
| OMG | 18.99 | 50 |
| NewJeans (Debut) | 19.99 | 45 |
| Supernatural | 21.99 | 30 |
| Gods | 9.99 | 60 |
| Zero | 8.99 | 60 |

### Routes

| Method | Path | Handler |
|---|---|---|
| GET | `/cart` | `CartController::index` |
| POST | `/cart/items` | `CartController::add` |
| POST | `/cart/items/{id}` | `CartController::update` |
| POST | `/cart/items/{id}/remove` | `CartController::remove` |
| GET | `/checkout` | `CheckoutController::show` |
| POST | `/checkout` | `CheckoutController::store` |
| GET | `/shipping/cities` | `ShippingController::cities` |
| GET | `/shipping/estimate` | `ShippingController::estimate` |
| GET | `/checkout/{token}/payment` | `PaymentController::show` |
| POST | `/checkout/{token}/pay` | `PaymentController::pay` |
| GET | `/orders/{token}` | `PaymentController::status` |
| GET | `/admin/orders` | `Admin\OrderController::index` |
| GET | `/admin/orders/{id}` | `Admin\OrderController::show` |

- Public routes are registered in `public/index.php` before legacy redirects.
- Admin routes are covered by the existing `/admin` auth guard.
- Token route values must match `^[a-f0-9]{64}$` before database lookup.

---

## Tasks

### Task T1: Config, env, shared helpers

**Depends on:** none

**Context to load:**
- `config/app.php`
- `app/Support/helpers.php`
- `app/Core/Response.php`
- `.env.example`

**Files:**
- Create: `config/shipping.php`
- Modify: `.env.example`
- Modify: `app/Support/helpers.php`
- Modify: `app/Core/Response.php`

**Checklist:**

- [x] Create `config/shipping.php` exactly as in **Data Contracts**.
- [x] Add `SHIPPING_BASE_FEE_USD` and `SHIPPING_FEE_PER_KM_USD` to `.env.example` with comments that blank disables checkout.
- [x] Add `money()` to `app/Support/helpers.php`; return `$0.00` for null/empty/non-numeric and `$` + `number_format((float) $amount, 2)` otherwise.
- [x] Add `Response::json(array $data, int $status = 200): self` with `Content-Type: application/json; charset=UTF-8` and `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`.
- [x] Keep existing helper and response behavior unchanged.

**Verify:**

- [x] `php -l config/shipping.php`
- [x] `php -l app/Support/helpers.php`
- [x] `php -l app/Core/Response.php`
- [x] Quick CLI check: `config('shipping.rates.base_fee_usd')` returns the env value or `null`.
- [x] Quick CLI check: `money(12.5)` returns `$12.50`.
- [x] Quick CLI check: `Response::json(['ok' => true])` has JSON content type.

**Acceptance:** Config, money formatting, and JSON responses are available without enabling checkout by default.

**Handoff:** Record the exact config keys and helper names.

---

### Task T2: GeoNames shipping dataset

**Depends on:** T1

**Context to load:**
- `config/shipping.php`
- `database/` directory listing
- GeoNames source pages (download URLs only; no need to read full docs)

**Files:**
- Create: `database/build-shipping-data.php`
- Create: `database/data/shipping/countries.json`
- Create: `database/data/shipping/cities/{ISO2}.json`
- Create: `database/data/shipping/version.json`
- Create: `database/data/shipping/README.md`
- Create: `database/data/shipping/LICENSE`
- Modify: `.gitignore` (ignore raw GeoNames downloads if used)

**Checklist:**

- [x] Write a CLI generator that accepts paths to extracted GeoNames `cities15000.txt`, `countryInfo.txt`, and `admin1CodesASCII.txt`.
- [x] Filter populated places (`feature class P`), valid ISO2 country codes, and population `> 0`.
- [x] Add missing national capitals from a supplement source so every supported country has at least one city. Document any intentional exception.
- [x] Resolve country names from `countryInfo.txt`.
- [x] Resolve region names from `admin1CodesASCII.txt`; leave `region` null when unavailable.
- [x] Deduplicate by GeoNames ID within each country.
- [x] Generate `countries.json`, one `cities/{ISO2}.json` per country, and `version.json`.
- [x] Add `README.md` with source URL, source date, license, filter criteria, row counts, rebuild command, and modification note.
- [x] Add `LICENSE` with CC BY 4.0 attribution text or a durable license link plus attribution notice.
- [x] Ensure the runtime app never downloads data; only the generator reads raw files.

**Verify:**

- [x] `php -l database/build-shipping-data.php`
- [x] Validate every generated JSON file with `json_decode(..., JSON_THROW_ON_ERROR)`.
- [x] Check counts in `version.json` match the files.
- [x] Spot-check `KR` contains Seoul, `ID` contains Jakarta, `US` contains Los Angeles and New York, `GB` contains London.
- [x] Confirm every country in `countries.json` has a non-empty city file, or the exception is documented.
- [x] Confirm no duplicate city IDs within a country.

**Acceptance:** Dataset is local, versioned, redistributable, attributed, and loadable without network access.

**Handoff:** Record dataset date, country count, city count, and any documented exceptions.

---

### Task T3: Destination catalog + shipping calculator

**Depends on:** T1, T2

**Context to load:**
- `config/shipping.php`
- `database/data/shipping/countries.json`
- One generated city file, e.g. `database/data/shipping/cities/KR.json`
- `app/Support/helpers.php`

**Files:**
- Create: `app/Shipping/DestinationCatalog.php`
- Create: `app/Shipping/ShippingCalculator.php`
- Create: `tests/shipping_calculator_test.php`

**Checklist:**

- [x] Implement `DestinationCatalog` per **Interface Contracts**.
- [x] Implement `ShippingCalculator::isConfigured()` with numeric `>= 0` checks.
- [x] Implement Haversine distance with Earth radius `6371.0088`; clamp `a` to avoid negative `sqrt`.
- [x] Implement `quote()` using origin config; return `null` when rates are missing.
- [x] Round distance and shipping amount to 2 decimals.
- [x] Write a plain PHP test script with assertions for:
  - Seoul → Tokyo ≈ 1,160 km
  - Seoul → Jakarta ≈ 5,300 km
  - Seoul → Los Angeles ≈ 9,600 km
  - Seoul → London ≈ 8,900 km
  - `$3.00 + $0.0015/km` calculation
  - missing rates → `isConfigured() === false` and `quote() === null`
  - unknown country/city → null/false
- [x] Do not add a test framework dependency.

**Verify:**

- [x] `php -l app/Shipping/DestinationCatalog.php`
- [x] `php -l app/Shipping/ShippingCalculator.php`
- [x] `php tests/shipping_calculator_test.php`

**Acceptance:** Catalog lookups and Haversine quotes are correct, deterministic, and independent of MySQL.

**Handoff:** Measured from config origin to dataset coordinates: Tokyo 1,152.62 km, Jakarta 5,296.88 km, Los Angeles 9,585.81 km, London 8,857.18 km, Seoul 0.07 km; tolerance ±10 km. 79 checks pass.

---

### Task T4: Orders schema + seed updates

**Depends on:** T1

**Context to load:**
- `database/schema.sql`
- `database/seed.php`
- `config/database.php`
- `README.md` section “Data dan soft delete”

**Files:**
- Modify: `database/schema.sql`
- Modify: `database/seed.php`

**Checklist:**

- [x] Add `orders` exactly as in **Data Contracts**.
- [x] Add `order_items` exactly as in **Data Contracts**.
- [x] Add `order_items`, `orders` to the `$tables` list in `seed.php` so `--fresh` truncates them with foreign key checks disabled.
- [x] Update the album seed insert to include `price`, `stock`, `is_for_sale`.
- [x] Set all 7 albums to the prices/stocks in **Seed prices and stock** and `is_for_sale = 1`.
- [x] Preserve the existing `--fresh` guard and idempotency behavior.
- [x] Do not change existing songs, news, events, members, histories, or gallery seed data.

**Verify:**

- [x] `php -l database/seed.php`
- [x] Import `database/schema.sql` into a disposable database, e.g. `newjeans_purchase_test`.
- [x] Run `php database/seed.php --fresh` against the disposable database.
- [x] Confirm `orders` and `order_items` exist and are empty.
- [x] Confirm all albums have the expected price/stock and `is_for_sale = 1`.
- [x] Confirm existing content counts are unchanged.
- [x] Do not run `--fresh` against the user's primary database without confirmation.

**Acceptance:** A fresh disposable database imports and seeds successfully; all albums are purchasable.

**Handoff:** Disposable DB `newjeans_purchase_test`: `orders`/`order_items` created empty; 7 albums seeded for sale with the specified price/stock; existing content counts unchanged (22 songs, 9 news, 12 events, 4 members, 4 histories, 12 gallery).

---

### Task T5: Cart service

**Depends on:** T1

**Context to load:**
- `app/Support/helpers.php` (`session_start_safe`)
- `app/Support/Cart.php` (new file target)

**Files:**
- Create: `app/Support/Cart.php`
- Create: `tests/cart_test.php`

**Checklist:**

- [x] Implement `Cart` per **Interface Contracts**.
- [x] Normalize album IDs and quantities to integers; ignore invalid IDs.
- [x] `add()` increments existing quantity; cap at `99`.
- [x] `setQuantity()` removes the item when quantity `<= 0`; cap at `99`.
- [x] `remove()`, `clear()`, `count()`, and `isEmpty()` behave predictably.
- [x] Do not access the database.
- [x] Write a plain PHP test that sets `$_SESSION['cart']` directly and asserts add/update/remove/clear/count.

**Verify:**

- [x] `php -l app/Support/Cart.php`
- [x] `php tests/cart_test.php`

**Acceptance:** Cart state is deterministic and cannot hold negative or invalid quantities.

**Handoff:** Session key `cart`; per-album cap `99`; `items()` normalizes/sorts ascending and treats a corrupted value as empty. `tests/cart_test.php`: 31 checks pass.

---

### Task T6: Cart routes, controller, page

**Depends on:** T4, T5

**Context to load:**
- `app/Support/Cart.php`
- `app/Models/AlbumRepository.php`
- `app/Controllers/Public/Concerns/PublicViewTrait.php`
- `app/Core/Csrf.php`
- `templates/pages/discography.php` (style reference only)
- `public/assets/css/theme.css` (`.btn`, `.input`, `.select`, `.field-label`)
- `public/assets/css/discography.css` (`.release-row` reference)

**Files:**
- Create: `app/Controllers/Public/CartController.php`
- Create: `templates/pages/cart.php`
- Create: `templates/partials/form-errors.php` (public variant)
- Create: `public/assets/css/purchase.css`
- Modify: `app/Models/AlbumRepository.php` (add `findMany(array $ids): array`)
- Modify: `public/index.php` (imports + routes)

**Checklist:**

- [x] Add `AlbumRepository::findMany(array $ids): array` using prepared placeholders, filtering `deleted_at IS NULL`, keyed by `id`.
- [x] `GET /cart`: load cart items, current album names/covers/prices/stock, compute subtotal, show unavailable warnings, empty state.
- [x] `POST /cart/items`: CSRF, integer album ID and quantity, album exists, `is_for_sale = 1`, stock `> 0`; add and redirect `/cart` with flash.
- [x] `POST /cart/items/{id}`: CSRF, quantity `1..min(stock, 99)`; update and redirect `/cart`.
- [x] `POST /cart/items/{id}/remove`: CSRF; remove and redirect `/cart`.
- [x] Cart page shows unit price, quantity input, line total, remove button, subtotal, “Continue shopping”, and “Checkout”.
- [x] Unavailable items show a clear warning and cannot be checked out.
- [x] Add responsive styles in `purchase.css`.
- [x] Do not trust any client-submitted price.

**Verify:**

- [x] `php -l` changed PHP files.
- [x] `GET /cart` empty state renders.
- [x] Add an in-stock album; verify item appears and header count updates.
- [x] Update quantity; verify subtotal.
- [x] Remove item; verify empty state.
- [x] Attempt adding a `0`-stock album; verify flash error and no cart change.
- [x] Submit without CSRF; verify `419`.

**Acceptance:** Session cart is fully manageable from the browser; unavailable albums cannot be added or checked out.

**Handoff:** Routes `GET /cart`, `POST /cart/items`, `POST /cart/items/{id}`, `POST /cart/items/{id}/remove`; `AlbumRepository::findMany()`; `templates/partials/form-errors.php`; `purchase.css` (`.cart-*`, `.form-errors`, `.btn.is-disabled`). 36 controller checks + HTTP add/update/remove/CSRF smoke pass on `newjeans_purchase_test_t6`. Header cart count is deferred to T7 (no header link yet).

---

### Task T7: Discography CTA + header cart link

**Depends on:** T4, T6

**Context to load:**
- `templates/pages/discography.php`
- `templates/partials/header.php`
- `public/assets/css/discography.css` (`.release-row`, `.rr-actions`, responsive rules)
- `public/assets/css/theme.css` (navbar styles)
- `templates/pages/album-detail.php` (confirm YouTube remains)
- `app/Support/Cart.php`
- `app/Core/Csrf.php`

**Files:**
- Modify: `templates/pages/discography.php`
- Modify: `templates/partials/header.php`
- Modify: `public/assets/css/discography.css`
- Modify: `public/assets/css/theme.css` (cart nav styles only)

**Checklist:**

- [x] Compute `isForSale`, `stock`, and `price` per release.
- [x] For sale + stock `> 0`: render a POST form to `/cart/items` with CSRF, `album_id`, `quantity=1`, and a `Buy Album` button.
- [x] For sale + stock `0`: render a non-actionable `Out of stock` badge.
- [x] Not for sale: render a non-actionable `Not for sale` badge.
- [x] Keep the Spotify action.
- [x] Remove the YouTube action from discography cards only; keep it on the album detail page.
- [x] Add cart link + total item count to the header; mark `cart` active when on `/cart`.
- [x] Ensure the buy form does not break `.rr-actions` flex layout; use `display: contents` on the form wrapper if needed.
- [x] Ensure the stretched row link does not intercept the buy button (`.rr-actions` already has `z-index: 2`; verify).
- [x] Add visible focus styles for the buy button and cart link.

**Verify:**

- [x] `php -l` changed PHP files.
- [x] In-stock albums show `Buy Album`; clicking adds to cart and redirects `/cart`.
- [x] Out-of-stock and not-for-sale albums show the correct non-actionable state.
- [x] Spotify still works; YouTube is absent from cards but present on detail.
- [x] Search/filter/sort still work.
- [x] Mobile and desktop layouts remain usable.
- [x] Cart count updates after add/remove.

**Acceptance:** Discography CTA matches the locked decision and does not regress existing filters or links.

**Handoff:** Labels `Buy Album` / `Out of stock` / `Not for sale`; added `.rr-buy-form` (`display: contents`), `.rr-price`, `.rr-availability--out/off`, and header `.nav-cart-link` / `.nav-cart-count` (total qty, `99+` cap, active on `/cart`). Verified on `newjeans_purchase_test_t6`: 7 buy buttons, add/remove/out-of-stock/not-for-sale HTTP checks, search/filter/sort, Playwright at 1280/768/390 with no console errors or horizontal overflow.

---

### Task T8: Shipping city/estimate endpoints

**Depends on:** T1, T3

**Context to load:**
- `app/Shipping/DestinationCatalog.php`
- `app/Shipping/ShippingCalculator.php`
- `app/Core/Response.php` (`json`)
- `app/Core/Request.php`
- `app/Controllers/Public/Concerns/PublicViewTrait.php`
- `public/index.php` route style

**Files:**
- Create: `app/Controllers/Public/ShippingController.php`
- Modify: `public/index.php` (imports + routes)

**Checklist:**

- [x] `GET /shipping/cities?country=KR`: validate ISO2, return `{country, cities}` with `id`, `name`, `region`.
- [x] `GET /shipping/estimate?country=KR&city=1835848`: validate country/city pair, require configured rates, return `{distance_km, shipping_usd, currency}`.
- [x] Return `422` JSON for invalid/unknown country or city.
- [x] Return `503` JSON when shipping rates are not configured.
- [x] Do not require CSRF for these read-only GET endpoints.
- [x] Do not write to the database.
- [x] Add `Cache-Control: no-store` if convenient.

**Verify:**

- [x] `php -l` changed PHP file.
- [x] `Invoke-RestMethod "http://localhost:8000/shipping/cities?country=KR"` returns Seoul.
- [x] Estimate for Seoul → Jakarta returns a plausible distance and shipping amount.
- [x] Invalid country, invalid city, and missing rates return the expected status/JSON.
- [x] Confirm checkout disabled state is represented by `503` from the estimate endpoint.

**Acceptance:** Browser JS can populate cities and display a server-calculated estimate without duplicating the Haversine formula.

**Handoff:** `GET /shipping/cities?country=KR` → 200 `{"country":"KR","cities":[{"id":1835848,"name":"Seoul","region":"Seoul"},...]}`. `GET /shipping/estimate?country=ID&city=1642911` → 200 `{"distance_km":5296.88,"shipping_usd":10.95,"currency":"USD"}`. Errors `{"error":"..."}` with 422 (bad country/city, incl. city from another country) or 503 (rates unconfigured). All responses `Cache-Control: no-store`; constructor injection `(DestinationCatalog, ShippingCalculator)` for T11/tests.

---

### Task T9: Order repository + order creation service

**Depends on:** T3, T4

**Context to load:**
- `database/schema.sql` (`orders`, `order_items`)
- `app/Core/Database.php`
- `app/Models/Repository.php` (do not extend for orders)
- `app/Models/AlbumRepository.php`
- `app/Shipping/ShippingCalculator.php`
- `app/Services/CheckoutService.php` (new file target)
- `app/Models/OrderRepository.php` (new file target)

**Files:**
- Create: `app/Models/OrderRepository.php`
- Create: `app/Services/CheckoutService.php`
- Create: `tests/checkout_service_test.php`

**Checklist:**

- [x] Implement `OrderRepository` per **Interface Contracts**.
- [x] Implement `CheckoutService::createOrder()`:
  - validate non-empty cart
  - load albums from DB
  - reject deleted, not-for-sale, or insufficient-stock albums
  - reject non-numeric/negative prices
  - snapshot album name, unit price, quantity, line total
  - compute `subtotal`, `shipping_amount`, `total`
  - generate 64-char hex `access_token`
  - generate `invoice_number = NJ-YYYYMMDD-` + uppercase first 8 token chars
  - set `payment_provider = 'mock'`, `status = 'PENDING_PAYMENT'`
  - store destination snapshot and coordinates
  - insert order + items in one transaction
  - roll back on any failure
- [x] Do not clear the cart inside the service.
- [x] Throw `RuntimeException` with a safe user-facing message on validation/stock failure.
- [x] Write a CLI test against a disposable DB:
  - successful order persists order + items with correct snapshots/totals
  - insufficient stock throws and writes nothing
  - forced item insert failure rolls back the order
  - token/invoice uniqueness holds

**Verify:**

- [x] `php -l` changed PHP files.
- [x] `php tests/checkout_service_test.php` against a disposable DB.
- [x] Inspect order and item rows for snapshot correctness.
- [x] Confirm no order is created when stock/sale validation fails.

**Acceptance:** Order creation is atomic, snapshot-based, and never trusts cart prices.

**Handoff:** Record the exception message contract and test DB used.

---

### Task T10: Checkout controller + page + validation

**Depends on:** T3, T5, T6, T8, T9

**Context to load:**
- `app/Services/CheckoutService.php`
- `app/Shipping/DestinationCatalog.php`
- `app/Shipping/ShippingCalculator.php`
- `app/Support/Cart.php`
- `app/Controllers/Public/CartController.php`
- `templates/pages/cart.php`
- `templates/partials/form-errors.php`
- `app/Core/Csrf.php`
- `app/Support/helpers.php` (`old`, `set_old`, `form_errors`, `flash`, `clear_form_state`)
- `app/Core/Validator.php`

**Files:**
- Create: `app/Controllers/Public/CheckoutController.php`
- Create: `templates/pages/checkout.php`
- Modify: `public/assets/css/purchase.css`
- Modify: `public/index.php` (imports + routes)

**Checklist:**

- [x] `GET /checkout`:
  - redirect to `/cart` with flash when cart is empty
  - load cart albums and compute subtotal
  - load country list
  - show disabled checkout notice when rates are missing
  - generate a one-time `checkout_nonce` in session and render it as a hidden field
  - render recipient fields, country/city selects, order summary
- [x] `POST /checkout`:
  - validate CSRF
  - validate and consume `checkout_nonce`
  - type-check scalar fields; reject arrays
  - validate name `1..120`, phone `7..30` digits/`+ - ( ) space`, address `1..255`
  - validate ISO2 country and city belongs to country via `DestinationCatalog`
  - validate non-empty cart, sale status, stock, and configured rates
  - on validation errors: `set_old()`, `set_form_errors()`, regenerate nonce, redirect `/checkout` with `303`
  - on stock/sale failure: flash error and redirect `/cart` with `303`
  - on success: call `CheckoutService::createOrder()`, `Cart::clear()`, set `$_SESSION['last_order_token']`, `clear_form_state()`, redirect `/checkout/{token}/payment` with `303`
- [x] Double submit:
  - nonce is single-use
  - if nonce is reused and `last_order_token` exists, redirect to that payment page
  - otherwise return `419`
- [x] Checkout page:
  - accessible labels and error messages
  - subtotal, shipping placeholder, total
  - “simulated estimate, not a courier quote” note
  - “Ships from Seoul, South Korea”
  - no-JS fallback message for the city selector
- [x] Preserve selected country and city after validation errors; server-render city options for the selected country if possible.

**Verify:**

- [x] `php -l` changed PHP files.
- [x] Empty cart redirects to `/cart`.
- [x] Missing rates shows disabled checkout and POST rejects.
- [x] Missing/invalid recipient fields preserve old input and show errors.
- [x] Invalid country/city pair is rejected.
- [x] Valid checkout creates exactly one order, clears cart, and opens payment page.
- [x] Double-submit does not create a second order.
- [x] Submitting without CSRF returns `419`.
- [x] Stock/sale change after cart add is caught before order creation.

**Acceptance:** Checkout validates all server-side inputs, creates one atomic order, and never writes on invalid input.

**Handoff:** Record nonce/session keys and the exact validation messages.

---

### Task T11: Checkout JS — city dropdown + estimate

**Depends on:** T8, T10

**Context to load:**
- `templates/pages/checkout.php`
- `app/Controllers/Public/ShippingController.php`
- `public/assets/js/discography.js` (style reference only)

**Files:**
- Create: `public/assets/js/checkout.js`
- Modify: `templates/pages/checkout.php` (script include + data attributes)

**Checklist:**

- [x] Add data attributes for cities URL, estimate URL, subtotal, and selected country/city.
- [x] On country change:
  - clear city options
  - disable city until loaded
  - fetch `/shipping/cities?country=...`
  - populate options with `id`, `name`, and `region`
  - show a friendly error on failure
- [x] On city change:
  - fetch `/shipping/estimate?country=...&city=...`
  - update distance, shipping amount, and total
  - use an `aria-live="polite"` region
- [x] Preserve selected city on initial load / validation error and trigger an estimate if a city is already selected.
- [x] Abort stale requests or ignore out-of-order responses.
- [x] Handle missing rates (`503`) and invalid destination (`422`) without breaking the form.
- [x] Do not duplicate the Haversine formula; always use the server response.
- [x] `node --check` passes.

**Verify:**

- [x] `node --check public/assets/js/checkout.js`
- [ ] Changing country repopulates cities.
- [ ] Changing city updates distance, shipping, and total.
- [ ] No console errors on initial load, country change, city change, or error response.
- [ ] Selected city survives a validation-error page reload.
- [ ] Form still submits with JS disabled (city may require server-side re-render; document the fallback).

**Acceptance:** Destination changes visibly update the simulated shipping estimate; the server remains authoritative.

**Handoff:** Record data attribute names and endpoint URLs.

---

### Task T12: Payment gateway + payment service

**Depends on:** T4, T5, T9

**Context to load:**
- `app/Services/CheckoutService.php`
- `app/Models/OrderRepository.php`
- `app/Support/Cart.php`
- `database/schema.sql` (`orders`, `order_items`)
- `app/Core/Database.php`

**Files:**
- Create: `app/Payments/PaymentGateway.php`
- Create: `app/Payments/MockPaymentGateway.php`
- Create: `app/Payments/PaymentResult.php`
- Modify: `app/Services/CheckoutService.php` (`pay()`, `restoreCart()`)
- Create: `tests/payment_service_test.php`

**Checklist:**

- [x] Implement `PaymentGateway`, `PaymentResult`, and `MockPaymentGateway` per **Interface Contracts**.
- [x] `pay()` failure result: return `pending`, no DB change.
- [x] `pay()` success:
  - start transaction
  - lock order with `lockById()`/`FOR UPDATE`
  - if `PAID`, return `paid` without stock changes (idempotent)
  - if `CANCELLED`, return `cancelled`
  - if `PENDING_PAYMENT`, lock album rows in ascending `album_id` order
  - recheck sale status, deletion, and stock for every item
  - if any item is unavailable: mark order `CANCELLED` with a reason containing “stock”, commit, restore cart, return `cancelled`/`unavailable`
  - otherwise decrement stock with `stock = stock - ?` and `stock >= ?`
  - mark order `PAID` and set `paid_at`
  - commit
  - rollback on any exception
- [x] `restoreCart()`:
  - read order items
  - check current album availability and stock
  - add `min(order quantity, stock)` to cart
  - return restored count and skipped album names
- [x] Ensure no negative stock and no double decrement.
- [x] Write a CLI test against a disposable DB:
  - success marks paid and decrements once
  - repeated success does not decrement again
  - failure leaves pending
  - failure then success pays once
  - stock unavailable cancels and restores available items
  - two orders for the last copy: only one pays; the other cancels or fails without negative stock

**Verify:**

- [x] `php -l` changed PHP files.
- [x] `php tests/payment_service_test.php` against a disposable DB.
- [x] Inspect stock and order status after each scenario.
- [x] Confirm cancelled orders have `cancelled_at` and `cancel_reason`.

**Acceptance:** Payment is atomic, idempotent, and handles stock races without overselling.

**Handoff:** Record the returned status values and cancellation reason text.

---

### Task T13: Payment + order status pages

**Depends on:** T10, T12

**Context to load:**
- `app/Services/CheckoutService.php`
- `app/Models/OrderRepository.php`
- `app/Payments/*`
- `templates/pages/checkout.php` (style reference)
- `public/assets/css/purchase.css`
- `app/Core/Response.php`

**Files:**
- Create: `app/Controllers/Public/PaymentController.php`
- Create: `templates/pages/payment.php`
- Create: `templates/pages/order-status.php`
- Modify: `public/assets/css/purchase.css`
- Modify: `public/index.php` (imports + routes)

**Checklist:**

- [ ] `GET /checkout/{token}/payment`:
  - validate token regex `^[a-f0-9]{64}$`
  - find order by token; missing → 404
  - `PAID` → redirect to `/orders/{token}` or show paid state
  - `CANCELLED` → show cancelled state + cart/discography link
  - `PENDING_PAYMENT` → show order summary, amount, “simulation only” notice, success and failure forms with CSRF
- [ ] `POST /checkout/{token}/pay`:
  - validate CSRF
  - outcome must be `success` or `failure`
  - failure → flash error and redirect back to payment page
  - success → call `CheckoutService::pay()`
  - paid → redirect `/orders/{token}` with `303`
  - cancelled due to stock → flash which items were removed, redirect `/cart` with `303`
- [ ] `GET /orders/{token}`:
  - validate token; missing → 404
  - show status, invoice, items, subtotal, shipping, total, destination, and timestamps
  - pending → link to payment page
  - cancelled → link to cart/discography
- [ ] Add `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex`, and `Cache-Control: no-store` to payment/status responses.
- [ ] Do not expose the access token in admin views.

**Verify:**

- [ ] `php -l` changed PHP files.
- [ ] Invalid token returns 404.
- [ ] Success page shows paid status and correct totals.
- [ ] Failure keeps pending and allows retry.
- [ ] Repeated success is idempotent.
- [ ] Stock-unavailable success cancels order and restores cart.
- [ ] CSRF failure returns `419`.
- [ ] Security headers are present.

**Acceptance:** Guests can view, pay, retry, and understand every order state without seeing another order.

**Handoff:** Record token validation and header names.

---

### Task T14: Admin orders list/detail

**Depends on:** T4, T9

**Context to load:**
- `app/Models/OrderRepository.php`
- `app/Controllers/Admin/Concerns/AdminFormTrait.php`
- `templates/admin/albums/index.php` (table style reference)
- `templates/layouts/admin.php`
- `public/index.php` admin route style

**Files:**
- Create: `app/Controllers/Admin/OrderController.php`
- Create: `templates/admin/orders/index.php`
- Create: `templates/admin/orders/show.php`
- Modify: `public/index.php` (imports + routes)
- Modify: `templates/layouts/admin.php` (Orders nav item)

**Checklist:**

- [ ] Add `GET /admin/orders` and `GET /admin/orders/{id}`.
- [ ] Index:
  - pagination 20 per page, newest first
  - columns: invoice, date, recipient, destination, total, status
  - status badges: pending warning, paid success, cancelled secondary/danger
  - empty state
  - page links preserve query string
- [ ] Detail:
  - invoice, status, payment provider, created/paid/cancelled timestamps
  - recipient name/phone/address
  - destination country/city/region, coordinates, distance
  - item snapshots: album name, unit price, quantity, line total
  - subtotal, shipping, total
  - back link
- [ ] Add Orders nav item with active state.
- [ ] No edit/delete actions.
- [ ] Do not display the access token.

**Verify:**

- [ ] `php -l` changed PHP files.
- [ ] Guest `/admin/orders` redirects to login.
- [ ] Admin index shows seeded/test orders and pagination.
- [ ] Detail shows snapshots even if the album was later renamed or soft-deleted.
- [ ] Pending/paid/cancelled badges are correct.
- [ ] Empty state renders.

**Acceptance:** Admin can inspect order summaries and immutable snapshots without mutating orders.

**Handoff:** Record pagination parameter and badge classes.

---

### Task T15: Dashboard + docs + spec sync

**Depends on:** T11, T13, T14

**Context to load:**
- `app/Controllers/Admin/DashboardController.php`
- `templates/admin/dashboard.php`
- `README.md`
- `docs/superpowers/specs/2026-11-17-album-purchase-design.md`
- This plan's **Locked Decisions**

**Files:**
- Modify: `app/Controllers/Admin/DashboardController.php`
- Modify: `templates/admin/dashboard.php`
- Modify: `README.md`
- Modify: `docs/superpowers/specs/2026-11-17-album-purchase-design.md`

**Checklist:**

- [ ] Dashboard: add total orders and pending orders counts; query `orders` separately because it has no `deleted_at`.
- [ ] Update stale dashboard copy that says modules are still coming.
- [ ] README:
  - update table count from 9 to 11
  - document `orders`/`order_items`
  - add public and admin routes
  - document shipping env keys, blank-disables behavior, and rate example
  - document dataset attribution and rebuild command
  - document guest checkout and mock payment scope
  - update verification commands
- [ ] Spec: record the locked decisions that changed or clarified the design:
  - `CANCELLED` status + cart restore
  - GeoNames `cities15000` + capitals, CC BY 4.0
  - `$3.00 + $0.0015/km`
  - all albums seeded for sale
  - English CTA + YouTube removal from cards
  - live city/estimate endpoints
  - admin pagination
  - security headers
- [ ] Keep README setup instructions runnable from a fresh clone.
- [ ] Do not invent commands that are not verified.

**Verify:**

- [ ] `php -l` changed PHP files.
- [ ] README route table matches `public/index.php`.
- [ ] README data section matches `database/schema.sql`.
- [ ] Spec decisions match this plan.
- [ ] Dashboard renders with zero and non-zero orders.

**Acceptance:** Documentation and spec match the implemented behavior; a fresh clone can configure and run the flow.

**Handoff:** Record doc files changed and any intentionally undocumented follow-ups.

---

### Task T16: Final end-to-end QA

**Depends on:** T1–T15

**Context to load:**
- This plan's **Manual Review Checklist**
- `README.md`
- Your disposable test database name from T4/T9/T12

**Files:** None unless a defect is found; fix defects in the owning task.

**Checklist:**

- [x] `php -l` every PHP file under `app/`, `config/`, `templates/`, `public/`, `database/`, `tests/`.
- [x] `node --check` every JS file under `public/assets/js/`.
- [ ] Create/import a fresh disposable database and run `schema.sql` + `seed.php --fresh`.
- [ ] Start `php -S localhost:8000 -t public`.
- [ ] Smoke-test all public routes, including new cart/checkout/payment/status routes.
- [ ] Unknown route and invalid order token return 404.
- [ ] Admin guest access redirects to login.
- [ ] Add/update/remove cart items; verify header count and subtotal.
- [ ] Out-of-stock and not-for-sale albums cannot be added or checked out.
- [ ] Checkout validation, CSRF, nonce, and missing-rate disabled state.
- [ ] Country change populates cities; city change updates distance/shipping/total.
- [ ] Order persists with item/price/destination snapshots and appears in admin.
- [ ] Success marks paid and decrements stock exactly once; repeated success is idempotent.
- [ ] Failure leaves pending and is retryable.
- [ ] Stock-unavailable payment cancels the order and restores available cart items.
- [ ] Two sessions competing for the last copy do not create negative stock.
- [ ] Admin order list/detail and pagination.
- [ ] Payment/status security headers and noindex/referrer policy.
- [ ] Responsive check at 360px, 768px, and desktop for cart/checkout/payment/admin.
- [ ] No browser console errors.
- [ ] Update all task statuses to `done` and fill handoff notes.

**Acceptance:** The full manual matrix passes on a fresh disposable database, with evidence recorded in the Progress Tracker handoff column.

**Handoff:** Record pass/fail counts, any skipped checks, and the disposable DB name.

---

## Manual Review Checklist

- [ ] Discography card shows `Buy Album` for in-stock albums and non-actionable states otherwise.
- [ ] YouTube remains on album detail but is absent from discography cards.
- [ ] Header cart count matches total quantity.
- [ ] Cart quantity/remove flows work and subtotal is correct.
- [ ] Checkout cannot submit with empty cart, missing rates, invalid destination, or invalid recipient fields.
- [ ] Shipping estimate changes when the city changes.
- [ ] Order and item snapshots persist and are visible to admin.
- [ ] Payment success decreases stock once; repeated success does not.
- [ ] Payment failure leaves order pending and retryable.
- [ ] Stock-unavailable payment cancels order and restores available cart items.
- [ ] `CANCELLED` orders do not decrement stock.
- [ ] Invalid/expired tokens return 404 without leaking order data.
- [ ] All state-changing POSTs reject missing/invalid CSRF.
- [ ] Admin orders are read-only and behind login.
- [ ] No `.env`, raw GeoNames downloads, or storage files are reachable over HTTP.
- [ ] `php -l` and `node --check` pass for all changed files.
- [ ] Dataset attribution and license are present in the repository.

---

## Out of Scope

- Real payment processing, credentials, webhooks, or provider-specific behavior.
- Real carrier quotes, labels, tracking, or credentials.
- Customer registration/login, persistent carts, coupons, email notifications.
- Customer account/order-history area.
- Payment-proof upload and manual approval.
- Order editing, cancellation by admin, refunds, or returns.
- Tax/duty calculation.
- Multi-currency support.
- Album detail buy button (card CTA only for this iteration).

---

## Assumptions

- Shipping is charged once per order, regardless of item count.
- `$3.00 + $0.0015/km` is the configured demo rate; actual quotes are calculated from the generated city coordinates.
- All seeded albums are purchasable; admins can set stock to 0 or disable sale later.
- The mock gateway is the only payment provider; provider name is stored as `mock`.
- Guest order URLs are bearer tokens; anyone with the token can view/pay. This is acceptable for the demo and mitigated with noindex/no-referrer headers.
- Admin pagination is 20 rows per page.
- Cart quantity is capped at 99 and further capped by available stock at checkout.
- The dataset is generated once and committed; the app never downloads GeoNames data at runtime.
