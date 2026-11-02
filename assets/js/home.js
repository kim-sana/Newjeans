/* Home page: gallery coverflow, lightbox and highlight news carousel. */
document.addEventListener("DOMContentLoaded", () => {
  const assetBase = window.SitePaths?.assets || "assets/";
  const siteBase = window.SitePaths?.site || "./";

  /* ------------------------------------------------------------------------
     Gallery expansion and modal
     ------------------------------------------------------------------------ */
  const expandButton = document.getElementById("expandGalleryBtn");
  const masonryWrapper = document.getElementById("masonryWrapper");
  const coverflow = document.getElementById("coverflow");
  const gallerySection = document.getElementById("gallery");
  const totalGalleryImages = 12;
  let currentImageIndex = 1;
  let modalReturnFocus = null;

  if (expandButton && masonryWrapper && coverflow) {
    expandButton.addEventListener("click", () => {
      const isExpanded = expandButton.getAttribute("aria-expanded") !== "true";
      expandButton.setAttribute("aria-expanded", String(isExpanded));
      masonryWrapper.classList.toggle("expanded", isExpanded);
      masonryWrapper.inert = !isExpanded;
      coverflow.classList.toggle("hide-coverflow", isExpanded);
      expandButton.textContent = isExpanded ? "Show less" : "View full gallery";
      document.dispatchEvent(
        new CustomEvent("home-gallery:expanded", {
          detail: { expanded: isExpanded },
        }),
      );

      if (!isExpanded) {
        window.scrollToElement?.(document.getElementById("gallery"));
      }
    });
  }

  const imageModal = document.getElementById("imageModal");
  const modalImage = document.getElementById("modalImg");
  const closeModalButton = document.getElementById("closeModal");
  const previousModalButton = document.getElementById("prevModalBtn");
  const nextModalButton = document.getElementById("nextModalBtn");

  function openModal(trigger) {
    if (!imageModal) return;
    imageModal.classList.add("show");
    document.body.classList.add("nav-open");
    modalReturnFocus = trigger;
    document.dispatchEvent(
      new CustomEvent("home-gallery:modal", { detail: { open: true } }),
    );
    closeModalButton?.focus();
  }

  function closeModal() {
    if (!imageModal) return;
    imageModal.classList.remove("show");
    document.body.classList.remove("nav-open");
    document.dispatchEvent(
      new CustomEvent("home-gallery:modal", { detail: { open: false } }),
    );
    modalReturnFocus?.focus();
  }

  function updateModalImage(index) {
    if (!modalImage) return;
    currentImageIndex = index;
    document.dispatchEvent(
      new CustomEvent("home-gallery:go-to", { detail: { index } }),
    );
    modalImage.classList.add("fade-transition");
    window.setTimeout(() => {
      modalImage.src = `${assetBase}images/gallery/gallery${index}.jpg`;
      modalImage.alt = `NewJeans gallery photo ${index}`;
      modalImage.onload = () => modalImage.classList.remove("fade-transition");
    }, 180);
  }

  if (gallerySection && imageModal && modalImage) {
    gallerySection.addEventListener("click", (event) => {
      const trigger = event.target.closest("[data-gallery-index]");
      const image = event.target.closest("img");
      const sourceMatch = image?.src.match(/gallery(\d+)\.jpg/);
      const index = trigger
        ? Number(trigger.dataset.galleryIndex)
        : Number(sourceMatch?.[1]);
      if (!Number.isInteger(index) || index < 1 || index > totalGalleryImages) {
        return;
      }

      currentImageIndex = index;
      document.dispatchEvent(
        new CustomEvent("home-gallery:go-to", { detail: { index } }),
      );
      modalImage.src = `${assetBase}images/gallery/gallery${currentImageIndex}.jpg`;
      modalImage.alt = `NewJeans gallery photo ${currentImageIndex}`;
      openModal(trigger || image);
    });

    previousModalButton?.addEventListener("click", (event) => {
      event.stopPropagation();
      currentImageIndex =
        currentImageIndex <= 1 ? totalGalleryImages : currentImageIndex - 1;
      updateModalImage(currentImageIndex);
    });

    nextModalButton?.addEventListener("click", (event) => {
      event.stopPropagation();
      currentImageIndex =
        currentImageIndex >= totalGalleryImages ? 1 : currentImageIndex + 1;
      updateModalImage(currentImageIndex);
    });

    closeModalButton?.addEventListener("click", closeModal);

    closeModalButton?.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        closeModal();
      }
    });

    imageModal.addEventListener("click", (event) => {
      if (event.target === imageModal) closeModal();
    });

    document.addEventListener("keydown", (event) => {
      if (!imageModal.classList.contains("show")) return;

      if (event.key === "ArrowLeft") previousModalButton?.click();
      if (event.key === "ArrowRight") nextModalButton?.click();
      if (event.key === "Escape") closeModal();
    });
  }

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
