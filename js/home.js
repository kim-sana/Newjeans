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
  const newsData = [
    {
      img: "images/gallery/gallery1.jpg",
      tag: "Comeback",
      headline: "NewJeans Announces Surprise Mini Album Drop This Summer",
      date: "June 10, 2024",
    },
    {
      img: "images/gallery/gallery2.jpg",
      tag: "Tour",
      headline: "Bunnies Camp World Tour 2024 — Full Venue List Revealed",
      date: "May 28, 2024",
    },
    {
      img: "images/gallery/gallery3.jpg",
      tag: "Award",
      headline: "NewJeans Sweep Melon Music Awards with 5 Trophies",
      date: "May 15, 2024",
    },
    {
      img: "images/gallery/gallery4.jpg",
      tag: "Music Video",
      headline: "How Sweet MV Hits 100 Million Views in Record Time",
      date: "Apr 30, 2024",
    },
    {
      img: "images/gallery/gallery5.jpg",
      tag: "Collab",
      headline: "NewJeans x Nike: Exclusive Limited Edition Collection",
      date: "Apr 18, 2024",
    },
    {
      img: "images/gallery/gallery6.jpg",
      tag: "Live",
      headline: "Behind the Scenes: OMG Live Stage at Inkigayo",
      date: "Mar 22, 2024",
    },
    {
      img: "images/gallery/gallery7.jpg",
      tag: "Chart",
      headline: "Super Shy Re-enters Top 10 on Billboard Global Chart",
      date: "Mar 08, 2024",
    },
    {
      img: "images/gallery/gallery8.jpg",
      tag: "Feature",
      headline: "NewJeans Cover Story: Inside the Studio with the Members",
      date: "Feb 14, 2024",
    },
    {
      img: "images/gallery/gallery9.jpg",
      tag: "Event",
      headline: "Fan Meeting 'Bunnies Camp' Sold Out in Under 2 Minutes",
      date: "Jan 31, 2024",
    },
  ];

  const newsTrack = document.getElementById("newsTrack");

  if (newsTrack) {
    [...newsData, ...newsData].forEach((item) => {
      const card = document.createElement("article");
      card.className = "news-card";
      card.innerHTML = `
        <img src="${item.img}" alt="${item.headline}" loading="lazy" />
        <div class="news-card-body">
          <span class="news-card-tag">${item.tag}</span>
          <p class="news-card-headline">${item.headline}</p>
          <span class="news-card-date">${item.date}</span>
        </div>
      `;
      newsTrack.appendChild(card);
    });
  }
});
