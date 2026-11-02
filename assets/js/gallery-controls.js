/* Shared gallery expansion and lightbox controls for Home and Gallery. */
document.addEventListener("DOMContentLoaded", () => {
  const assetBase = window.SitePaths?.assets || "assets/";
  const totalGalleryImages = 12;
  const gallerySection = document.getElementById("gallery");
  const expandButton = document.getElementById("expandGalleryBtn");
  const masonryWrapper = document.getElementById("masonryWrapper");
  const coverflow = document.getElementById("coverflow");
  const imageModal = document.getElementById("imageModal");
  const modalImage = document.getElementById("modalImg");
  const closeModalButton = document.getElementById("closeModal");
  const previousModalButton = document.getElementById("prevModalBtn");
  const nextModalButton = document.getElementById("nextModalBtn");
  let currentImageIndex = 1;
  let modalReturnFocus = null;

  function setExpanded(isExpanded, scrollToGallery = false) {
    if (!expandButton || !masonryWrapper) return;

    expandButton.setAttribute("aria-expanded", String(isExpanded));
    masonryWrapper.classList.toggle("expanded", isExpanded);
    masonryWrapper.inert = !isExpanded;
    coverflow?.classList.toggle("hide-coverflow", isExpanded);
    expandButton.textContent = isExpanded ? "Show less" : "View full gallery";
    document.dispatchEvent(
      new CustomEvent("gallery:expanded", {
        detail: { expanded: isExpanded },
      }),
    );

    if (scrollToGallery) {
      window.scrollToElement?.(gallerySection);
    }
  }

  if (expandButton && masonryWrapper) {
    const startExpanded =
      new URLSearchParams(window.location.search).get("expanded") === "1";
    setExpanded(startExpanded);

    expandButton.addEventListener("click", () => {
      const isExpanded = expandButton.getAttribute("aria-expanded") !== "true";
      setExpanded(isExpanded, !isExpanded);
    });
  }

  function openModal(trigger) {
    if (!imageModal) return;
    imageModal.classList.add("show");
    imageModal.setAttribute("aria-hidden", "false");
    document.body.classList.add("nav-open");
    modalReturnFocus = trigger;
    document.dispatchEvent(
      new CustomEvent("gallery:modal", { detail: { open: true } }),
    );
    closeModalButton?.focus();
  }

  function closeModal() {
    if (!imageModal) return;
    imageModal.classList.remove("show");
    imageModal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("nav-open");
    document.dispatchEvent(
      new CustomEvent("gallery:modal", { detail: { open: false } }),
    );
    modalReturnFocus?.focus();
  }

  function goToImage(index) {
    currentImageIndex = index;
    document.dispatchEvent(
      new CustomEvent("gallery:go-to", { detail: { index } }),
    );
  }

  function updateModalImage(index) {
    if (!modalImage) return;
    goToImage(index);
    modalImage.classList.add("fade-transition");
    window.setTimeout(() => {
      modalImage.onload = () => modalImage.classList.remove("fade-transition");
      modalImage.src = `${assetBase}images/gallery/gallery${index}.jpg`;
      modalImage.alt = `NewJeans gallery photo ${index}`;
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

      goToImage(index);
      modalImage.src = `${assetBase}images/gallery/gallery${index}.jpg`;
      modalImage.alt = `NewJeans gallery photo ${index}`;
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
});
