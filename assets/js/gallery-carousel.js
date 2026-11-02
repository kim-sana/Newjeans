/* Home gallery coverflow carousel. */
document.addEventListener("DOMContentLoaded", () => {
  const totalPhotos = 12;
  const rotate = 44;
  const depth = 0.6;
  const falloff = 0.56;
  const fade = 0.1;
  const gap = 0.05;
  const maxTilt = 82;
  const settleOmega = 20;
  const autoAdvanceDelay = 5000;
  const focusPositions = {};
  const portraitFocus = "50% 22%";
  const assetBase = window.SitePaths?.assets || "assets/";
  const photoPath = (index) =>
    `${assetBase}images/gallery/gallery${index}.jpg`;

  const track = document.getElementById("coverflowTrack");
  const stage = document.getElementById("coverflowStage");
  const announcement = document.getElementById("slideAnnouncement");
  const pagination = document.getElementById("slidePagination");

  if (!track || !stage) return;

  const mod = (number, divisor) => ((number % divisor) + divisor) % divisor;
  let position = 0;
  let target = 0;
  let cardWidth = 0;
  let frame = null;
  let motionVelocity = 0;
  let selected = -1;
  let drag = null;
  let suppressClick = false;
  let autoAdvanceTimer = null;
  let pointerInside = false;
  let galleryExpanded = false;
  let modalOpen = false;

  function makePhotoCard(index) {
    const card = document.createElement("button");
    card.className = "coverflow-card";
    card.type = "button";
    card.dataset.galleryIndex = String(index);
    card.setAttribute("aria-label", `Open gallery photo ${index} of ${totalPhotos}`);
    if (focusPositions[index]) {
      card.style.setProperty("--focus-position", focusPositions[index]);
    }

    const image = document.createElement("img");
    image.src = photoPath(index);
    image.alt = `NewJeans gallery photo ${index}`;
    image.loading = "eager";
    image.decoding = "async";
    image.draggable = false;
    image.addEventListener("load", () => {
      if (focusPositions[index]) return;
      if (image.naturalWidth / image.naturalHeight < 0.9) {
        card.style.setProperty("--focus-position", portraitFocus);
      }
    });
    card.appendChild(image);
    return card;
  }

  for (let index = 1; index <= totalPhotos; index += 1) {
    track.appendChild(makePhotoCard(index));
  }

  const cards = Array.from(track.querySelectorAll(".coverflow-card"));
  const dots = Array.from(pagination?.querySelectorAll(".pagination-dot") || []);
  const selectedIndexAt = (value) => mod(Math.round(value), totalPhotos);

  function canAutoAdvance() {
    return (
      !document.hidden &&
      !pointerInside &&
      !galleryExpanded &&
      !modalOpen
    );
  }

  function updateAutoAdvance() {
    window.clearTimeout(autoAdvanceTimer);
    autoAdvanceTimer = null;
    if (!canAutoAdvance()) return;

    autoAdvanceTimer = window.setTimeout(() => {
      autoAdvanceTimer = null;
      if (canAutoAdvance()) move(1);
    }, autoAdvanceDelay);
  }

  function syncSelected(index) {
    if (index === selected) return;
    selected = index;
    cards.forEach((card, cardIndex) => {
      card.tabIndex = cardIndex === index ? 0 : -1;
      if (cardIndex === index) card.setAttribute("aria-current", "true");
      else card.removeAttribute("aria-current");
    });
    if (announcement) {
      announcement.textContent = `Photo ${index + 1} of ${totalPhotos}`;
    }
  }

  function syncPagination() {
    if (!dots.length) return;
    const activeDot = mod(Math.round(position), dots.length);
    dots.forEach((dot, index) => {
      if (index === activeDot) {
        dot.classList.add("is-active");
        dot.setAttribute("aria-current", "page");
      } else {
        dot.classList.remove("is-active");
        dot.removeAttribute("aria-current");
      }
    });
  }

  function paint() {
    if (!cardWidth) return;
    const pitch = cardWidth * (1 + gap);

    cards.forEach((card, index) => {
      let offset = mod(index - position, totalPhotos);
      if (offset > totalPhotos / 2) offset -= totalPhotos;

      const distance = Math.abs(offset);
      const ramp = Math.pow(distance, falloff);
      const tilt = Math.min(rotate * ramp, maxTilt) * Math.sign(offset);
      const edge = Math.min(1, Math.max(0, totalPhotos / 2 - distance));
      const opacity = Math.max(0, 1 - fade * distance) * edge;

      card.style.transform =
        `translateX(calc(-50% + ${offset * pitch}px)) ` +
        `translateZ(${-depth * cardWidth * ramp}px) rotateY(${-tilt}deg)`;
      card.style.opacity = opacity.toFixed(3);
      card.style.zIndex = String(100 - Math.round(distance));
      card.style.visibility = opacity < 0.01 ? "hidden" : "visible";
    });

    syncSelected(selectedIndexAt(position));
    syncPagination();
  }

  function settle(nextTarget, initialVelocity = motionVelocity) {
    if (frame !== null) cancelAnimationFrame(frame);
    window.clearTimeout(autoAdvanceTimer);
    autoAdvanceTimer = null;
    target = nextTarget;

    const distance = Math.abs(target - position);
    const velocityTowardTarget =
      Math.sign(target - position) === Math.sign(initialVelocity);
    const maxVelocityWithoutOvershoot = distance * settleOmega;
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

      const error = position - target;
      const springTerm = motionVelocity + settleOmega * error;
      const decay = Math.exp(-settleOmega * dt);
      const nextError = (error + springTerm * dt) * decay;
      motionVelocity =
        (motionVelocity - settleOmega * springTerm * dt) * decay;
      position = target + nextError;
      paint();

      if (Math.abs(nextError) < 0.001 && Math.abs(motionVelocity) < 0.01) {
        position = target;
        motionVelocity = 0;
        paint();
        frame = null;
        updateAutoAdvance();
        return;
      }
      frame = requestAnimationFrame(step);
    };
    frame = requestAnimationFrame(step);
  }

  function goTo(index) {
    settle(
      index + Math.round((target - index) / totalPhotos) * totalPhotos,
    );
  }

  function move(by) {
    settle(Math.round(target) + by);
  }

  document.getElementById("previousSlide")?.addEventListener("click", () => move(-1));
  document.getElementById("nextSlide")?.addEventListener("click", () => move(1));

  dots.forEach((dot) => {
    dot.addEventListener("click", () => {
      const requestedDot = Number(dot.dataset.page);
      const currentStep = Math.round(target);
      const forward = mod(requestedDot - mod(currentStep, dots.length), dots.length);
      const backward = forward - dots.length;
      const step = Math.abs(forward) <= Math.abs(backward) ? forward : backward;
      settle(currentStep + step);
    });
  });

  stage.addEventListener("keydown", (event) => {
    if (event.key === "ArrowLeft") {
      event.preventDefault();
      move(-1);
    } else if (event.key === "ArrowRight") {
      event.preventDefault();
      move(1);
    }
  });

  stage.addEventListener("pointerdown", (event) => {
    if (event.button !== 0) return;
    drag = {
      id: event.pointerId,
      x: event.clientX,
      position,
      velocity: 0,
      time: performance.now(),
      active: false,
    };
  });

  stage.addEventListener("pointermove", (event) => {
    if (!drag || drag.id !== event.pointerId) return;
    const deltaX = event.clientX - drag.x;

    if (!drag.active) {
      if (Math.abs(deltaX) < 6) return;
      drag.active = true;
      window.clearTimeout(autoAdvanceTimer);
      autoAdvanceTimer = null;
      if (frame !== null) {
        cancelAnimationFrame(frame);
        frame = null;
      }
      motionVelocity = 0;
      drag.x = event.clientX;
      drag.position = position;
      drag.time = performance.now();
      stage.setPointerCapture(event.pointerId);
      return;
    }

    const pitch = cardWidth * (1 + gap);
    if (!pitch) return;
    const now = performance.now();
    const previousPosition = position;
    position = drag.position - (event.clientX - drag.x) / pitch;
    target = position;

    const instantVelocity =
      ((position - previousPosition) / Math.max(now - drag.time, 1)) * 1000;
    drag.velocity = drag.velocity * 0.5 + instantVelocity * 0.5;
    motionVelocity = drag.velocity;
    drag.time = now;
    paint();
  });

  function endDrag(event) {
    if (!drag || drag.id !== event.pointerId) return;
    const wasActive = drag.active;
    const isStale = performance.now() - drag.time > 100;
    const releaseVelocity = isStale ? 0 : drag.velocity;
    drag = null;
    if (!wasActive) return;

    suppressClick = true;
    window.setTimeout(() => {
      suppressClick = false;
    }, 0);
    const carried = Math.max(-2, Math.min(2, releaseVelocity * 0.18));
    settle(Math.round(position + carried), releaseVelocity);
  }

  stage.addEventListener("pointerup", endDrag);
  stage.addEventListener("pointercancel", endDrag);

  const coverflow = document.getElementById("coverflow");
  coverflow?.addEventListener("pointerenter", (event) => {
    if (event.pointerType === "touch") return;
    pointerInside = true;
    updateAutoAdvance();
  });
  coverflow?.addEventListener("pointerleave", (event) => {
    if (event.pointerType === "touch") return;
    pointerInside = false;
    updateAutoAdvance();
  });

  document.addEventListener("visibilitychange", updateAutoAdvance);
  document.addEventListener("home-gallery:expanded", (event) => {
    galleryExpanded = Boolean(event.detail?.expanded);
    updateAutoAdvance();
  });
  document.addEventListener("home-gallery:modal", (event) => {
    modalOpen = Boolean(event.detail?.open);
    updateAutoAdvance();
  });

  track.addEventListener("click", (event) => {
    const card = event.target.closest(".coverflow-card");
    if (!card || suppressClick) return;
    const index = Number(card.dataset.galleryIndex) - 1;
    if (index === selected) return;
    event.preventDefault();
    event.stopPropagation();
    goTo(index);
  });

  document.addEventListener("home-gallery:go-to", (event) => {
    const index = Number(event.detail?.index);
    if (Number.isInteger(index) && index >= 1 && index <= totalPhotos) {
      goTo(index - 1);
    }
  });

  const measure = () => {
    cardWidth = cards[0]?.offsetWidth || 0;
    paint();
  };
  if ("ResizeObserver" in window) {
    new ResizeObserver(measure).observe(stage);
  } else {
    window.addEventListener("resize", measure);
  }

  measure();
  updateAutoAdvance();
});
