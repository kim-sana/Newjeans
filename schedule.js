// === SCHEDULE DATA ===
const scheduleData = [
  {
    month: "JUN", day: 28, year: 2026,
    title: "SBS Inkigayo Pre-recording",
    location: "SBS Prism Tower, Seoul",
    time: "03:00 KST",
  },
  {
    month: "JUN", day: 30, year: 2026,
    title: "'Supernatural' Pop-Up Store",
    location: "Line Friends Square, Shibuya",
    time: "10:00 JST",
  },
  {
    month: "JUL", day: 5, year: 2026,
    title: "Global Fansign Event",
    location: "Soundwave Store, Seoul",
    time: "14:00 KST",
  },
  {
    month: "JUL", day: 15, year: 2026,
    title: "Bunnies Camp 2026: Fan Meeting",
    location: "Tokyo Dome, Japan",
    time: "18:00 JST",
  },
  {
    month: "JUL", day: 22, year: 2026,
    title: "M Countdown Comeback Stage",
    location: "CJ ENM Center, Seoul",
    time: "16:00 KST",
  },
  {
    month: "AUG", day: 2, year: 2026,
    title: "Summer Showcase Concert",
    location: "Olympic Gymnastics Arena, Seoul",
    time: "19:00 KST",
  },
  {
    month: "AUG", day: 9, year: 2026,
    title: "Bunnies Radio Live Session",
    location: "MBC Radio Studio, Seoul",
    time: "20:00 KST",
  },
  {
    month: "AUG", day: 18, year: 2026,
    title: "Brand Ambassador Photo Shoot",
    location: "Studio Lumina, Seoul",
    time: "09:00 KST",
  },
  {
    month: "AUG", day: 27, year: 2026,
    title: "Asia Tour: Bangkok Stop",
    location: "Impact Arena, Bangkok",
    time: "19:30 ICT",
  },
  {
    month: "SEP", day: 3, year: 2026,
    title: "Music Bank Live Performance",
    location: "KBS Hall, Seoul",
    time: "17:00 KST",
  },
  {
    month: "SEP", day: 12, year: 2026,
    title: "Asia Tour: Jakarta Stop",
    location: "Indonesia Arena, Jakarta",
    time: "19:00 WIB",
  },
  {
    month: "SEP", day: 20, year: 2026,
    title: "Bunnies Camp Finale Livestream",
    location: "Online Broadcast",
    time: "21:00 KST",
  },
];

const MONTH_ORDER = [
  "JAN","FEB","MAR","APR","MAY","JUN",
  "JUL","AUG","SEP","OCT","NOV","DEC",
];

// === HELPERS ===
function dateValue(item) {
  return new Date(item.year, MONTH_ORDER.indexOf(item.month), item.day);
}

function isPast(item) {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return dateValue(item) < today;
}

