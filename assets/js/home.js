/* Home page: highlight news carousel. */
document.addEventListener("DOMContentLoaded", () => {
  const assetBase = window.SitePaths?.assets || "assets/";
  const siteBase = window.SitePaths?.site || "./";

  /* ------------------------------------------------------------------------
     Highlight news carousel
     ------------------------------------------------------------------------ */
  const newsTrack = document.getElementById("newsTrack");

  if (newsTrack) {
    const dateFormatter = new Intl.DateTimeFormat("en-US", {
      dateStyle: "medium",
    });

    window.NewsData.loadNews()
      .then((stories) => {
        if (stories.length === 0) {
          newsTrack.textContent = "No published highlights yet.";
          return;
        }

        [...stories, ...stories].forEach((story) => {
          const card = document.createElement("article");
          card.className = "news-card";

          const link = document.createElement("a");
          link.href = `${siteBase}pages/news.html?slug=${encodeURIComponent(story.slug)}`;
          link.setAttribute("aria-label", `Read story: ${story.title}`);
          link.style.display = "block";
          link.style.color = "inherit";
          link.style.textDecoration = "none";

          const media = document.createElement("div");
          media.className = "news-card-media";

          function showImageFallback() {
            const fallback = document.createElement("span");
            fallback.className = "news-media-fallback";
            fallback.textContent = "Image unavailable";
            media.replaceChildren(fallback);
          }

          if (typeof story.thumbnail === "string" && story.thumbnail.trim()) {
            const image = document.createElement("img");
            image.src = `${assetBase}${story.thumbnail}`;
            image.alt = story.title;
            image.loading = "lazy";
            image.addEventListener("error", showImageFallback, { once: true });
            media.appendChild(image);
          } else {
            showImageFallback();
          }

          const body = document.createElement("div");
          body.className = "news-card-body";

          const headline = document.createElement("p");
          headline.className = "news-card-headline";
          headline.textContent = story.title;

          const publishedAt = new Date(story.published_at);
          const date = document.createElement("time");
          date.className = "news-card-date";
          date.dateTime = publishedAt.toISOString();
          date.textContent = dateFormatter.format(publishedAt);

          body.append(headline, date);
          link.append(media, body);
          card.appendChild(link);
          newsTrack.appendChild(card);
        });
      })
      .catch(() => {
        newsTrack.textContent = "News unavailable.";
      });
  }
});
