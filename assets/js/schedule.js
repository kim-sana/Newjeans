/* Schedule data, filters, sorting, URL state, countdown and reveal. */
const scheduleData = [
  {
    slug: "sbs-inkigayo-pre-recording",
    title: "SBS Inkigayo Pre-recording",
    venue: "SBS Prism Tower",
    city: "Seoul",
    date: "2026-10-28",
    time: "03:00 KST",
    poster: "images/gallery/gallery1.jpg",
    description:
      "NewJeans opens the season with an early-morning pre-recording for SBS Inkigayo.",
  },
  {
    slug: "supernatural-pop-up-store",
    title: "'Supernatural' Pop-Up Store",
    venue: "Line Friends Square",
    city: "Shibuya",
    date: "2026-10-30",
    time: "10:00 JST",
    poster: "images/gallery/gallery2.jpg",
    description:
      "A limited pop-up celebrating 'Supernatural' opens in the heart of Shibuya.",
  },
  {
    slug: "global-fansign-event",
    title: "Global Fansign Event",
    venue: "Soundwave Store",
    city: "Seoul",
    date: "2026-11-05",
    time: "14:00 KST",
    poster: "images/gallery/gallery3.jpg",
    description:
      "Bunnies get closer to the members at an intimate global fansign in Seoul.",
  },
  {
    slug: "bunnies-camp-2026-fan-meeting",
    title: "Bunnies Camp 2026: Fan Meeting",
    venue: "Tokyo Dome",
    city: "Tokyo",
    date: "2026-11-15",
    time: "18:00 JST",
    poster: "images/gallery/gallery4.jpg",
    description:
      "The annual Bunnies Camp fan meeting lands at Tokyo Dome for a night of games and performances.",
  },
  {
    slug: "m-countdown-comeback-stage",
    title: "M Countdown Comeback Stage",
    venue: "CJ ENM Center",
    city: "Seoul",
    date: "2026-11-22",
    time: "16:00 KST",
    poster: "images/gallery/gallery5.jpg",
    description:
      "NewJeans returns to M Countdown with a special comeback stage.",
  },
  {
    slug: "year-end-showcase-concert",
    title: "Year-End Showcase Concert",
    venue: "Olympic Gymnastics Arena",
    city: "Seoul",
    date: "2026-12-02",
    time: "19:00 KST",
    poster: "images/gallery/gallery6.jpg",
    description:
      "A full-scale year-end concert closes the season with fan-favourite hits.",
  },
  {
    slug: "bunnies-radio-live-session",
    title: "Bunnies Radio Live Session",
    venue: "MBC Radio Studio",
    city: "Seoul",
    date: "2026-12-09",
    time: "20:00 KST",
    poster: "images/gallery/gallery7.jpg",
    description:
      "The members take over MBC Radio for a live session and Q&A with Bunnies.",
  },
  {
    slug: "brand-ambassador-photo-shoot",
    title: "Brand Ambassador Photo Shoot",
    venue: "Studio Lumina",
    city: "Seoul",
    date: "2026-12-18",
    time: "09:00 KST",
    poster: "images/gallery/gallery8.jpg",
    description:
      "An exclusive brand campaign shoot with the full group.",
  },
  {
    slug: "asia-tour-bangkok-stop",
    title: "Asia Tour: Bangkok Stop",
    venue: "Impact Arena",
    city: "Bangkok",
    date: "2026-12-27",
    time: "19:30 ICT",
    poster: "images/gallery/gallery9.jpg",
    description:
      "The Asia tour continues with a headline show at Impact Arena in Bangkok.",
  },
  {
    slug: "music-bank-live-performance",
    title: "Music Bank Live Performance",
    venue: "KBS Hall",
    city: "Seoul",
    date: "2027-01-03",
    time: "17:00 KST",
    poster: "images/gallery/gallery10.jpg",
    description:
      "NewJeans opens the new year with a live performance on Music Bank.",
  },
  {
    slug: "asia-tour-jakarta-stop",
    title: "Asia Tour: Jakarta Stop",
    venue: "Indonesia Arena",
    city: "Jakarta",
    date: "2027-01-12",
    time: "19:00 WIB",
    poster: "images/gallery/gallery11.jpg",
    description:
      "The Asia tour arrives in Jakarta for a night at Indonesia Arena.",
  },
  {
    slug: "bunnies-camp-finale-livestream",
    title: "Bunnies Camp Finale Livestream",
    venue: "Online Broadcast",
    city: "Worldwide",
    date: "2027-01-20",
    time: "21:00 KST",
    poster: "images/gallery/gallery12.jpg",
    description:
      "Bunnies everywhere can join the season finale through a global livestream.",
  },
];

const assetBase = window.SitePaths?.assets || "../assets/";

const MONTH_ORDER = [
  "JAN", "FEB", "MAR", "APR", "MAY", "JUN",
  "JUL", "AUG", "SEP", "OCT", "NOV", "DEC",
];

function dateValue(item) {
  const [year, month, day] = item.date.split("-").map(Number);
  return new Date(year, month - 1, day);
}

