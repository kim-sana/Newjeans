# PHP Backend Content Platform Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. Update the **Progress Tracker** as each milestone completes.

**Goal:** Replace the static content site with a PHP 8.5 + MySQL server-rendered application so albums, songs, news, events, members, histories, and gallery items are stored in a database and managed through a session-authenticated admin panel.

**Architecture:** Front controller (`public/index.php`) + small hand-rolled router, PDO repositories, plain PHP templates with one public and one admin layout. Public pages render HTML on the server from MySQL. Admin pages provide CRUD + image upload behind session auth and CSRF. The existing CSS, fonts, and interactive JS are reused; data-loading JS is removed or refactored to operate on server-rendered DOM.

**Tech Stack:** PHP 8.5, MySQL 8.4 (Laragon, `127.0.0.1:3306`), Composer, PDO (`pdo_mysql`), vanilla JS, existing CSS.

**References:**
- Database review source: `archive/rancangan-database-newjeans.md`
- ER draft: `archive/dbdiagram.md`
- Design rules: `docs/design.md`
- Current frontend behavior: `index.html`, `pages/*.html`, `assets/js/*.js`, `assets/data/news.json`

---

## Locked Decisions

| Topic | Decision |
|---|---|
| Delivery | Server-rendered PHP pages (no public JSON API in Phase 1) |
| Framework | PHP native + PDO + Composer autoload (no Laravel/Slim) |
| Database | MySQL 8.4 via Laragon, database name `newjeans` |
| Auth | PHP session, `password_hash`/`password_verify`, CSRF token |
| Currency | USD, stored as `DECIMAL(10,2)` |
| Phase 1 scope | Content only: `users`, `album_types`, `albums`, `songs`, `news`, `events`, `members`, `histories`, `gallery_items` |
| Phase 2 scope | E-commerce: `shipping_options`, `carts`, `cart_items`, `orders`, `order_items`, `payment_proofs`, `order_status_histories` |
| Docroot | `public/` (app code and storage stay outside the docroot) |
| URLs | Clean routes via front controller (`/discography`, `/news/{slug}`, `/admin/...`) |
| Admin UI | AdminLTE 4.10.1 assets from `adminlte-v4/`, moved to `public/assets/vendor/adminlte-v4/`; admin pages do not load `theme.css` |

## Global Constraints

