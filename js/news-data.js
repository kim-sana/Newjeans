/* Shared loader for the frontend news JSON fixture. */
(function (global) {
  async function loadNews() {
    const response = await fetch("data/news.json", {
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

        const publishedAt = Date.parse(record.published_at);
        return (
          record.status === "published" &&
          record.deleted_at == null &&
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
