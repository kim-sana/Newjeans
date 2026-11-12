/* Keep the album info card fixed while the tracklist scrolls. */
(() => {
  const wrap = document.querySelector(".album-info-wrap");
  const card = document.querySelector(".album-info");
  if (!wrap || !card) return;

  const desktopQuery = window.matchMedia("(min-width: 901px)");
  const root = document.documentElement;
  let ticking = false;

  function fixedTop() {
    const navHeight =
      Number.parseFloat(getComputedStyle(root).getPropertyValue("--nav-height")) ||
      74;

    return navHeight + 18;
  }

  function clearCard() {
    card.classList.remove("is-fixed", "is-pinned-bottom");
    card.style.position = "";
    card.style.top = "";
    card.style.left = "";
    card.style.width = "";
    card.style.bottom = "";
  }

  function updateCard() {
    ticking = false;

    if (!desktopQuery.matches) {
      clearCard();
      return;
    }

    const grid = wrap.closest(".album-detail-grid");
    if (!grid) {
      clearCard();
      return;
    }

    const wrapRect = wrap.getBoundingClientRect();
    const gridRect = grid.getBoundingClientRect();
    const top = fixedTop();
    const cardHeight = card.offsetHeight;

    if (gridRect.top > top) {
      clearCard();
      return;
    }

    if (gridRect.bottom - cardHeight <= top) {
      card.classList.remove("is-fixed");
      card.classList.add("is-pinned-bottom");
      card.style.position = "absolute";
      card.style.top = "auto";
      card.style.left = "0";
      card.style.width = "100%";
      card.style.bottom = "0";
      return;
    }

    card.classList.add("is-fixed");
    card.classList.remove("is-pinned-bottom");
    card.style.position = "fixed";
    card.style.top = `${top}px`;
    card.style.left = `${wrapRect.left}px`;
    card.style.width = `${wrapRect.width}px`;
    card.style.bottom = "auto";
  }

  function scheduleUpdate() {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(updateCard);
  }

  window.addEventListener("scroll", scheduleUpdate, { passive: true });
  window.addEventListener("resize", scheduleUpdate);
  window.addEventListener("load", scheduleUpdate);

  if (typeof desktopQuery.addEventListener === "function") {
    desktopQuery.addEventListener("change", scheduleUpdate);
  } else if (typeof desktopQuery.addListener === "function") {
    desktopQuery.addListener(scheduleUpdate);
  }

  updateCard();
})();
