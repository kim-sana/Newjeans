# News Highlights Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a frontend News list and article view powered by one JSON fixture shared with the Home highlight carousel.

**Architecture:** `data/news.json` stores records matching the planned news table. A shared `loadNews()` helper fetches and filters public records; `news.html` renders the list or one article based on `?slug=`, while Home uses the same helper for its carousel. No backend or API is added.

**Tech Stack:** Static HTML, CSS, vanilla JavaScript, JSON.

**Spec:** `docs/superpowers/specs/2026-10-29-news-highlights-design.md`

## Global Constraints

- Keep this project frontend-only: local JSON is the initial data source; no database or API implementation.
- Display records only when `status` is `published`, `deleted_at` is null, and `published_at` is not in the future.
- Render `content` as text, never inject it as HTML.
- Reuse the existing Home highlight entries; do not add headlines or factual claims.
- Follow `docs/design.md` for colors, typography, cards, spacing, responsive layout, and motion.

---

### Task 1: Add the shared news data source

**Files:**
- Create: `data/news.json`
- Create: `js/news-data.js`

**Interfaces:**
- Produces `window.NewsData.loadNews(): Promise<NewsRecord[]>` for Home and the News page.
- `NewsRecord` fields: `id`, `slug`, `title`, `thumbnail`, `content`, `status`, `created_at`, `published_at`, `updated_at`, `deleted_at`.

- [ ] Create a JSON array with the nine current Home highlight items. Preserve their existing headlines and thumbnail paths. Use unique lowercase hyphenated slugs, IDs 1–9, `status: "published"`, empty `content`, `deleted_at: null`, and ISO timestamps converted from the existing dates.
- [ ] Add `loadNews()`. Fetch `data/news.json` with same-origin credentials, reject non-OK responses with a useful error, parse the array, discard records missing `slug`, `title`, or a valid `published_at`, then filter to published, non-deleted records whose publication date is not in the future.
- [ ] Sort the returned records by `published_at` descending and expose the helper as `window.NewsData.loadNews` so both classic scripts can use it.
- [ ] Format dates in the page layer with `Intl.DateTimeFormat("en-US", { dateStyle: "medium" })` to match the site's existing English copy.

### Task 2: Create the News list and article view

**Files:**
- Create: `news.html`
- Create: `css/news.css`
- Create: `js/news.js`

**Interfaces:**
- Consumes `window.NewsData.loadNews()` from Task 1.
- List route: `news.html`; article route: `news.html?slug=<slug>`.

- [ ] Build `news.html` from the existing root-page pattern: skip link, shared navbar/footer placeholders, page hero, News content root, theme stylesheet, `css/news.css`, then scripts for components, `js/news-data.js`, and `js/news.js`.
- [ ] In `js/news.js`, call `loadNews()` once. Without a slug, render a responsive grid of cards with thumbnail, title, and publication date. Set card links with `URLSearchParams` so each opens its matching slug.
- [ ] With a known slug, render article title, thumbnail, formatted publication date, and `content` via `textContent`; preserve line breaks. If `content` is empty, show the spec's neutral “Full article coming soon” state.
- [ ] Render explicit empty, unknown-slug, load-error, and missing-thumbnail states. Include a link back to the Home page for errors and a link back to the News list for article view.
- [ ] Use DOM APIs and `textContent` for all JSON-backed values. Do not interpolate record values into `innerHTML`.
- [ ] Style using existing theme tokens, blue-and-white palette, Archivo display headings, DM Sans body copy, bordered cards, and an auto-fit grid with a 260px minimum card width. Keep layouts usable on narrow screens and honor reduced motion.

### Task 3: Connect Home, navigation, and documentation

**Files:**
- Modify: `index.html`
- Modify: `js/home.js`
- Modify: `js/components.js`
- Modify: `README.md`

**Interfaces:**
- Home consumes `window.NewsData.loadNews()` from Task 1.
- Navigation and “See all news” link to `news.html`.

- [ ] Add `News` to the shared navbar and add `'news.html': 'news'` to the active-page map in `js/components.js`.
- [ ] Change the Home section's “See all news” link to `news.html` and load `js/news-data.js` before `js/home.js` in `index.html`.
- [ ] Replace the hard-coded array in `js/home.js` with the shared helper. Preserve the existing carousel motion and hover pause, duplicate the ordered items only for the looping presentation, remove the unsupported tag field, and link each card to `news.html?slug=<slug>`.
- [ ] In `README.md`, state that Home news and News articles fetch local JSON and therefore must be opened through the documented static server, not directly through `file://`.
- [ ] Inspect the final diff to confirm only planned files changed for this feature and that the previously existing `discography.html`, `index.html`, and `web-page/about.html` edits remain intact.

## Manual Review

Serve the project with the README command (`python -m http.server 8000`) and inspect `http://localhost:8000/news.html` and `http://localhost:8000/index.html`. Confirm the list and Home carousel use the same items, a card opens its slug view, and the empty-content, unknown-slug, missing-image, and fetch-error states are readable. No automated test suite is added for this static-site change.
