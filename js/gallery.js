/* Gallery "see more" toggle. */
document.addEventListener("DOMContentLoaded", () => {
  const button = document.getElementById("toggleMoreBtn");
  const grid = document.getElementById("moreGrid");
  if (!button || !grid) return;

  const label = button.querySelector("span");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  button.addEventListener("click", () => {
    const isHidden = grid.hasAttribute("hidden");

    if (isHidden) {
      grid.removeAttribute("hidden");
      if (label) label.textContent = "Show less";
      button.classList.add("is-open");
      button.setAttribute("aria-expanded", "true");
      grid.scrollIntoView({
        behavior: reduceMotion ? "auto" : "smooth",
        block: "nearest",
      });
    } else {
      grid.setAttribute("hidden", "");
      if (label) label.textContent = "Tap to see more";
      button.classList.remove("is-open");
      button.setAttribute("aria-expanded", "false");
    }
  });
});