- Do not implement any e-commerce table, route, controller, or template in Phase 1.
- Keep `docs/design.md` visual rules: white background, blue `#005bab`, dot texture, bold-italic Archivo headings, DM Sans body.
- All SQL must use prepared statements; never interpolate user input.
- Escape every DB value in templates with `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- All mutating forms (admin CRUD, login) must include and validate a CSRF token.
- Uploads: validate MIME + extension + size, generate random filenames, never trust the client filename.
- Use `utf8mb4` and InnoDB for every table. Foreign keys use `ON DELETE RESTRICT`.
- Soft delete uses `deleted_at`; every public query filters `deleted_at IS NULL`.
- Password column stores only hashes; seed admin password must be documented and hashed.
- Run `php -l` on every changed PHP file before marking a task complete.
- Do not delete archived/legacy assets; keep `archive/` outside the docroot.

---

## Progress Tracker

| # | Milestone | Status | Owner | Notes |
|---|---|---|---|---|
| M1 | Project restructure + foundation | `done` | — | Composer, docroot, router, config, helpers. Verified: `/` 200, `/assets/*` 200, `/.env` 404, unknown 404, PDO `SELECT 1` OK (MariaDB 11.7.2, XAMPP) |
| M2 | Database schema + seed | `done` | — | 9 content tables; seeded 7 albums, 22 songs (3 featured), 9 news, 12 events, 4 members, 4 histories, 12 gallery items, 1 admin. `seed.php --fresh` idempotent. Verified on MariaDB 11.7.2 |
| M3 | Auth + admin shell | `done` | — | Session + CSRF + login/logout; dashboard counts; AdminLTE 4.10.1 (`adminlte-v4/`, Source Sans 3, popper/bootstrap/adminlte JS). Verified: `/admin` guest 302, bad CSRF 419, wrong password 422, valid login 302, dashboard 200, logout 302, Playwright no console errors |
| M4 | Admin CRUD | `done` | — | All modules implemented (album types, albums, songs, news, events, members, histories, gallery): CRUD, upload, soft delete, validation, flash/old input. Members, histories, gallery verified end-to-end (create/list/edit/delete + upload + soft delete). Reviewed and approved by user |
| M5 | Public server-rendered pages | `done` | — | 6 public routes + 404. Server-rendered header/footer/partials; home (featured 3, gallery, news), discography, schedule, news list/article, about, gallery. JS adapted to server DOM (schedule, gallery carousel/controls). PHP lint 32 files OK, JS `node --check` OK. Verified: `/`, `/discography`, `/schedule`, `/news`, `/news/{slug}`, `/about`, `/gallery` 200; unknown route + unknown slug 404; `/admin` guest 302 |
| M6 | Frontend JS refactor + link migration | `done` | — | Removed JSON-era JS (`components`, `home`, `news`, `news-data`, `gallery`) to `archive/legacy/js/`; gallery carousel now requires server-rendered cards (no 12-image fallback); seed data moved to `database/seed-data/news.json`; legacy HTML archived and `/pages/*.html` + `/home-legacy.html` 301-redirect to clean routes. PHP lint + JS `node --check` OK; Playwright smoke on home/gallery/schedule/discography/news/about passed with no console errors or 404s |
| M7 | QA + documentation | `done` | — | PHP lint 78 files OK, JS `node --check` 6 files OK. Fresh DB import + `seed.php --fresh` verified (counts 1/3/7/22/9/12/4/4/12). Public routes 200, unknown 404, legacy redirects 301. Admin: guest 302, CSRF 419, wrong password 422, login/logout, CRUD + upload for all 8 modules, soft delete hidden publicly. Empty states pass on all public pages. README rewritten for PHP setup/routes/admin. Verified on MariaDB 11.7.2 |

Status values: `pending`, `in_progress`, `blocked`, `done`.

---

## Target Structure

```text
Newjeans/
├── public/                     # docroot
│   ├── index.php               # front controller
│   ├── .htaccess               # rewrite all non-files to index.php
│   ├── assets/                 # moved from root assets/
│   └── uploads/                # public content images (optional; see M4)
├── app/
│   ├── Core/
│   │   ├── Database.php
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── View.php
│   │   ├── Auth.php
│   │   ├── Csrf.php
│   │   ├── Validator.php
│   │   └── Upload.php
│   ├── Controllers/
│   │   ├── Public/
│   │   └── Admin/
│   └── Models/
├── config/
│   ├── app.php
│   └── database.php
├── database/
│   ├── schema.sql
│   └── seed.php
├── templates/
│   ├── layouts/
│   ├── partials/
│   ├── pages/
│   └── admin/
├── storage/
│   ├── uploads/                # protected/non-public uploads
│   └── logs/
├── vendor/
├── .env.example
├── .gitignore
├── composer.json
└── README.md
```

---

## Phase 1 Schema Contract

Use this as the authoritative schema for M2. Adjust only if a task explicitly requires it.

### `users`
`id INT UNSIGNED PK AI`, `name VARCHAR(120) NOT NULL`, `email VARCHAR(190) NOT NULL UNIQUE`, `password VARCHAR(255) NOT NULL`, `role ENUM('admin','customer') NOT NULL DEFAULT 'customer'`, `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`, `deleted_at DATETIME NULL`. Index: `(deleted_at)`.

### `album_types`
`id INT UNSIGNED PK AI`, `name VARCHAR(80) NOT NULL UNIQUE`, `slug VARCHAR(80) NOT NULL UNIQUE`, `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`, `deleted_at DATETIME NULL`. Seed slugs must match the frontend filters: `mini-album`, `single`, `collab`.

### `albums`
`id INT UNSIGNED PK AI`, `album_type_id INT UNSIGNED NOT NULL`, `name VARCHAR(160) NOT NULL`, `cover_album VARCHAR(255) NOT NULL`, `release_date DATETIME NULL`, `spotify_url VARCHAR(255) NULL`, `youtube_url VARCHAR(255) NULL`, `price DECIMAL(10,2) NOT NULL DEFAULT 0`, `stock INT NOT NULL DEFAULT 0`, `is_for_sale TINYINT(1) NOT NULL DEFAULT 0`, `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`, `deleted_at DATETIME NULL`. FKs: `album_type_id → album_types.id`. Indexes: `(album_type_id)`, `(release_date)`, `(deleted_at)`, `(is_for_sale)`.

### `songs`
`id INT UNSIGNED PK AI`, `album_id INT UNSIGNED NOT NULL`, `title VARCHAR(160) NOT NULL`, `track_number INT NOT NULL DEFAULT 0`, `is_title TINYINT(1) NOT NULL DEFAULT 0`, `is_featured TINYINT(1) NOT NULL DEFAULT 0`, `audio_url VARCHAR(255) NULL`, `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`, `deleted_at DATETIME NULL`. FKs: `album_id → albums.id`. Indexes: `(album_id)`, `(is_featured)`, `(deleted_at)`.

### `news`
`id INT UNSIGNED PK AI`, `created_by INT UNSIGNED NULL`, `slug VARCHAR(190) NOT NULL UNIQUE`, `title VARCHAR(200) NOT NULL`, `thumbnail VARCHAR(255) NOT NULL`, `content TEXT NOT NULL`, `status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft'`, `published_at DATETIME NULL`, `source_name VARCHAR(120) NULL`, `source_url VARCHAR(255) NULL`, `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`, `deleted_at DATETIME NULL`. FKs: `created_by → users.id`. Indexes: `(status)`, `(published_at)`, `(deleted_at)`.

### `events`
`id INT UNSIGNED PK AI`, `created_by INT UNSIGNED NULL`, `slug VARCHAR(190) NOT NULL UNIQUE`, `title VARCHAR(200) NOT NULL`, `venue VARCHAR(160) NOT NULL`, `city VARCHAR(120) NOT NULL`, `event_date DATETIME NULL`, `poster VARCHAR(255) NULL`, `description TEXT NOT NULL`, `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`, `deleted_at DATETIME NULL`. FKs: `created_by → users.id`. Indexes: `(event_date)`, `(deleted_at)`.

### `members`
`id INT UNSIGNED PK AI`, `created_by INT UNSIGNED NULL`, `name VARCHAR(120) NOT NULL`, `photo VARCHAR(255) NOT NULL`, `description VARCHAR(255) NOT NULL`, `member_roles VARCHAR(120) NOT NULL`, `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`, `deleted_at DATETIME NULL`. FKs: `created_by → users.id`. Index: `(deleted_at)`.

### `histories`
`id INT UNSIGNED PK AI`, `created_by INT UNSIGNED NULL`, `image VARCHAR(255) NOT NULL`, `title VARCHAR(160) NOT NULL`, `description VARCHAR(255) NOT NULL`, `event_date DATETIME NULL`, `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`, `deleted_at DATETIME NULL`. FKs: `created_by → users.id`. Indexes: `(event_date)`, `(deleted_at)`.

### `gallery_items`
`id INT UNSIGNED PK AI`, `created_by INT UNSIGNED NULL`, `sort_order INT NOT NULL DEFAULT 0`, `image VARCHAR(255) NOT NULL`, `alt_text VARCHAR(200) NULL`, `caption VARCHAR(200) NULL`, `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`, `deleted_at DATETIME NULL`. FKs: `created_by → users.id`. Indexes: `(sort_order)`, `(deleted_at)`.

### Deferred to Phase 2 (do not create now)
`shipping_options`, `carts`, `cart_items`, `orders`, `order_items`, `payment_proofs`, `order_status_histories`. When created, add the review fixes: `orders.recipient_name`, `orders.phone`, `order_status_histories.changed_by`, `cart_items UNIQUE (cart_id, album_id)`.

---

## Frontend Data Mapping

| Page | Route | Source tables | Notes |
|---|---|---|---|
| Home | `/` | `songs` (is_featured, limit 3), `albums`, `gallery_items` (limit), `news` (published, limit) | Top 3 replaces hardcoded cards |
| Discography | `/discography` | `albums` + `album_types` + `songs` | Render `.release-row` with `data-type` = `album_types.slug`, `data-year` = year of `release_date` |
| Schedule | `/schedule` | `events` | Render `.wf-card`; split `event_date` into date + time label |
| News list | `/news` | `news` where `status='published'`, `deleted_at IS NULL`, `published_at <= NOW()` | Sort `published_at DESC` |
| News article | `/news/{slug}` | same filter + `slug` | 404 template if missing |
| About | `/about` | `histories`, `members` | Timeline + member grid |
| Gallery | `/gallery` | `gallery_items` ordered by `sort_order` | Lightbox reads rendered DOM |

---

## Tasks

### Task 1: Project restructure and foundation (M1)

**Files:**
- Create: `composer.json`, `.env.example`, `public/index.php`, `public/.htaccess`
- Create: `config/app.php`, `config/database.php`
- Create: `app/Core/Database.php`, `app/Core/Router.php`, `app/Core/Request.php`, `app/Core/Response.php`, `app/Core/View.php`
- Create: `app/Support/helpers.php`
- Move: `assets/` → `public/assets/`; `index.html` → temporary `public/index.php` shell; `pages/` → `public/pages/` (temporary; converted in M5)
- Create: `storage/uploads/.gitkeep`, `storage/logs/.gitkeep`

**Interfaces:**
- `App\Core\Database::pdo(): PDO` (singleton; `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `ATTR_EMULATE_PREPARES = false`).
- `App\Core\Router`: `get(string $path, callable|array $handler)`, `post(...)`, `dispatch(string $method, string $uri)`.
- `App\Core\View::render(string $template, array $data = []): string` and `View::layout(...)`.
- Helpers: `e()`, `url()`, `asset()`, `redirect()`, `old()`, `flash()`, `config()`.

- [ ] Initialize Composer with PSR-4 `App\\` → `app/`; add `vlucas/phpdotenv`; run `composer dump-autoload`.
- [ ] Create `.env.example` with `APP_ENV`, `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
- [ ] Load `.env` from `public/index.php`; fail with a clear message if required keys are missing.
- [ ] Implement PDO singleton and verify with a `SELECT 1` smoke script.
- [ ] Implement request/response helpers and a minimal router with 404 handling.
- [ ] Move `assets/` under `public/`; update CSS/JS references only where paths break.
- [ ] Add `.htaccess` that routes non-file requests to `index.php`.
- [ ] Add `.gitignore` entries for `vendor/`, `.env`, `storage/uploads/*`, `storage/logs/*`.
- [ ] Verify `php -S localhost:8000 -t public` serves `/assets/css/theme.css` and a temporary home route.

**Acceptance:** Server boots, DB connects, `/` renders a placeholder through the router, assets load, 404 works.

---

### Task 2: Database schema and seed (M2)

**Files:**
- Create: `database/schema.sql`
- Create: `database/seed.php`
- Create: `app/Models/` repositories as needed for seeding (or seed with plain PDO)

**Interfaces:**
- `schema.sql` is idempotent enough to run on a fresh `newjeans` database.
- `seed.php` runs from CLI: `php database/seed.php`; reads `assets/data/news.json`, `assets/js/schedule.js` content is ported manually into arrays, and static markup from `pages/discography.html` / `pages/about.html` / `index.html` is transcribed into seed arrays.

- [x] Write `schema.sql` exactly matching the Phase 1 Schema Contract, including engine, charset, indexes, and FKs.
- [x] Create the `newjeans` database and import `schema.sql`; confirm all 9 tables exist.
- [x] Seed `album_types`: `mini-album`, `single`, `collab`.
- [x] Seed `albums` + `songs` from `pages/discography.html`: How Sweet, Get Up, OMG, NewJeans (Debut), Supernatural, Gods, Zero. Include track order, title flags, Spotify/YouTube URLs, and cover paths relative to `assets/`.
- [x] Seed Top 3 featured songs from `index.html`: Hype Boy, Super Shy, ETA (`is_featured = 1`).
- [x] Seed `news` from `assets/data/news.json` (9 records): map `published_at`, `source_name`, `source_url`, `status = 'published'`, and `deleted_at = NULL`.
- [x] Seed `events` from `assets/js/schedule.js` (12 records): convert `date` + `time` to `event_date`; preserve `poster` and `description`.
- [x] Seed `members` and `histories` from `pages/about.html` (4 members, 4 timeline entries).
- [x] Seed `gallery_items` for `gallery1.jpg` … `gallery12.jpg` with `sort_order` 1–12 and alt text.
- [x] Seed one admin user (`admin@newjeans.local`); print the generated password once to console or document it in `README.md`.
- [x] Verify row counts per table and spot-check one joined album/song query.

**Acceptance:** Fresh import + seed succeeds; counts match source data; public queries can join albums→songs and albums→album_types.

---

### Task 3: Auth and admin shell (M3)

**Files:**
- Create: `app/Core/Auth.php`, `app/Core/Csrf.php`, `app/Core/Validator.php`, `app/Core/Upload.php`
- Create: `app/Controllers/Admin/AuthController.php`, `app/Controllers/Admin/DashboardController.php`
- Create: `templates/layouts/admin.php`, `templates/admin/login.php`, `templates/admin/dashboard.php`
- Create: `public/assets/css/admin.css` (AdminLTE overrides only; no public theme tokens)
- Vendor: `public/assets/vendor/{adminlte,bootstrap,bootstrap-icons,overlayscrollbars}`
- Modify: `public/index.php` routes, `app/Core/Router.php` (pass `$_GET`/`$_POST` into `Request`)

**Interfaces:**
- `Auth::attempt(string $email, string $password): bool`, `Auth::check(): bool`, `Auth::user(): ?array`, `Auth::logout(): void`, `Auth::requireAdmin(): void`.
- `Csrf::token(): string`, `Csrf::validate(?string $token): bool`.
- `Validator::make(array $data, array $rules): array` returning errors.
- `Upload::store(array $file, string $dir): string` returning a relative path.

- [x] Implement session bootstrap with `session_start`, `httponly`, `samesite=Lax`, and session regeneration on login.
- [x] Implement `Auth` with `password_verify` and admin-role check.
- [x] Implement CSRF token generation/validation and render hidden inputs in forms.
- [x] Build AdminLTE 4 admin layout + login form + logout route.
- [x] Protect `/admin/*` with `Auth::requireAdmin()`; redirect guests to `/login`.
- [x] Build dashboard with counts for each content table.
- [x] Verify wrong password, correct login, logout, and direct `/admin` access are all handled.

**Acceptance:** Only an authenticated admin can reach `/admin`; CSRF rejection returns a safe error; no password is ever rendered.

---

### Task 4: Admin CRUD (M4)

**Files:**
- Create controllers: `app/Controllers/Admin/AlbumTypeController.php`, `AlbumController.php`, `SongController.php`, `NewsController.php`, `EventController.php`, `MemberController.php`, `HistoryController.php`, `GalleryController.php`
- Create models/repositories under `app/Models/`
- Create templates under `templates/admin/{album-types,albums,songs,news,events,members,histories,gallery}/`
- Modify: routes in `public/index.php`

**Interfaces:**
- Each module supports: list (exclude soft-deleted by default), create form, store, edit form, update, soft delete.
- Slug is auto-generated from title/name and made unique (`slug`, `slug-2`, …).
- Upload fields map to: `albums.cover_album`, `news.thumbnail`, `events.poster`, `members.photo`, `histories.image`, `gallery_items.image`.

- [x] Implement `album_types` CRUD (name + slug).
- [x] Implement `albums` CRUD (type, name, cover upload, release date, Spotify/YouTube, price, stock, for-sale).
- [x] Implement `songs` CRUD scoped to an album (title, track number, title flag, featured flag, audio URL).
- [x] Implement `news` CRUD (slug, title, thumbnail, content, status, published_at, source fields).
- [x] Implement `events` CRUD (slug, title, venue, city, event date, poster, description).
- [x] Implement `members` CRUD (name, photo, roles, description).
- [x] Implement `histories` CRUD (title, image, description, event date).
- [x] Implement `gallery_items` CRUD (image, alt text, caption, sort order).
- [x] Add server-side validation for required fields, numeric ranges, dates, URLs, and uploads.
- [x] Add flash success/error messages and preserve old input on validation failure.
- [x] Add delete confirmation + soft delete; ensure deleted rows disappear from public queries.
- [x] Verify each module end-to-end (create → list → edit → delete) with an uploaded image.

**Acceptance:** Every content table is fully manageable from the admin panel; invalid input never writes partial rows.

---

### Task 5: Public server-rendered pages (M5)

**Files:**
- Create controllers: `app/Controllers/Public/HomeController.php`, `DiscographyController.php`, `ScheduleController.php`, `NewsController.php`, `AboutController.php`, `GalleryController.php`
- Create templates: `templates/layouts/public.php`, `templates/partials/{header,footer,flash,news-card,event-card}.php`, `templates/pages/{home,discography,schedule,news-list,news-article,about,gallery,404}.php`
- Modify: `public/index.php` routes

**Interfaces:**
- Routes: `GET /`, `/discography`, `/schedule`, `/news`, `/news/{slug}`, `/about`, `/gallery`.
- Controllers fetch data via repositories and pass arrays to `View`.
- Templates reuse existing CSS class names and markup structure from the current HTML.

- [ ] Replace `components.js` navbar/footer injection with `partials/header.php` + `partials/footer.php`; set active nav from the current route.
- [ ] Home: render featured songs (limit 3), gallery preview, and latest published news.
- [ ] Discography: render all albums with type badge, release date, track list, title flag, Spotify/YouTube buttons, and `data-type`/`data-year`.
- [ ] Schedule: render upcoming events sorted by `event_date`; keep the countdown and search/sort UI (server-side GET preferred).
- [ ] News list: render featured + grid using the same filtering rules as `news-data.js` (published, not deleted, not future).
- [ ] News article: render by slug, with 404 template when missing/unpublished.
- [ ] About: render histories timeline and members grid from DB.
- [ ] Gallery: render items ordered by `sort_order`; keep lightbox structure.
- [ ] Add `<title>`/meta description per route and a 404 route.
- [ ] Verify every page renders with seeded data and with empty tables (graceful empty states).

**Acceptance:** All public pages are DB-driven; no page depends on `assets/data/news.json` or hardcoded content arrays.

---

### Task 6: Frontend JS refactor and link migration (M6)

**Files:**
- Modify: `public/assets/js/discography.js`, `schedule.js`, `gallery-controls.js`, `home.js`, `components.js`, `news.js`, `news-data.js`
- Modify: all templates to load only required scripts

**Interfaces:**
- `discography.js` continues to read `.release-row` from the DOM (no data source change).
- `schedule.js` no longer defines `scheduleData`; it reads rendered `.wf-card` nodes or submits GET params.
- `gallery-controls.js` derives image list/indexes from rendered DOM data attributes instead of `totalGalleryImages = 12`.
- `news.js`/`news-data.js` are removed or reduced to progressive enhancement; news is server-rendered.
- `home.js` operates on server-rendered news cards.

- [x] Update `discography.js` only if markup attributes change; confirm filters/search/sort still work.
- [x] Refactor `schedule.js` to remove the hardcoded array and operate on server-rendered cards or server-side GET state.
- [x] Refactor `gallery-controls.js` to read image sources/indexes from the DOM; remove the fixed 12-image assumption.
- [x] Update `home.js` to work with server-rendered news cards (keep looping/clone behavior if needed).
- [x] Remove `news-data.js` and `assets/data/news.json` references from templates; keep the file in `archive/` if it must be preserved.
- [x] Update every internal link from `*.html` to the new route (or add `.html → route` redirects).
- [x] Confirm Locomotive Scroll, theme toggle, track carousel, and lightbox still initialize without console errors.

**Acceptance:** No 404s in the browser console; filters, sort, lightbox, carousel, and theme still work.

---

### Task 7: QA and documentation (M7)

**Files:**
- Modify: `README.md`
- Create/Modify: `docs/superpowers/plans/2026-11-09-php-backend-content.md` (update Progress Tracker only)
- Optional: `database/README.md`

- [x] Run `php -l` on every PHP file under `app/`, `config/`, `templates/`, `public/`, `database/`.
- [x] Import schema + seed on a clean database and boot `php -S localhost:8000 -t public`.
- [x] Smoke-test public routes: `/`, `/discography`, `/schedule`, `/news`, `/news/{slug}`, `/about`, `/gallery`, unknown route.
- [x] Smoke-test admin: login, create/edit/delete each module, upload image, logout, guest redirect.
- [x] Test empty-state behavior by temporarily querying an empty table.
- [x] Verify soft-deleted rows are hidden publicly and still present in DB.
- [x] Update `README.md` with prerequisites, `.env` setup, DB import, seeding, run command, admin credentials, and route list.
- [x] Update the Progress Tracker to mark milestones `done`.

**Acceptance:** A fresh clone can be set up and run using only the README instructions.

---

## Manual Review Checklist

- [ ] Home, Discography, Schedule, News, About, Gallery visually match the previous static site.
- [ ] Discography search/filter/sort returns correct results from DB data.
- [ ] Schedule search/sort and countdown work with DB events.
- [ ] News article deep-link works and unpublished/deleted slugs return 404.
- [ ] Gallery lightbox navigates the full DB-backed image set.
- [ ] Admin CRUD writes, updates, and soft-deletes correctly.
- [ ] Uploads reject non-images and oversized files.
- [ ] No secrets, `.env`, or `storage/` files are reachable over HTTP.
- [ ] All PHP files pass `php -l`.

---

## Out of Scope (Phase 2)

Do not implement now:
- Cart, checkout, orders, shipping options, payment proof upload/verification, order status history.
- Customer registration/login (admin-only auth is enough for Phase 1).
- Public REST/JSON API.
- Email notifications, payment gateway, shipping integration.
- Automated test suite (manual smoke testing only in Phase 1).

When Phase 2 starts, add the deferred tables plus `orders.recipient_name`, `orders.phone`, `order_status_histories.changed_by`, and `cart_items UNIQUE (cart_id, album_id)`.
