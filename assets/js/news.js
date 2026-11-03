/* News list and article view, sourced from the shared frontend dataset. */
document.addEventListener("DOMContentLoaded", () => {
  const contentRoot = document.getElementById("newsContent");
  const viewTitle = document.getElementById("newsViewTitle");
  const viewHead = document.getElementById("newsViewHead");
  const countLabel = document.getElementById("newsCount");
  const metaDescription = document.querySelector('meta[name="description"]');
  if (!contentRoot || !viewTitle) return;

  const assetBase = window.SitePaths?.assets || "../assets/";
  const siteBase = window.SitePaths?.site || "../";
  const newsIndexHref = `${siteBase}pages/news.html`;
  const homeHref = `${siteBase}index.html`;
  const listTitle = "News — NewJeans";
  const listDescription =
    "Verified NewJeans milestones, chart history, and stories from the Bunnies universe.";

  const dateFormatter = new Intl.DateTimeFormat("en-US", {
    dateStyle: "medium",
  });

  function setDocumentMeta(title, description = listDescription) {
    document.title = title;
    if (metaDescription) {
      metaDescription.setAttribute("content", description);
    }
  }

  function createDate(value, className) {
    const date = new Date(value);
    const time = document.createElement("time");
    time.className = className;
    time.dateTime = date.toISOString();
    time.textContent = dateFormatter.format(date);
    return time;
  }

  function createImage(src, alt, className, { eager = false } = {}) {
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
    image.src = `${assetBase}${src}`;
    image.alt = alt;
    image.loading = eager ? "eager" : "lazy";
    image.decoding = "async";
    image.addEventListener(
      "error",
      () => {
        const fallback = document.createElement("span");
        fallback.className = "news-media-fallback";
        fallback.textContent = "Image unavailable";
        frame.replaceChildren(fallback);
      },
      { once: true },
    );
    frame.appendChild(image);
    return frame;
  }

  function createExcerpt(content, maxLength = 150) {
    if (typeof content !== "string") return "";
    const clean = content.replace(/\s+/g, " ").trim();
    if (!clean) return "";
    if (clean.length <= maxLength) return clean;

    const cut = clean.slice(0, maxLength);
    const lastSpace = cut.lastIndexOf(" ");
    return `${cut.slice(0, lastSpace > 0 ? lastSpace : maxLength).trim()}…`;
  }

  function createParagraphs(content) {
    const fragment = document.createDocumentFragment();
    const blocks = String(content)
      .split(/\n\s*\n/)
      .map((block) => block.trim())
      .filter(Boolean);

    if (blocks.length === 0) {
      const paragraph = document.createElement("p");
      paragraph.setAttribute("class", "news-article-empty-copy");
      paragraph.textContent = "Full article coming soon.";
      fragment.appendChild(paragraph);
      return fragment;
    }

    blocks.forEach((block) => {
      const paragraph = document.createElement("p");
      paragraph.textContent = block.replace(/\n+/g, " ");
      fragment.appendChild(paragraph);
    });

    return fragment;
  }

  function readingTime(content) {
    const words = String(content).trim().split(/\s+/).filter(Boolean).length;
    return Math.max(1, Math.round(words / 200));
  }

  function createAction(label, href, className = "news-state-link") {
    const link = document.createElement("a");
    link.className = className;
    link.href = href;
    link.textContent = label;
    return link;
  }

  function createSource(story) {
    if (typeof story.source_url !== "string" || !story.source_url.trim()) {
      return null;
    }

    let sourceUrl;
    try {
      sourceUrl = new URL(story.source_url);
    } catch {
      return null;
    }

    if (sourceUrl.protocol !== "https:" && sourceUrl.protocol !== "http:") {
      return null;
    }

    const source = document.createElement("p");
    source.className = "news-article-source";
    source.append("Source: ");

    const link = document.createElement("a");
    link.href = sourceUrl.href;
    link.target = "_blank";
    link.rel = "noopener noreferrer";
    link.textContent =
      typeof story.source_name === "string" && story.source_name.trim()
        ? story.source_name.trim()
        : "Read the original report";
    source.appendChild(link);
    return source;
  }

  function createNewsLink(story) {
    const params = new URLSearchParams({ slug: story.slug });
    return `${newsIndexHref}?${params.toString()}`;
  }

  function createState(title, message, action) {
    const state = document.createElement("section");
    state.className = "news-state";
    state.setAttribute("role", "status");

    const heading = document.createElement("h3");
    heading.textContent = title;
    const description = document.createElement("p");
    description.textContent = message;

    state.append(heading, description);
    if (action) {
      state.appendChild(createAction(action.label, action.href));
    }
    return state;
  }

  function createLoading() {
    const state = document.createElement("section");
    state.className = "news-state news-state--loading";
    state.setAttribute("role", "status");

    const heading = document.createElement("h3");
    heading.textContent = "Loading stories";
    const description = document.createElement("p");
    description.textContent = "Fetching the latest highlights.";

    state.append(heading, description);
    return state;
  }

  function createCard(story, index = 0) {
    const card = document.createElement("article");
    card.className = "news-list-card";
    card.style.animationDelay = `${Math.min(index, 6) * 45}ms`;

    const link = document.createElement("a");
    link.className = "news-card-link";
    link.href = createNewsLink(story);
    link.setAttribute("aria-label", `Read story: ${story.title}`);

    const media = createImage(story.thumbnail, story.title, "news-card-media");
    const body = document.createElement("div");
    body.className = "news-card-body";

    const date = createDate(story.published_at, "news-card-date");
    const title = document.createElement("h3");
    title.className = "news-card-title";
    title.textContent = story.title;
    const excerpt = document.createElement("p");
    excerpt.className = "news-card-excerpt";
    excerpt.textContent = createExcerpt(story.content, 140);
    const cta = document.createElement("span");
    cta.className = "news-card-cta";
    cta.textContent = "Read story";

    body.append(date, title, excerpt, cta);
    link.append(media, body);
    card.appendChild(link);
    return card;
  }

  function createFeatured(story) {
    const article = document.createElement("article");
    article.className = "news-featured";

    const link = document.createElement("a");
    link.className = "news-featured-link";
    link.href = createNewsLink(story);
    link.setAttribute("aria-label", `Read top story: ${story.title}`);

    const media = createImage(
      story.thumbnail,
      story.title,
      "news-featured-media",
      { eager: true },
    );
    const body = document.createElement("div");
    body.className = "news-featured-body";

    const badge = document.createElement("span");
    badge.className = "news-featured-badge";
    badge.textContent = "Top story";
    const title = document.createElement("h3");
    title.className = "news-featured-title";
    title.textContent = story.title;
    const excerpt = document.createElement("p");
    excerpt.className = "news-featured-excerpt";
    excerpt.textContent = createExcerpt(story.content, 210);

    const meta = document.createElement("div");
    meta.className = "news-featured-meta";
    const date = createDate(story.published_at, "news-featured-date");
    const cta = document.createElement("span");
    cta.className = "news-featured-cta";
    cta.textContent = "Read story";
    meta.append(date, cta);

    body.append(badge, title, excerpt, meta);
    link.append(media, body);
    article.appendChild(link);
    return article;
  }

  function renderList(stories) {
    if (viewHead) viewHead.hidden = false;
    viewTitle.textContent = "All stories";
    if (countLabel) {
      countLabel.textContent =
        stories.length === 1 ? "1 story" : `${stories.length} stories`;
    }
    setDocumentMeta(listTitle);

    if (stories.length === 0) {
      contentRoot.replaceChildren(
        createState(
          "No published highlights yet",
          "Check back when the next story is published.",
        ),
      );
      contentRoot.setAttribute("aria-busy", "false");
      return;
    }

    const [featured, ...rest] = stories;
    const fragment = document.createDocumentFragment();
    fragment.appendChild(createFeatured(featured));

    if (rest.length > 0) {
      const grid = document.createElement("div");
      grid.className = "news-list-grid";
      rest.forEach((story, index) => {
        grid.appendChild(createCard(story, index));
      });
      fragment.appendChild(grid);
    }

    contentRoot.replaceChildren(fragment);
    contentRoot.setAttribute("aria-busy", "false");
  }

  function renderRelated(stories, currentSlug) {
    const related = stories
      .filter((story) => story.slug !== currentSlug)
      .slice(0, 3);
    if (related.length === 0) return null;

    const section = document.createElement("section");
    section.className = "news-related";

    const head = document.createElement("div");
    head.className = "news-related-head";
    const heading = document.createElement("h2");
    heading.textContent = "More stories";
    head.appendChild(heading);

    const grid = document.createElement("div");
    grid.className = "news-list-grid";
    related.forEach((story, index) => {
      grid.appendChild(createCard(story, index));
    });

    section.append(head, grid);
    return section;
  }

  function renderArticle(story, stories) {
    if (viewHead) viewHead.hidden = true;
    if (countLabel) countLabel.textContent = "";
    setDocumentMeta(
      `${story.title} — NewJeans`,
      createExcerpt(story.content, 155),
    );

    const article = document.createElement("article");
    article.className = "news-article";

    const backLink = createAction(
      "← All stories",
      newsIndexHref,
      "news-back-link",
    );

    const kicker = document.createElement("p");
    kicker.className = "eyebrow news-article-kicker";
    kicker.textContent = "News story";

    const title = document.createElement("h2");
    title.className = "news-article-title";
    title.textContent = story.title;

    const meta = document.createElement("div");
    meta.className = "news-article-meta";
    const date = createDate(story.published_at, "news-article-date");
    const dot = document.createElement("span");
    dot.className = "news-article-meta-dot";
    dot.setAttribute("aria-hidden", "true");
    const read = document.createElement("span");
    read.textContent = `${readingTime(story.content)} min read`;
    meta.append(date, dot, read);

    const media = createImage(
      story.thumbnail,
      story.title,
      "news-article-media",
      { eager: true },
    );
    const body = document.createElement("div");
    body.className = "news-article-content";
    body.appendChild(createParagraphs(story.content));

    article.append(backLink, kicker, title, meta, media, body);

    // const source = createSource(story);
    // if (source) article.appendChild(source);

    const footer = document.createElement("div");
    footer.className = "news-article-footer";
    footer.appendChild(
      createAction("← Back to all stories", newsIndexHref, "news-back-link"),
    );
    article.appendChild(footer);

    const related = renderRelated(stories, story.slug);
    contentRoot.replaceChildren(article);
    if (related) contentRoot.appendChild(related);
    contentRoot.setAttribute("aria-busy", "false");
  }

  async function renderNews() {
    contentRoot.setAttribute("aria-busy", "true");
    contentRoot.replaceChildren(createLoading());

    try {
      const stories = await window.NewsData.loadNews();
      const slug = new URLSearchParams(window.location.search).get("slug");

      if (slug !== null) {
        const story = stories.find((item) => item.slug === slug);
        if (!story) {
          if (viewHead) viewHead.hidden = false;
          viewTitle.textContent = "Story not found";
          if (countLabel) countLabel.textContent = "";
          setDocumentMeta("Story not found — NewJeans");
          contentRoot.replaceChildren(
            createState(
              "This story isn't available",
              "It may have been removed or unpublished.",
              { label: "Back to News", href: newsIndexHref },
            ),
          );
          contentRoot.setAttribute("aria-busy", "false");
          return;
        }

        renderArticle(story, stories);
        return;
      }

      renderList(stories);
    } catch {
      if (viewHead) viewHead.hidden = false;
      viewTitle.textContent = "News unavailable";
      if (countLabel) countLabel.textContent = "";
      setDocumentMeta("News unavailable — NewJeans");
      contentRoot.replaceChildren(
        createState("News couldn't load", "Reload the page to try again.", {
          label: "Back to Home",
          href: homeHref,
        }),
      );
      contentRoot.setAttribute("aria-busy", "false");
    }
  }

  renderNews();
});
