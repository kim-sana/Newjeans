# Album Purchase Flow — Design

**Status:** Implemented and verified against `docs/superpowers/plans/2026-11-17-album-purchase.md`.

## Goal

Add a functional album-order flow to the NewJeans fan site. Guests can add albums to a browser-session cart, submit an order, estimate worldwide shipping from Seoul, and complete a simulated payment. Orders and their item/price snapshots are stored in MySQL.

## User flow

1. A discography card shows **Beli Album** when the album is marked for sale and has stock. An unavailable album shows a non-actionable availability state. The card's YouTube action is replaced; the YouTube link on the album detail page stays available.
2. The buy action adds the album to the guest's PHP session cart. The cart page lets the guest change quantities, remove items, and continue to checkout. Cart contents may expire with the browser session.
3. Guest checkout collects recipient name, phone, street address, and country/city selections. Country and city values must come from dropdown options; they are not accepted as free-text values.
4. After checkout validation, the server creates the order and immutable item-price snapshots, then opens the simulated gateway page.
5. The mock gateway offers simulated success and failure outcomes. Success marks the order paid and reduces album stock once. Failure leaves the order pending so the guest can retry.
6. Admins can view order summaries and details from a login-protected Orders area.

## Shipping estimate

- Use a local, versioned country/city reference dataset with country identifiers, city names, and city coordinates. The city selector is populated after a country is chosen. The dataset must cover worldwide destinations represented as populated cities, be redistributable with the project, and retain its source/license attribution in the repository.
- Use Seoul, South Korea as the origin. Calculate great-circle distance from origin to the selected city's coordinates using the Haversine formula.
- Calculate the simulated shipping amount as `base_fee_usd + distance_km * fee_per_km_usd`, rounded to two decimal places. The two rates live in application configuration and must be explicitly configured before checkout is enabled. The currency remains USD to match the current album prices.
- Store the selected country/city names, coordinates, distance, and calculated shipping amount on the order so future dataset or rate changes do not change an existing order.
- This is a demonstrative estimate, not a quote from a courier. No carrier API is used.

## Data and application components

- Keep the cart in the PHP session; do not add persistent `carts` or `cart_items` tables for this guest-only MVP.
- Add `orders` and `order_items` tables. Guest orders have a nullable `user_id` and store recipient/address fields, invoice number, destination snapshot, distance, subtotal, shipping amount, total, payment provider (`mock`), and status.
- `order_items` stores the album ID plus album name, unit price, and quantity snapshots. Checkout re-reads album prices and stock from the database; it never trusts cart-submitted prices.
- Initial order status is `PENDING_PAYMENT`. A failed simulated attempt keeps that status. A successful attempt changes it to `PAID`.
- Put gateway behavior behind a small `PaymentGateway` interface with a `MockPaymentGateway` implementation. This keeps the demo provider replaceable without integrating a real provider now.
- Add public cart, checkout, and payment routes, plus an authenticated admin Orders list/detail route. Exact route names can follow the project's existing route conventions.

## Validation and failure handling

- Use CSRF validation on every state-changing POST, prepared SQL statements, and server-side validation for quantities, recipient fields, and selected destination IDs.
- Recheck sale status and stock when creating an order and again when simulating a successful payment. If stock is no longer available, do not mark the order paid; show an actionable message.
- Update order status and stock in one database transaction. The success operation must be idempotent so repeated requests cannot decrement stock more than once.
- Use a non-guessable invoice/reference token for guest payment and confirmation URLs. Admin order routes remain protected by the existing admin authentication.

## Out of scope

- Real payment processing, payment credentials, webhooks, and payment-provider-specific behavior.
- Real carrier quotes, shipping labels, tracking, or carrier credentials.
- Customer registration/login, persistent carts, coupons, email notifications, and a customer account/order-history area.
- Payment-proof uploads and manual payment approval; the mock gateway supplies the simulated outcome.

## Acceptance criteria

- A purchasable album can be added from its discography card, managed in the session cart, and ordered as a guest.
- A checkout order and its album/price snapshots persist in the database and can be viewed by an admin.
- Country and city are selected from supported local dropdown data; changing the destination changes the calculated simulated shipping estimate from Seoul.
- Simulated payment success marks the order paid and reduces stock once; a simulated failure leaves the order pending and retryable.
- Unavailable/out-of-stock albums cannot be added or paid for.

## Decisions recorded

- Orders are functional and persisted; this is not a UI-only prototype.
- Guest checkout is used instead of customer registration/login.
- Cart state lives in the browser session; orders persist in the database.
- Payment uses a simulated gateway for the first implementation.
- Shipping uses a worldwide city-coordinate dataset and a configurable distance-based estimate from Seoul.
- Order statuses are `PENDING_PAYMENT`, `PAID`, and `CANCELLED`; if stock becomes unavailable at payment, the order is marked `CANCELLED` and available items are restored to the session cart.
- Destination data uses GeoNames `cities15000` + national capitals supplement under **CC BY 4.0** (`database/data/shipping/`), with demo rates `$3.00 + $0.0015/km` and all 7 seeded albums marked for sale.
- Discography cards use English CTAs (`Buy Album` / `Out of stock` / `Not for sale`) with YouTube removed from cards (kept on album detail).
- Checkout uses live `GET /shipping/cities` and `GET /shipping/estimate` endpoints; admin Orders paginate at 20 rows per page; payment/order status responses include `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex`, and `Cache-Control: no-store`.
