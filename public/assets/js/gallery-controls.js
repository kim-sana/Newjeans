/* Shared gallery expansion and lightbox controls for Home and Gallery. */
document.addEventListener("DOMContentLoaded", () => {
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

  function galleryEntries() {
    const entries = new Map();

    document.querySelectorAll("[data-gallery-index]").forEach((trigger) => {
      const index = Number(trigger.dataset.galleryIndex);
      if (!Number.isInteger(index) || index < 1 || entries.has(index)) return;

      const image = trigger.querySelector("img");
      const src = trigger.dataset.gallerySrc || image?.src || "";
      if (!src) return;

      entries.set(index, {
        index,
        src,
        alt: image?.alt || `NewJeans gallery photo ${index}`,
      });
    });

    return Array.from(entries.values()).sort((a, b) => a.index - b.index);
  }

  function findEntry(index) {
    return galleryEntries().find((entry) => entry.index === index) || null;
  }

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
    const entry = findEntry(index);
    if (!modalImage || !entry) return;

    goToImage(index);
    modalImage.classList.add("fade-transition");
    window.setTimeout(() => {
      modalImage.onload = () => modalImage.classList.remove("fade-transition");
      modalImage.src = entry.src;
      modalImage.alt = entry.alt;
    }, 180);
  }

  function stepModal(direction) {
    const entries = galleryEntries();
    if (entries.length === 0) return;

    const position = entries.findIndex(
      (entry) => entry.index === currentImageIndex,
    );
    const nextPosition = position === -1
      ? 0
      : (position + direction + entries.length) % entries.length;

    updateModalImage(entries[nextPosition].index);
  }

  if (gallerySection && imageModal && modalImage) {
    gallerySection.addEventListener("click", (event) => {
      const trigger = event.target.closest("[data-gallery-index]");
      if (!trigger) return;

      const index = Number(trigger.dataset.galleryIndex);
      const entry = findEntry(index);
      if (!entry) return;

      goToImage(index);
      modalImage.src = entry.src;
      modalImage.alt = entry.alt;
      openModal(trigger);
    });

    previousModalButton?.addEventListener("click", (event) => {
      event.stopPropagation();
      stepModal(-1);
    });

    nextModalButton?.addEventListener("click", (event) => {
      event.stopPropagation();
      stepModal(1);
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
