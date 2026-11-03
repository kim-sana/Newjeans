/* Shared loader for the frontend news JSON fixture. */
(function (global) {
  const scriptUrl = global.document.currentScript?.src;
  const scriptVersion = scriptUrl
    ? new URL(scriptUrl).searchParams.get("v")
    : null;
  const assetBase =
    global.SitePaths?.assets ||
    (scriptUrl ? new URL("../", scriptUrl).href : "assets/");
  const dataQuery = scriptVersion
    ? `?v=${encodeURIComponent(scriptVersion)}`
    : "";

  async function loadNews() {
    const response = await fetch(`${assetBase}data/news.json${dataQuery}`, {
      credentials: "same-origin",
    });

    if (!response.ok) {
      throw new Error(`News data request failed (${response.status}).`);
    }

    const records = await response.json();
    if (!Array.isArray(records)) {
      throw new Error("News data must be a JSON array.");
    }

    const now = Date.now();

    return records
      .filter((record) => {
        if (!record || typeof record !== "object") return false;
        if (typeof record.slug !== "string" || !record.slug.trim()) return false;
        if (typeof record.title !== "string" || !record.title.trim()) return false;
        if (typeof record.thumbnail !== "string" || !record.thumbnail.trim()) {
          return false;
        }
        if (typeof record.content !== "string" || !record.content.trim()) {
          return false;
        }

        const publishedAt = Date.parse(record.published_at);
        return (
          record.status === "published" &&
          record.deleted_at === null &&
          Number.isFinite(publishedAt) &&
          publishedAt <= now
        );
      })
      .sort(
        (a, b) => Date.parse(b.published_at) - Date.parse(a.published_at),
      );
  }

  global.NewsData = Object.freeze({ loadNews });
})(window);
