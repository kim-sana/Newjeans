/* Discography search, category filter and year sorting. */
document.addEventListener("DOMContentLoaded", () => {
  const releaseList = document.getElementById("releaseList");
  if (!releaseList) return;

  const items = Array.from(releaseList.querySelectorAll(".release-row"));
  const chips = Array.from(document.querySelectorAll(".disco-chips .chip"));
  const searchInput = document.getElementById("searchInput");
  const categorySelect = document.getElementById("catSelect2");
  const sortSelect = document.getElementById("sortSelect");
  const resetButton = document.getElementById("resetBtn");
  const applyButton = document.getElementById("applyBtn");

  if (!searchInput || !categorySelect || !sortSelect) return;

  function processDiscography() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const activeCategory = categorySelect.value;
    const activeSort = sortSelect.value;

    items.forEach((item) => {
      const title = item.querySelector(".rr-title")?.textContent ?? "";
      const tracks = item.querySelector(".rr-tracks")?.textContent ?? "";
      const searchable = `${title} ${tracks}`.toLowerCase();
      const type = item.dataset.type;
      const matchesSearch = searchable.includes(searchTerm);
      const matchesCategory =
        activeCategory === "all" || type === activeCategory;
      item.style.display = matchesSearch && matchesCategory ? "" : "none";
    });

    items.sort((first, second) => {
      const yearA = Number.parseInt(first.dataset.year, 10);
      const yearB = Number.parseInt(second.dataset.year, 10);
      return activeSort === "newest" ? yearB - yearA : yearA - yearB;
    });

    items.forEach((item) => releaseList.appendChild(item));
  }

  function syncChips(category) {
    chips.forEach((chip) => {
      chip.classList.toggle("is-active", chip.dataset.filter === category);
    });
  }

  chips.forEach((chip) => {
    chip.addEventListener("click", () => {
      syncChips(chip.dataset.filter);
      categorySelect.value = chip.dataset.filter;
      processDiscography();
    });
  });

  categorySelect.addEventListener("change", () => {
    syncChips(categorySelect.value);
  });

  applyButton?.addEventListener("click", (event) => {
    event.preventDefault();
    processDiscography();
  });

  searchInput.addEventListener("keydown", (event) => {
    if (event.key !== "Enter") return;
    event.preventDefault();
    processDiscography();
  });

  resetButton?.addEventListener("click", (event) => {
    event.preventDefault();
    searchInput.value = "";
    categorySelect.value = "all";
    sortSelect.value = "newest";
    syncChips("all");
    processDiscography();
  });

  processDiscography();
});
