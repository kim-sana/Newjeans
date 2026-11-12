/* Discography search, category filter and year sorting. */
document.addEventListener("DOMContentLoaded", () => {
  const releaseList = document.getElementById("releaseList");
  if (!releaseList) return;

  const items = Array.from(releaseList.querySelectorAll(".release-row"));
  const chips = Array.from(document.querySelectorAll(".disco-chips .chip"));
  const searchInput = document.getElementById("searchInput");
  const resetButton = document.getElementById("resetBtn");
  const applyButton = document.getElementById("applyBtn");

  if (!searchInput) return;

  const state = {
    category: "all",
    sort: "newest",
  };

  function processDiscography() {
    const searchTerm = searchInput.value.toLowerCase().trim();

    items.forEach((item) => {
      const title = item.querySelector(".rr-title")?.textContent ?? "";
      const tracks = item.querySelector(".rr-tracks")?.textContent ?? "";
      const searchable = `${title} ${tracks}`.toLowerCase();
      const type = item.dataset.type;
      const matchesSearch = searchable.includes(searchTerm);
      const matchesCategory =
        state.category === "all" || type === state.category;
      item.style.display = matchesSearch && matchesCategory ? "" : "none";
    });

    items.sort((first, second) => {
      const yearA = Number.parseInt(first.dataset.year, 10);
      const yearB = Number.parseInt(second.dataset.year, 10);
      return state.sort === "newest" ? yearB - yearA : yearA - yearB;
    });

    items.forEach((item) => releaseList.appendChild(item));
  }

  function syncChips(category) {
    chips.forEach((chip) => {
      chip.classList.toggle("is-active", chip.dataset.filter === category);
    });
  }

  function initDiscoSelect(root, initialValue, onChange) {
    if (!root) return null;

    const trigger = root.querySelector(".disco-select-trigger");
    const valueEl = root.querySelector(".disco-select-value");
    const menu = root.querySelector(".disco-select-menu");
    if (!trigger || !valueEl || !menu) return null;

    const options = Array.from(menu.querySelectorAll(".disco-select-option"));
    if (options.length === 0) return null;

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
        typeof startIndex === "number"
          ? startIndex
          : Math.max(selectedIndex, 0)
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

    return {
      getValue: () => currentValue,
      setValue,
    };
  }

  const categoryControl = initDiscoSelect(
    document.getElementById("categorySelect"),
    state.category,
    (value) => {
      state.category = value;
      syncChips(value);
      processDiscography();
    }
  );

  const sortControl = initDiscoSelect(
    document.getElementById("sortSelect"),
    state.sort,
    (value) => {
      state.sort = value;
      processDiscography();
    }
  );

  chips.forEach((chip) => {
    chip.addEventListener("click", () => {
      state.category = chip.dataset.filter;
      syncChips(state.category);
      categoryControl?.setValue(state.category, false);
      processDiscography();
    });
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
    state.category = "all";
    state.sort = "newest";
    syncChips("all");
    categoryControl?.setValue("all", false);
    sortControl?.setValue("newest", false);
    processDiscography();
  });

  processDiscography();
});
