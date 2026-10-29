/* Schedule data, filters, sorting, URL state, countdown and reveal. */
const scheduleData = [
  {
    month: "OCT", day: 28, year: 2026,
    title: "SBS Inkigayo Pre-recording",
    location: "SBS Prism Tower, Seoul",
    time: "03:00 KST",
  },
  {
    month: "OCT", day: 30, year: 2026,
    title: "'Supernatural' Pop-Up Store",
    location: "Line Friends Square, Shibuya",
    time: "10:00 JST",
  },
  {
    month: "NOV", day: 5, year: 2026,
    title: "Global Fansign Event",
    location: "Soundwave Store, Seoul",
    time: "14:00 KST",
  },
  {
    month: "NOV", day: 15, year: 2026,
    title: "Bunnies Camp 2026: Fan Meeting",
    location: "Tokyo Dome, Japan",
    time: "18:00 JST",
  },
  {
    month: "NOV", day: 22, year: 2026,
    title: "M Countdown Comeback Stage",
    location: "CJ ENM Center, Seoul",
    time: "16:00 KST",
  },
  {
    month: "DEC", day: 2, year: 2026,
    title: "Year-End Showcase Concert",
    location: "Olympic Gymnastics Arena, Seoul",
    time: "19:00 KST",
  },
  {
    month: "DEC", day: 9, year: 2026,
    title: "Bunnies Radio Live Session",
    location: "MBC Radio Studio, Seoul",
    time: "20:00 KST",
  },
  {
    month: "DEC", day: 18, year: 2026,
    title: "Brand Ambassador Photo Shoot",
    location: "Studio Lumina, Seoul",
    time: "09:00 KST",
  },
  {
    month: "DEC", day: 27, year: 2026,
    title: "Asia Tour: Bangkok Stop",
    location: "Impact Arena, Bangkok",
    time: "19:30 ICT",
  },
  {
    month: "JAN", day: 3, year: 2027,
    title: "Music Bank Live Performance",
    location: "KBS Hall, Seoul",
    time: "17:00 KST",
  },
  {
    month: "JAN", day: 12, year: 2027,
    title: "Asia Tour: Jakarta Stop",
    location: "Indonesia Arena, Jakarta",
    time: "19:00 WIB",
  },
  {
    month: "JAN", day: 20, year: 2027,
    title: "Bunnies Camp Finale Livestream",
    location: "Online Broadcast",
    time: "21:00 KST",
  },
];

const MONTH_ORDER = [
  "JAN", "FEB", "MAR", "APR", "MAY", "JUN",
  "JUL", "AUG", "SEP", "OCT", "NOV", "DEC",
];

function dateValue(item) {
  return new Date(item.year, MONTH_ORDER.indexOf(item.month), item.day);
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
      `${next.month} ${next.day}, ${next.year} \u00b7 ${next.time} \u00b7 ${next.location}`;
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
    const past = isPast(item);
    const card = document.createElement("article");
    card.className = "wf-card" + (past ? " wf-card--past" : "");
    card.setAttribute("tabindex", "0");
    card.setAttribute(
      "aria-label",
      `${item.title}, ${item.month} ${item.day} ${item.year}, ${item.location}`
    );
    card.style.setProperty("--reveal-delay", `${index * 60}ms`);

    const squareEl = document.createElement("div");
    squareEl.className = "wf-card-square";
    const monthSpan = document.createElement("span");
    monthSpan.textContent = item.month;
    const dayStrong = document.createElement("strong");
    dayStrong.textContent = item.day;
    squareEl.append(monthSpan, dayStrong);

    const infoEl = document.createElement("div");
    infoEl.className = "wf-card-info";

    const titleEl = document.createElement("h3");
    titleEl.textContent = item.title;

    const locEl = document.createElement("p");
    locEl.className = "wf-loc";
    locEl.textContent = item.location;

    const timeEl = document.createElement("p");
    timeEl.className = "wf-time";
    timeEl.textContent = item.time;

    infoEl.append(titleEl, locEl, timeEl);

    if (past) {
      const badge = document.createElement("span");
      badge.className = "wf-badge wf-badge--past";
      badge.setAttribute("aria-label", "Event completed");
      badge.textContent = "Completed";
      infoEl.appendChild(badge);
    }

    card.append(squareEl, infoEl);
    fragment.appendChild(card);
  });

  grid.appendChild(fragment);
  initReveal();
}

function getResults(query, sortMode, activeMonth) {
  return scheduleData
    .filter((item) => {
      const matchQuery =
        !query ||
        item.title.toLowerCase().includes(query) ||
        item.location.toLowerCase().includes(query);
      const matchMonth =
        activeMonth === "ALL" || item.month === activeMonth;
      return matchQuery && matchMonth;
    })
    .sort((a, b) => {
      switch (sortMode) {
        case "date-asc": return dateValue(a) - dateValue(b);
        case "date-desc": return dateValue(b) - dateValue(a);
        case "month-asc": return a.month.localeCompare(b.month) || a.day - b.day;
        case "month-desc": return b.month.localeCompare(a.month) || a.day - b.day;
        default: return 0;
      }
    });
}

function readState() {
  const params = new URLSearchParams(window.location.search);
  return {
    query: params.get("q") || "",
    sort: params.get("sort") || "date-asc",
    month: params.get("month") || "ALL",
  };
}

function pushState(query, sort, month) {
  const params = new URLSearchParams();
  if (query) params.set("q", query);
  if (sort !== "date-asc") params.set("sort", sort);
  if (month !== "ALL") params.set("month", month);
  history.replaceState(
    null,
    "",
    window.location.pathname + (params.toString() ? "?" + params : "")
  );
}

function initMonthChips(initial, onChange) {
  const container = document.getElementById("wfMonthFilters");
  if (!container) return;

  const months = [...new Set(scheduleData.map((item) => item.month))]
    .sort((a, b) => {
      const firstDate = (month) =>
        Math.min(
          ...scheduleData
            .filter((item) => item.month === month)
            .map((item) => dateValue(item).getTime())
        );
      return firstDate(a) - firstDate(b);
    });

  ["ALL", ...months].forEach((month) => {
    const button = document.createElement("button");
    button.type = "button";
    button.className = "wf-chip" + (month === initial ? " wf-chip--active" : "");
    button.textContent = month === "ALL" ? "All" : month;
    button.setAttribute("aria-pressed", String(month === initial));
    button.addEventListener("click", () => {
      container.querySelectorAll(".wf-chip").forEach((chip) => {
        chip.classList.remove("wf-chip--active");
        chip.setAttribute("aria-pressed", "false");
      });
      button.classList.add("wf-chip--active");
      button.setAttribute("aria-pressed", "true");
      onChange(month);
    });
    container.appendChild(button);
  });
}

document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("scheduleSearch");
  const sortSelect = document.getElementById("scheduleSort");

  const state = readState();
  if (searchInput) searchInput.value = state.query;
  if (sortSelect) sortSelect.value = state.sort;

  let activeMonth = state.month;

  function update() {
    const query = searchInput ? searchInput.value.trim().toLowerCase() : "";
    const sort = sortSelect ? sortSelect.value : "date-asc";
    pushState(query, sort, activeMonth);
    renderCards(getResults(query, sort, activeMonth));
  }

  initMonthChips(activeMonth, (selected) => {
    activeMonth = selected;
    update();
  });

  if (searchInput) searchInput.addEventListener("input", update);
  if (sortSelect) sortSelect.addEventListener("change", update);

  initCountdown();
  update();
});
