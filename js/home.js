/* Home page: gallery showcase, lightbox and highlight news carousel. */
document.addEventListener("DOMContentLoaded", () => {
  /* ------------------------------------------------------------------------
     Gallery showcase, expansion and modal
     ------------------------------------------------------------------------ */
  const expandButton = document.getElementById("expandGalleryBtn");
  const masonryWrapper = document.getElementById("masonryWrapper");
  const randomShowcase = document.getElementById("randomShowcase");
  const gallerySection = document.getElementById("gallery");
  const showcaseImages = document.querySelectorAll(".showcase-item img");
  const totalGalleryImages = 12;
  let randomInterval = null;
  let currentImageIndex = 1;

  function getUniqueRandomNumbers() {
    const numbers = new Set();
    while (numbers.size < 3) {
      numbers.add(Math.floor(Math.random() * totalGalleryImages) + 1);
    }
    return Array.from(numbers);
  }

  function updateShowcaseImages() {
    const nextNumbers = getUniqueRandomNumbers();

    showcaseImages.forEach((image, index) => {
      image.style.opacity = "0";
      window.setTimeout(() => {
        image.src = `images/gallery/gallery${nextNumbers[index]}.jpg`;
        image.style.opacity = "1";
      }, 400);
    });
  }

  function startShowcaseRotation() {
    clearInterval(randomInterval);
    randomInterval = setInterval(updateShowcaseImages, 5000);
  }

  if (randomShowcase && showcaseImages.length) {
    startShowcaseRotation();
  }

  if (expandButton && masonryWrapper && randomShowcase) {
    expandButton.addEventListener("click", () => {
      const isExpanded = masonryWrapper.classList.toggle("expanded");
      randomShowcase.classList.toggle("hide-showcase", isExpanded);
      expandButton.textContent = isExpanded ? "Show less" : "View full gallery";

      if (isExpanded) {
        clearInterval(randomInterval);
        return;
      }

      updateShowcaseImages();
      startShowcaseRotation();
      window.scrollToElement?.(document.getElementById("gallery"));
    });
  }

  const imageModal = document.getElementById("imageModal");
  const modalImage = document.getElementById("modalImg");
  const closeModalButton = document.getElementById("closeModal");
  const previousModalButton = document.getElementById("prevModalBtn");
  const nextModalButton = document.getElementById("nextModalBtn");

  function openModal() {
    if (!imageModal) return;
    imageModal.classList.add("show");
    document.body.classList.add("nav-open");
    closeModalButton?.focus();
  }

  function closeModal() {
    if (!imageModal) return;
    imageModal.classList.remove("show");
    document.body.classList.remove("nav-open");
  }

  function updateModalImage(index) {
    if (!modalImage) return;
    modalImage.classList.add("fade-transition");
    window.setTimeout(() => {
      modalImage.src = `images/gallery/gallery${index}.jpg`;
      modalImage.onload = () => modalImage.classList.remove("fade-transition");
    }, 180);
  }

  if (gallerySection && imageModal && modalImage) {
    gallerySection.addEventListener("click", (event) => {
      if (event.target.tagName !== "IMG") return;

      const match = event.target.src.match(/gallery(\d+)\.jpg/);
      if (!match) return;

      currentImageIndex = Number.parseInt(match[1], 10);
      modalImage.src = `images/gallery/gallery${currentImageIndex}.jpg`;
      openModal();
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
          link.href = `news.html?slug=${encodeURIComponent(story.slug)}`;
          link.setAttribute("aria-label", `Read story: ${story.title}`);
          link.style.display = "block";
          link.style.color = "inherit";
          link.style.textDecoration = "none";

          const image = document.createElement("img");
          image.src = story.thumbnail;
          image.alt = story.title;
          image.loading = "lazy";

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
          link.append(image, body);
          card.appendChild(link);
          newsTrack.appendChild(card);
        });
      })
      .catch(() => {
        newsTrack.textContent = "News unavailable.";
      });
  }
});
