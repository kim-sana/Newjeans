# News Highlights Page Design

## Goal

Add a News page that presents highlight articles from the planned `news` table schema. Keep this project frontend-only: the initial data source is a local JSON file, with no database or API implementation.

## Scope

- Add `news.html` with a responsive news list and an article view selected by `?slug=<slug>`.
- Read the same JSON dataset from the Home highlight carousel and News page.
- Link Home's “See all news” and news cards to the new page; add News to the shared navigation and active-page handling.
- Follow `docs/design.md` for colors, typography, cards, spacing, responsive layout, and motion.
- Update README run instructions to use the static server for pages that fetch JSON.

## Data contract

`data/news.json` contains an array of records matching the planned table:

```json
{
  "id": 1,
  "slug": "example-news-slug",
  "title": "Example headline",
  "thumbnail": "images/gallery/gallery1.jpg",
  "content": "Article body as plain text.",
  "status": "published",
  "created_at": "2026-10-01T09:00:00Z",
  "published_at": "2026-10-01T09:00:00Z",
  "updated_at": "2026-10-01T09:00:00Z",
  "deleted_at": null
}
```

The frontend displays records only when `status` is `published`, `deleted_at` is null, and `published_at` is not in the future. It sorts newest first by `published_at`. `thumbnail` is a project-root-relative path. `content` is rendered as text, never injected as HTML.

The JSON fixture will reuse the existing Home highlight entries rather than add new headlines. Since those entries currently have no article body, their detail view may show the existing title and a neutral “Full article coming soon” state until real `content` values are supplied. This keeps demo content explicit and avoids inventing article copy.

## Page behavior

- Without a `slug`, `news.html` shows the page heading and a card grid. Cards show thumbnail, title, and formatted `published_at`; the card links to the same page with the article's slug.
- With a known `slug`, the page shows the article title, thumbnail, publication date, and plain-text content, with a link back to the news list.
- An unknown slug, empty published dataset, or JSON load failure gets a clear in-page state and a route back to Home.
- The Home carousel uses the same filtered and sorted records, retaining its horizontal animation and pausing on hover.
- Use DOM creation and `textContent` for data-driven fields to prevent JSON strings from being interpreted as markup.
- A missing `thumbnail` gets a styled fallback rather than a broken image.

## Structure

- `data/news.json`: frontend fixture using the database-shaped fields above.
- `js/news-data.js`: shared load, validate, filter, and sort helper for the fixture.
- `news.html`, `css/news.css`, `js/news.js`: list and article states.
- `js/home.js`: replace the hard-coded news array with the shared data helper.
- `js/components.js` and `index.html`: navigation, Home “See all news,” and article links.
- `README.md`: explain that the static server is required for JSON fetch.

The JSON path is isolated in the shared data helper. A later API integration can replace the loader without changing the page rendering contract.

## Out of scope

- Backend, database connection, authentication, admin editing, pagination, search, categories, and API endpoints.
- Changing the contents or factual claims of the existing highlight headlines.

## Acceptance criteria

1. Home and News page render from one JSON source.
2. Draft, deleted, and future-dated records do not appear publicly.
3. Each published card opens its article through its slug; invalid slugs and load errors have usable fallback states.
4. The list and article layouts follow `docs/design.md` and remain usable on narrow screens.
5. JSON text is rendered safely, and the README gives a working local-server instruction.
