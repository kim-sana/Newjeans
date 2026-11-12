/* Schedule filters, sorting, URL state, countdown and reveal. */
document.addEventListener("DOMContentLoaded", () => {
  const grid = document.getElementById("wfGrid");
  const emptyState = document.getElementById("wfEmpty");
  const resultCount = document.getElementById("wfResultCount");
  const searchInput = document.getElementById("scheduleSearch");

  if (!grid || !emptyState || !resultCount) return;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  function cardDate(card) {
    const value = card.dataset.date || "";
    if (!value) return null;
    const [year, month, day] = value.split("-").map(Number);
    if (!year || !month || !day) return null;
    return new Date(year, month - 1, day);
  }

  function initReveal() {
    const cards = document.querySelectorAll(".wf-card");

    if (!("IntersectionObserver" in window)) {
      cards.forEach((card) => card.classList.add("wf-card--visible"));
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("wf-card--visible");
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.08, rootMargin: "0px 0px -30px 0px" }
    );

    cards.forEach((card) => {
      if (!card.classList.contains("wf-card--visible")) observer.observe(card);
    });
  }

  function initCountdown() {
    const banner = document.getElementById("wfCountdown");
    if (!banner) return;

    const labelEl = banner.querySelector(".wf-countdown-label");
    const daysEl = document.getElementById("wfCountdownDays");
    const titleEl = document.getElementById("wfCountdownTitle");
    const dateEl = document.getElementById("wfCountdownDate");

    const upcoming = Array.from(grid.querySelectorAll(".wf-card"))
      .map((card) => ({ card, date: cardDate(card) }))
      .filter((item) => item.date !== null && item.date >= today)
      .sort((a, b) => a.date - b.date);

    if (upcoming.length === 0) {
      banner.classList.add("is-empty");
      if (labelEl) labelEl.textContent = "Season status";
      if (daysEl) daysEl.textContent = "Wrapped";
      if (titleEl) titleEl.textContent = "All scheduled events have completed";
      if (dateEl) dateEl.textContent = "Stay tuned — the next NewJeans season is loading.";
      return;
    }

    const next = upcoming[0];
    const diffDays = Math.round((next.date - today) / 86400000);

    banner.classList.remove("is-empty");
    if (labelEl) labelEl.textContent = "Next event";
    if (titleEl) titleEl.textContent = next.card.dataset.title || "";
    if (daysEl) {
      daysEl.textContent =
        diffDays === 0 ? "Today" :
        diffDays === 1 ? "Tomorrow" :
        `${diffDays} days away`;
    }
    if (dateEl) {
      dateEl.textContent =
        `${next.card.dataset.dateLabel || ""} \u00b7 ${next.card.dataset.time || ""} \u00b7 ` +
        `${next.card.dataset.venue || ""}, ${next.card.dataset.city || ""}`;
    }
  }

  function applyFilters(query, sortMode) {
    const cards = Array.from(grid.querySelectorAll(".wf-card"));

    const visible = cards.filter((card) => {
      const date = cardDate(card);
      if (date === null || date < today) return false;
      if (!query) return true;
      return (card.dataset.search || "").toLowerCase().includes(query);
    });

    visible.sort((first, second) => {
      const dateA = cardDate(first);
      const dateB = cardDate(second);

      switch (sortMode) {
        case "date-asc": return dateA - dateB;
        case "date-desc": return dateB - dateA;
        case "month-asc":
          return (first.dataset.month || "").localeCompare(second.dataset.month || "") ||
            dateA - dateB;
        case "month-desc":
          return (second.dataset.month || "").localeCompare(first.dataset.month || "") ||
            dateA - dateB;
        default: return 0;
      }
    });

    cards.forEach((card) => {
      card.style.display = "none";
    });

    visible.forEach((card) => {
      card.style.display = "";
      grid.appendChild(card);
    });

    emptyState.style.display = visible.length === 0 ? "flex" : "none";
    resultCount.textContent =
      visible.length === 0
        ? ""
        : `Showing ${visible.length} event${visible.length !== 1 ? "s" : ""}`;

    initReveal();
  }

  function readState() {
    const params = new URLSearchParams(window.location.search);
    return {
      query: params.get("q") || "",
      sort: params.get("sort") || "date-asc",
    };
  }

  function pushState(query, sort) {
    const params = new URLSearchParams();
    if (query) params.set("q", query);
    if (sort !== "date-asc") params.set("sort", sort);
    history.replaceState(
      null,
      "",
      window.location.pathname + (params.toString() ? "?" + params : "")
    );
  }

  function initSortDropdown(initialValue, onChange) {
    const root = document.getElementById("scheduleSort");
    if (!root) return;

    const trigger = root.querySelector(".wf-select-trigger");
    const valueEl = root.querySelector(".wf-select-value");
    const menu = root.querySelector(".wf-select-menu");
    if (!trigger || !valueEl || !menu) return;

    const options = Array.from(menu.querySelectorAll(".wf-select-option"));
    if (options.length === 0) return;

    let currentValue = options.some(
      (option) => option.dataset.value === initialValue
    )
      ? initialValue
      : options[0].dataset.value;

    function focusOption(index) {
      const option = options[index];
      if (option) option.focus();
    }

    function setValue(nextValue, emit = true) {
      const nextOption = options.find(
        (option) => option.dataset.value === nextValue
      );
      if (!nextOption) return;

      currentValue = nextValue;
      valueEl.textContent = nextOption.textContent.trim();
      options.forEach((option) => {
        option.setAttribute("aria-selected", String(option === nextOption));
      });

      if (emit) onChange(currentValue);
    }

    function openMenu(startIndex) {
      if (!menu.hidden) return;

      menu.hidden = false;
      root.classList.add("is-open");
      trigger.setAttribute("aria-expanded", "true");

      const selectedIndex = options.findIndex(
        (option) => option.dataset.value === currentValue
      );
      focusOption(
        typeof startIndex === "number" ? startIndex : Math.max(selectedIndex, 0)
      );
    }

    function closeMenu(returnFocus = true) {
      if (menu.hidden) return;

      menu.hidden = true;
      root.classList.remove("is-open");
      trigger.setAttribute("aria-expanded", "false");
      if (returnFocus) trigger.focus();
    }

    function selectOption(option) {
      setValue(option.dataset.value);
      closeMenu();
    }

    trigger.addEventListener("click", () => {
      if (menu.hidden) {
        openMenu();
      } else {
        closeMenu();
      }
    });

    trigger.addEventListener("keydown", (event) => {
      if (event.key === "ArrowDown" || event.key === "ArrowUp") {
        event.preventDefault();
        openMenu(event.key === "ArrowUp" ? options.length - 1 : undefined);
      } else if (event.key === "Escape") {
        closeMenu();
      }
    });

    menu.addEventListener("keydown", (event) => {
      const activeIndex = options.indexOf(document.activeElement);

      if (event.key === "ArrowDown") {
        event.preventDefault();
        focusOption((activeIndex + 1) % options.length);
      } else if (event.key === "ArrowUp") {
        event.preventDefault();
        focusOption((activeIndex - 1 + options.length) % options.length);
      } else if (event.key === "Home") {
        event.preventDefault();
        focusOption(0);
      } else if (event.key === "End") {
        event.preventDefault();
        focusOption(options.length - 1);
      } else if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        if (options[activeIndex]) selectOption(options[activeIndex]);
      } else if (event.key === "Escape") {
        event.preventDefault();
        closeMenu();
      } else if (event.key === "Tab") {
        closeMenu();
      }
    });

    options.forEach((option) => {
      option.addEventListener("click", () => selectOption(option));
    });

    document.addEventListener("click", (event) => {
      if (!root.contains(event.target)) closeMenu(false);
    });

    root.addEventListener("focusout", (event) => {
      if (!root.contains(event.relatedTarget)) closeMenu(false);
    });

    setValue(currentValue, false);
    return currentValue;
  }

  const state = readState();
  let sort = state.sort;

  if (searchInput) searchInput.value = state.query;

  function update() {
    const query = searchInput ? searchInput.value.trim().toLowerCase() : "";
    pushState(query, sort);
    applyFilters(query, sort);
  }

  sort = initSortDropdown(sort, (value) => {
    sort = value;
    update();
  }) || sort;

  if (searchInput) searchInput.addEventListener("input", update);

  initCountdown();
  update();
});
