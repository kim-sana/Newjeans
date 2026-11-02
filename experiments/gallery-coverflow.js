document.addEventListener("DOMContentLoaded", () => {
  // ── Data foto ─────────────────────────────────────────────
  // Nambah foto: ubah TOTAL_PHOTOS (file tetap gallery13.jpg, dst).
  // FOCUS = titik yang dipertahankan saat foto di-crop jadi persegi.
  // Format "x% y%". Contoh: { 4: "50% 20%" } = foto #4 dipotong dari atas.
  const TOTAL_PHOTOS = 12;
  const FOCUS = {};
  const PORTRAIT_FOCUS = "50% 22%"; // default foto portrait: wajah biasanya di atas

  // ── Tuning (sama dengan default 21st.dev) ─────────────────
  const ROTATE = 44; // derajat tilt kartu tetangga
  const DEPTH = 0.6; // seberapa jauh mundur (x lebar kartu)
  const FALLOFF = 0.56; // <1 = tilt melandai di kartu yang jauh
  const FADE = 0.1; // opacity hilang per langkah
  const GAP = 0.05; // jarak antar kartu (x lebar kartu)
  const MAX_TILT = 82;
  const SETTLE_OMEGA = 20; // damping kritis, cepat berhenti tanpa pantulan

  const photoPath = (index) => `../assets/images/gallery/gallery${index}.jpg`;
  const track = document.getElementById("coverflowTrack");
  const stage = document.getElementById("coverflowStage");
  const pagination = document.getElementById("slidePagination");
  const announcement = document.getElementById("slideAnnouncement");
  const expandButton = document.getElementById("expandGalleryBtn");
  const gridWrapper = document.getElementById("masonryWrapper");
  const grid = document.getElementById("galleryGrid");
  const modal = document.getElementById("imageModal");
  const modalImage = document.getElementById("modalImg");
  const closeModalButton = document.getElementById("closeModal");
  const previousModalButton = document.getElementById("prevModalBtn");
  const nextModalButton = document.getElementById("nextModalBtn");

  if (!track || !stage) return;

  const mod = (n, m) => ((n % m) + m) % m;

  let pos = 0; // posisi pecahan kartu di tengah — sumber kebenaran tunggal
  let target = 0; // tujuan settle yang sedang berjalan
  let width = 0; // lebar kartu (px)
  let raf = null;
  let motionVelocity = 0; // kartu per detik; dipertahankan saat animasi dialihkan
  let selected = -1; // index terdekat; -1 supaya paint pertama menyinkronkan UI
  let drag = null;
  let suppressClick = false;
  let modalReturnFocus = null;

  function makePhotoButton(index, className, eager) {
    const button = document.createElement("button");
    button.className = className;
    button.type = "button";
    button.dataset.galleryIndex = String(index);
    button.setAttribute(
      "aria-label",
      `Open gallery photo ${index} of ${TOTAL_PHOTOS}`,
    );
    if (FOCUS[index])
      button.style.setProperty("--focus-position", FOCUS[index]);

    const image = document.createElement("img");
    image.alt = `NewJeans gallery photo ${index}`;
    image.loading = eager ? "eager" : "lazy";
    image.decoding = "async";
    image.draggable = false;
    // Foto portrait di-crop persegi dari tengah sering memotong kepala,
    // jadi geser fokus ke atas — kecuali sudah diatur manual di FOCUS.
    image.addEventListener("load", () => {
      if (FOCUS[index]) return;
      if (image.naturalWidth / image.naturalHeight < 0.9) {
        button.style.setProperty("--focus-position", PORTRAIT_FOCUS);
      }
    });
    image.src = photoPath(index);
    button.appendChild(image);
    return button;
  }

  for (let index = 1; index <= TOTAL_PHOTOS; index += 1) {
    // Semua kartu carousel eager: kartu lazy di luar area terlihat baru
    // dimuat saat sudah masuk layar, jadi terlihat kosong sesaat.
    track.appendChild(makePhotoButton(index, "coverflow-card", true));
    grid?.appendChild(makePhotoButton(index, "experiment-grid-card", false));
  }

  const cards = Array.from(track.querySelectorAll(".coverflow-card"));
  const paginationDots = Array.from(
    pagination?.querySelectorAll(".pagination-dot") || [],
  );
  const indexAt = (p) => mod(Math.round(p), TOTAL_PHOTOS);

  function syncSelected(index) {
    if (index === selected) return;
    selected = index;
    cards.forEach((card, i) => {
      card.tabIndex = i === index ? 0 : -1;
      if (i === index) card.setAttribute("aria-current", "true");
      else card.removeAttribute("aria-current");
    });
    if (announcement)
      announcement.textContent = `Photo ${index + 1} of ${TOTAL_PHOTOS}`;
  }

  function syncPagination() {
    if (!paginationDots.length) return;
    const activeDot = mod(Math.round(pos), paginationDots.length);
    paginationDots.forEach((dot, index) => {
      if (index === activeDot) {
        dot.classList.add("is-active");
        dot.setAttribute("aria-current", "page");
      } else {
        dot.classList.remove("is-active");
        dot.removeAttribute("aria-current");
      }
    });
  }

  // Tulis langsung ke DOM tiap frame. Tidak ada transition CSS — gerak
  // sepenuhnya dihitung di sini, jadi kartu yang "teleport" di ujung ring
  // tidak pernah terlihat melintas di depan kartu lain.
  function paint() {
    if (!width) return;
    const pitch = width * (1 + GAP);

    cards.forEach((card, index) => {
      // Lipat jarak ke arah terpendek di sekeliling ring = mekanisme loop.
      let offset = mod(index - pos, TOTAL_PHOTOS);
      if (offset > TOTAL_PHOTOS / 2) offset -= TOTAL_PHOTOS;

      const distance = Math.abs(offset);
      const ramp = Math.pow(distance, FALLOFF);
      const tilt = Math.min(ROTATE * ramp, MAX_TILT) * Math.sign(offset);

      // Kartu di separuh putaran harus sudah transparan sebelum teleport.
      const edge = Math.min(1, Math.max(0, TOTAL_PHOTOS / 2 - distance));
      const opacity = Math.max(0, 1 - FADE * distance) * edge;

      card.style.transform =
        `translateX(calc(-50% + ${offset * pitch}px)) ` +
        `translateZ(${-DEPTH * width * ramp}px) rotateY(${-tilt}deg)`;
      card.style.opacity = opacity.toFixed(3);
      card.style.zIndex = String(100 - Math.round(distance));
      card.style.visibility = opacity < 0.01 ? "hidden" : "visible";
    });

    syncSelected(indexAt(pos));
    syncPagination();
  }

  function settle(to, initialVelocity = motionVelocity) {
    if (raf !== null) cancelAnimationFrame(raf);
    target = to;
    const distance = Math.abs(target - pos);
    const velocityTowardTarget =
      Math.sign(target - pos) === Math.sign(initialVelocity);
    const maxVelocityWithoutOvershoot = distance * SETTLE_OMEGA;
    motionVelocity =
      distance < 0.001
        ? 0
        : velocityTowardTarget
          ? Math.sign(initialVelocity) *
            Math.min(Math.abs(initialVelocity), maxVelocityWithoutOvershoot)
          : initialVelocity;
    let lastFrame = performance.now();

    const step = (now) => {
      const dt = Math.min((now - lastFrame) / 1000, 0.05);
      lastFrame = now;

      // Exact critically damped spring: preserves release velocity and lands
      // quickly without bouncing past the selected photo.
      const error = pos - target;
      const springTerm = motionVelocity + SETTLE_OMEGA * error;
      const decay = Math.exp(-SETTLE_OMEGA * dt);
      const nextError = (error + springTerm * dt) * decay;
      motionVelocity =
        (motionVelocity - SETTLE_OMEGA * springTerm * dt) * decay;
      pos = target + nextError;
      paint();

      if (Math.abs(nextError) < 0.001 && Math.abs(motionVelocity) < 0.01) {
        pos = target;
        motionVelocity = 0;
        paint();
        raf = null;
        return;
      }
      raf = requestAnimationFrame(step);
    };
    raf = requestAnimationFrame(step);
  }

  function goTo(index) {
    // Ambil jalur terpendek, jangan memutar balik seluruh ring.
    settle(index + Math.round((target - index) / TOTAL_PHOTOS) * TOTAL_PHOTOS);
  }

  function move(by) {
    settle(Math.round(target) + by);
  }

  paginationDots.forEach((dot) => {
    dot.addEventListener("click", () => {
      const requestedDot = Number(dot.dataset.page);
      const currentStep = Math.round(target);
      const forward = mod(
        requestedDot - mod(currentStep, paginationDots.length),
        paginationDots.length,
      );
      const backward = forward - paginationDots.length;
      const step = Math.abs(forward) <= Math.abs(backward) ? forward : backward;
      settle(currentStep + step);
    });
  });

  document
    .getElementById("previousSlide")
    ?.addEventListener("click", () => move(-1));
  document
    .getElementById("nextSlide")
    ?.addEventListener("click", () => move(1));

  stage.addEventListener("keydown", (event) => {
    if (event.key === "ArrowLeft") {
      event.preventDefault();
      move(-1);
    } else if (event.key === "ArrowRight") {
      event.preventDefault();
      move(1);
    }
  });

  // ── Drag ──────────────────────────────────────────────────
  // Pointer capture baru dipasang setelah gerakan melewati ambang. Kalau
  // dipasang di pointerdown, klik pada kartu dialihkan ke stage.
  stage.addEventListener("pointerdown", (event) => {
    if (event.button !== 0) return;
    drag = {
      id: event.pointerId,
      x: event.clientX,
      pos,
      v: 0,
      t: performance.now(),
      active: false,
    };
  });

  stage.addEventListener("pointermove", (event) => {
    if (!drag || drag.id !== event.pointerId) return;
    const dx = event.clientX - drag.x;

    if (!drag.active) {
      if (Math.abs(dx) < 6) return;
      drag.active = true;
      if (raf !== null) {
        cancelAnimationFrame(raf);
        raf = null;
      }
      motionVelocity = 0;
      drag.x = event.clientX;
      drag.pos = pos;
      drag.t = performance.now();
      stage.setPointerCapture(event.pointerId);
      return;
    }

    const pitch = width * (1 + GAP);
    if (!pitch) return;
    const now = performance.now();
    const previous = pos;
    pos = drag.pos - (event.clientX - drag.x) / pitch;
    target = pos;
    // Kartu per detik, sedikit di-smooth supaya lemparan tidak liar.
    const instant = ((pos - previous) / Math.max(now - drag.t, 1)) * 1000;
    drag.v = drag.v * 0.5 + instant * 0.5;
    motionVelocity = drag.v;
    drag.t = now;
    paint();
  });

  function endDrag(event) {
    if (!drag || drag.id !== event.pointerId) return;
    const wasActive = drag.active;
    const stale = performance.now() - drag.t > 100; // ditahan diam sebelum dilepas
    const velocity = stale ? 0 : drag.v;
    drag = null;
    if (!wasActive) return;

    suppressClick = true;
    window.setTimeout(() => {
      suppressClick = false;
    }, 0);
    const carried = Math.max(-2, Math.min(2, velocity * 0.18));
    settle(Math.round(pos + carried), velocity);
  }
  stage.addEventListener("pointerup", endDrag);
  stage.addEventListener("pointercancel", endDrag);

  // ── Modal ─────────────────────────────────────────────────
  function openModal(index, trigger) {
    goTo(index - 1);
    modalReturnFocus = trigger;
    modalImage.src = photoPath(index);
    modalImage.alt = `NewJeans gallery photo ${index}`;
    modal.classList.add("show");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("nav-open");
    closeModalButton.focus();
  }

  function closeModal() {
    modal.classList.remove("show");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("nav-open");
    modalReturnFocus?.focus();
  }

  function updateModalImage(by) {
    move(by);
    const number = mod(Math.round(target), TOTAL_PHOTOS) + 1;
    modalImage.classList.add("fade-transition");
    window.setTimeout(
      () => {
        modalImage.onload = () =>
          modalImage.classList.remove("fade-transition");
        modalImage.src = photoPath(number);
        modalImage.alt = `NewJeans gallery photo ${number}`;
      },
      140,
    );
  }

  document.addEventListener("click", (event) => {
    const photoButton = event.target.closest("[data-gallery-index]");
    if (!photoButton || suppressClick) return;
    const number = Number(photoButton.dataset.galleryIndex);

    // Klik kartu samping = pindah ke tengah. Klik kartu tengah = buka modal.
    if (
      photoButton.classList.contains("coverflow-card") &&
      number - 1 !== selected
    ) {
      goTo(number - 1);
      return;
    }
    openModal(number, photoButton);
  });

  closeModalButton.addEventListener("click", closeModal);
  previousModalButton.addEventListener("click", () => updateModalImage(-1));
  nextModalButton.addEventListener("click", () => updateModalImage(1));
  modal.addEventListener("click", (event) => {
    if (event.target === modal) closeModal();
  });

  document.addEventListener("keydown", (event) => {
    if (!modal.classList.contains("show")) return;
    if (event.key === "Escape") closeModal();
    if (event.key === "ArrowLeft") updateModalImage(-1);
    if (event.key === "ArrowRight") updateModalImage(1);
  });

  expandButton?.addEventListener("click", () => {
    const expanded = expandButton.getAttribute("aria-expanded") !== "true";
    expandButton.setAttribute("aria-expanded", String(expanded));
    expandButton.textContent = expanded ? "Show less" : "View full gallery";
    gridWrapper?.classList.toggle("expanded", expanded);
    if (gridWrapper) gridWrapper.inert = !expanded;
  });

  // Lebar kartu menentukan pitch, depth, dan perspective. Ukur offsetWidth
  // (lebar asli, tidak terpengaruh rotateY) hanya saat kotak berubah ukuran.
  const measure = () => {
    width = cards[0]?.offsetWidth || 0;
    paint();
  };
  if ("ResizeObserver" in window) {
    new ResizeObserver(measure).observe(stage);
  } else {
    window.addEventListener("resize", measure);
  }

  measure();
});