function eventDateLabel(item) {
  const date = dateValue(item);
  return `${MONTH_ORDER[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
}

function eventMonthKey(item) {
  return MONTH_ORDER[dateValue(item).getMonth()];
}

function isPast(item) {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return dateValue(item) < today;
}

function initCountdown() {
  const banner = document.getElementById("wfCountdown");
  if (!banner) return;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const upcoming = scheduleData
    .filter((item) => !isPast(item))
    .sort((a, b) => dateValue(a) - dateValue(b));

  const labelEl = banner.querySelector(".wf-countdown-label");
  const daysEl = document.getElementById("wfCountdownDays");
  const titleEl = document.getElementById("wfCountdownTitle");
  const dateEl = document.getElementById("wfCountdownDate");

  if (upcoming.length === 0) {
    banner.classList.add("is-empty");
    if (labelEl) labelEl.textContent = "Season status";
    if (daysEl) daysEl.textContent = "Wrapped";
    if (titleEl) titleEl.textContent = "All scheduled events have completed";
    if (dateEl) dateEl.textContent = "Stay tuned — the next NewJeans season is loading.";
    return;
  }

  const next = upcoming[0];
  const diffDays = Math.round((dateValue(next) - today) / 86400000);

  if (titleEl) titleEl.textContent = next.title;
  if (daysEl) {
    daysEl.textContent =
      diffDays === 0 ? "Today" :
      diffDays === 1 ? "Tomorrow" :
      `${diffDays} days away`;
  }
  if (dateEl) {
    dateEl.textContent =
      `${eventDateLabel(next)} \u00b7 ${next.time} \u00b7 ${next.venue}, ${next.city}`;
  }
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

  cards.forEach((card) => observer.observe(card));
}

function renderCards(items) {
  const grid = document.getElementById("wfGrid");
  const emptyState = document.getElementById("wfEmpty");
  const resultCount = document.getElementById("wfResultCount");
  if (!grid || !emptyState || !resultCount) return;

  const fragment = document.createDocumentFragment();
  grid.innerHTML = "";

  if (items.length === 0) {
    emptyState.style.display = "flex";
    resultCount.textContent = "";
    return;
  }

  emptyState.style.display = "none";
  resultCount.textContent =
    `Showing ${items.length} event${items.length !== 1 ? "s" : ""}`;

  items.forEach((item, index) => {
    const date = dateValue(item);

    const card = document.createElement("article");
    card.className = "wf-card";
    card.id = item.slug;
    card.dataset.slug = item.slug;
    card.setAttribute("tabindex", "0");
    card.setAttribute(
      "aria-label",
      `${item.title}, ${eventDateLabel(item)}, ${item.venue}, ${item.city}`
    );
    card.style.setProperty("--reveal-delay", `${index * 60}ms`);

    const posterEl = document.createElement("div");
    posterEl.className = "wf-card-poster";

    const posterImg = document.createElement("img");
    posterImg.src = `${assetBase}${item.poster}`;
    posterImg.alt = `NewJeans gallery photo for ${item.title}`;
    posterImg.loading = "lazy";
    posterImg.decoding = "async";
    posterImg.addEventListener(
      "error",
      () => {
        posterImg.hidden = true;
      },
      { once: true }
    );

    const squareEl = document.createElement("time");
    squareEl.className = "wf-card-square";
    squareEl.dateTime = item.date;
    squareEl.setAttribute("aria-label", eventDateLabel(item));
    const monthSpan = document.createElement("span");
    monthSpan.textContent = MONTH_ORDER[date.getMonth()];
    const dayStrong = document.createElement("strong");
    dayStrong.textContent = date.getDate();
    const yearEl = document.createElement("small");
    yearEl.textContent = date.getFullYear();
    squareEl.append(monthSpan, dayStrong, yearEl);

    posterEl.append(posterImg, squareEl);

    const infoEl = document.createElement("div");
    infoEl.className = "wf-card-info";

    const headEl = document.createElement("div");
    headEl.className = "wf-card-head";

    const titleEl = document.createElement("h3");
    titleEl.textContent = item.title;

    const timeEl = document.createElement("p");
    timeEl.className = "wf-time";
    timeEl.textContent = item.time;

    headEl.append(titleEl, timeEl);

    const venueEl = document.createElement("p");
    venueEl.className = "wf-venue";
    const venueName = document.createElement("strong");
    venueName.textContent = item.venue;
    const cityEl = document.createElement("span");
    cityEl.className = "wf-venue-city";
    cityEl.textContent = item.city;
    venueEl.append(venueName, cityEl);

    const descEl = document.createElement("p");
    descEl.className = "wf-desc";
    descEl.textContent = item.description;

    infoEl.append(headEl, venueEl, descEl);

    card.append(posterEl, infoEl);
    fragment.appendChild(card);
  });

  grid.appendChild(fragment);
  initReveal();
}

function getResults(query, sortMode) {
  return scheduleData
    .filter(
      (item) =>
        !isPast(item) &&
        (!query ||
          item.title.toLowerCase().includes(query) ||
          item.venue.toLowerCase().includes(query) ||
          item.city.toLowerCase().includes(query))
    )
    .sort((a, b) => {
      switch (sortMode) {
        case "date-asc": return dateValue(a) - dateValue(b);
        case "date-desc": return dateValue(b) - dateValue(a);
        case "month-asc":
          return eventMonthKey(a).localeCompare(eventMonthKey(b)) ||
            dateValue(a) - dateValue(b);
        case "month-desc":
          return eventMonthKey(b).localeCompare(eventMonthKey(a)) ||
            dateValue(a) - dateValue(b);
        default: return 0;
      }
    });
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

document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("scheduleSearch");

  const state = readState();
  let sort = state.sort;

  if (searchInput) searchInput.value = state.query;

  function update() {
    const query = searchInput ? searchInput.value.trim().toLowerCase() : "";
    pushState(query, sort);
    renderCards(getResults(query, sort));
  }

  sort = initSortDropdown(sort, (value) => {
    sort = value;
    update();
  }) || sort;

  if (searchInput) searchInput.addEventListener("input", update);

  initCountdown();
  update();
});
