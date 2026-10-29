/* News list and article view, sourced from the shared frontend dataset. */
document.addEventListener("DOMContentLoaded", () => {
  const contentRoot = document.getElementById("newsContent");
  const viewTitle = document.getElementById("newsViewTitle");
  if (!contentRoot || !viewTitle) return;

  const dateFormatter = new Intl.DateTimeFormat("en-US", {
    dateStyle: "medium",
  });

  function createDate(value, className) {
    const date = new Date(value);
    const time = document.createElement("time");
    time.className = className;
    time.dateTime = date.toISOString();
    time.textContent = dateFormatter.format(date);
    return time;
  }

  function createImage(src, alt, className) {
    const frame = document.createElement("div");
    frame.className = className;

    if (typeof src !== "string" || !src.trim()) {
      const fallback = document.createElement("span");
      fallback.className = "news-media-fallback";
      fallback.textContent = "Image unavailable";
      frame.appendChild(fallback);
      return frame;
    }

    const image = document.createElement("img");
    image.src = src;
    image.alt = alt;
    image.loading = "lazy";
    image.addEventListener("error", () => {
      const fallback = document.createElement("span");
      fallback.className = "news-media-fallback";
      fallback.textContent = "Image unavailable";
      frame.replaceChildren(fallback);
    }, { once: true });
    frame.appendChild(image);
    return frame;
  }

  function createHomeLink(label) {
    const link = document.createElement("a");
    link.className = "news-state-link";
    link.href = "index.html";
    link.textContent = label;
    return link;
  }

  function createState(title, message, actionLabel = "Back to Home") {
    const state = document.createElement("section");
    state.className = "news-state";
    state.setAttribute("role", "status");

    const heading = document.createElement("h3");
    heading.textContent = title;
    const description = document.createElement("p");
    description.textContent = message;

    state.append(heading, description, createHomeLink(actionLabel));
    return state;
  }

  function createNewsLink(story) {
    const params = new URLSearchParams({ slug: story.slug });
    return `news.html?${params.toString()}`;
  }

  function renderList(stories) {
    viewTitle.textContent = "All stories";

    if (stories.length === 0) {
      contentRoot.replaceChildren(
        createState("No published highlights yet", "Check back when the next story is published."),
      );
      return;
    }

    const grid = document.createElement("div");
    grid.className = "news-list-grid";
    stories.forEach((story) => {
      const card = document.createElement("article");
      card.className = "news-list-card";

      const link = document.createElement("a");
      link.className = "news-card-link";
      link.href = createNewsLink(story);

      const media = createImage(story.thumbnail, story.title, "news-card-media");
      const body = document.createElement("div");
      body.className = "news-card-body";

      const title = document.createElement("h3");
      title.className = "news-card-title";
      title.textContent = story.title;

      body.append(title, createDate(story.published_at, "news-card-date"));
      link.append(media, body);
      card.appendChild(link);
      grid.appendChild(card);
    });

    contentRoot.replaceChildren(grid);
  }

  function renderArticle(story) {
    viewTitle.textContent = "Story";

    const article = document.createElement("article");
    article.className = "news-article";

    const backLink = document.createElement("a");
    backLink.className = "news-back-link";
    backLink.href = "news.html";
    backLink.textContent = "← All stories";

    const title = document.createElement("h2");
    title.className = "news-article-title";
    title.textContent = story.title;

    const media = createImage(story.thumbnail, story.title, "news-article-media");
    const body = document.createElement("div");
    body.className = "news-article-content";
    if (typeof story.content === "string" && story.content.trim()) {
      body.textContent = story.content;
    } else {
      body.classList.add("news-article-empty-copy");
      body.textContent = "Full article coming soon.";
    }

    article.append(
      backLink,
      title,
      createDate(story.published_at, "news-article-date"),
      media,
      body,
    );
    contentRoot.replaceChildren(article);
  }

  async function renderNews() {
    try {
      const stories = await window.NewsData.loadNews();
      const slug = new URLSearchParams(window.location.search).get("slug");

      if (slug !== null) {
        const story = stories.find((item) => item.slug === slug);
        if (!story) {
          viewTitle.textContent = "Story not found";
          contentRoot.replaceChildren(
            createState("This story isn't available", "It may have been removed or unpublished.", "Back to News"),
          );
          contentRoot.querySelector(".news-state-link").href = "news.html";
          return;
        }

        renderArticle(story);
        return;
      }

      renderList(stories);
    } catch {
      viewTitle.textContent = "News unavailable";
      contentRoot.replaceChildren(
        createState("News couldn't load", "Reload the page to try again."),
      );
    }
  }

  renderNews();
});