// === COUNTDOWN BANNER ===
function initCountdown() {
  const banner = document.getElementById("wfCountdown");
  if (!banner) return;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const upcoming = scheduleData
    .filter((item) => !isPast(item))
    .sort((a, b) => dateValue(a) - dateValue(b));

  if (upcoming.length === 0) {
    banner.style.display = "none";
    return;
  }

  const next = upcoming[0];
  const diffDays = Math.round((dateValue(next) - today) / 86400000);

  const labelEl = document.getElementById("wfCountdownTitle");
  const daysEl  = document.getElementById("wfCountdownDays");
  const dateEl  = document.getElementById("wfCountdownDate");

  if (labelEl) labelEl.textContent = next.title;
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

// === SCROLL REVEAL via IntersectionObserver ===
function initReveal() {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("wf-card--visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.08 }
  );
  document.querySelectorAll(".wf-card").forEach((card) => observer.observe(card));
}

// === RENDER CARDS (DocumentFragment) ===
function renderCards(items) {
  const grid        = document.getElementById("wfGrid");
  const emptyState  = document.getElementById("wfEmpty");
  const resultCount = document.getElementById("wfResultCount");

  const fragment = document.createDocumentFragment();
  grid.innerHTML = "";

  if (items.length === 0) {
    emptyState.style.display = "flex";
    resultCount.textContent  = "";
    return;
  }

  emptyState.style.display = "none";
  resultCount.textContent  =
    `Showing ${items.length} event${items.length !== 1 ? "s" : ""}`;

  items.forEach((item, index) => {
    const past = isPast(item);
    const card = document.createElement("article");
    card.className =
      "wf-card glass-panel" + (past ? " wf-card--past" : "");
    card.setAttribute("tabindex", "0");
    card.setAttribute("role", "article");
    card.setAttribute(
      "aria-label",
      `${item.title}, ${item.month} ${item.day} ${item.year}, ${item.location}`
    );
    // staggered reveal delay
    card.style.setProperty("--reveal-delay", `${index * 60}ms`);

    // Date square
    const squareEl  = document.createElement("div");
    squareEl.className = "wf-card-square";
    const monthSpan = document.createElement("span");
    monthSpan.textContent = item.month;
    const dayStrong = document.createElement("strong");
    dayStrong.textContent = item.day;
    squareEl.append(monthSpan, dayStrong);

    // Info block
    const infoEl  = document.createElement("div");
    infoEl.className = "wf-card-info";

    const titleEl = document.createElement("h4");
    titleEl.textContent = item.title;

    const locEl = document.createElement("p");
    locEl.className = "wf-loc";
    locEl.textContent = item.location;

    const timeEl = document.createElement("p");
    timeEl.className = "wf-time";
    timeEl.textContent = item.time;

    infoEl.append(titleEl, locEl, timeEl);

    // Past badge
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

// === FILTER + SORT ===
function getResults(query, sortMode, activeMonth) {
  return scheduleData
    .filter((item) => {
      const matchQ =
        !query ||
        item.title.toLowerCase().includes(query) ||
        item.location.toLowerCase().includes(query);
      const matchM = activeMonth === "ALL" || item.month === activeMonth;
      return matchQ && matchM;
    })
    .sort((a, b) => {
      switch (sortMode) {
        case "date-asc":  return dateValue(a) - dateValue(b);
        case "date-desc": return dateValue(b) - dateValue(a);
        case "month-asc": return a.month.localeCompare(b.month) || a.day - b.day;
        case "month-desc":return b.month.localeCompare(a.month) || a.day - b.day;
        default:          return 0;
      }
    });
}

// === URL PARAM STATE ===
function readState() {
  const p = new URLSearchParams(window.location.search);
  return {
    query: p.get("q")     || "",
    sort:  p.get("sort")  || "date-asc",
    month: p.get("month") || "ALL",
  };
}

function pushState(query, sort, month) {
  const p = new URLSearchParams();
  if (query)               p.set("q",     query);
  if (sort !== "date-asc") p.set("sort",  sort);
  if (month !== "ALL")     p.set("month", month);
  history.replaceState(
    null, "",
    window.location.pathname + (p.toString() ? "?" + p : "")
  );
}

// === MONTH FILTER CHIPS ===
function initMonthChips(initial, onChange) {
  const container = document.getElementById("wfMonthFilters");
  if (!container) return;

  ["ALL", "JUN", "JUL", "AUG", "SEP"].forEach((m) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "wf-chip" + (m === initial ? " wf-chip--active" : "");
    btn.textContent = m === "ALL" ? "All" : m;
    btn.setAttribute("aria-pressed", String(m === initial));
    btn.addEventListener("click", () => {
      container.querySelectorAll(".wf-chip").forEach((c) => {
        c.classList.remove("wf-chip--active");
        c.setAttribute("aria-pressed", "false");
      });
      btn.classList.add("wf-chip--active");
      btn.setAttribute("aria-pressed", "true");
      onChange(m);
    });
    container.appendChild(btn);
  });
}

// === MAIN ===
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("scheduleSearch");
  const sortSelect  = document.getElementById("scheduleSort");

  const { query, sort, month } = readState();
  if (searchInput) searchInput.value = query;
  if (sortSelect)  sortSelect.value  = sort;

  let activeMonth = month;

  function update() {
    const q = searchInput ? searchInput.value.trim().toLowerCase() : "";
    const s = sortSelect  ? sortSelect.value : "date-asc";
    pushState(q, s, activeMonth);
    renderCards(getResults(q, s, activeMonth));
  }

  initMonthChips(activeMonth, (selected) => {
    activeMonth = selected;
    update();
  });

  if (searchInput) searchInput.addEventListener("input", update);
  if (sortSelect)  sortSelect.addEventListener("change", update);

  initCountdown();
  update();
});

// === NAVBAR ===
document.addEventListener("DOMContentLoaded", () => {
  const nav = document.getElementById("dynamicNavbar");
  if (!nav) return;

  window.addEventListener("scroll", () => {
    if (window.scrollY > 80) nav.classList.remove("navbar-show");
  });

  window.addEventListener("mousemove", (e) => {
    if (e.clientY < 80)       nav.classList.add("navbar-show");
    else if (e.clientY > 150) nav.classList.remove("navbar-show");
  });

  nav.addEventListener("mouseenter", () => nav.classList.add("navbar-show"));
});
