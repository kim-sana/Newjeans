/* ==========================================================================
   NEWJEANS — ADVANCED SCRIPT ENGINE
   1. Custom Smooth Cursor Glow
   2. Comet Canvas (Pixel Art Sprites)
   3. 3D Carousel Auto-Rotate Logic
   4. Scroll Reveal & Navbar/Scroll Indicator Logic
   5. Discography Filter & Sort Engine (Fixed Re-animation)
   6. CTA Re-Animation Trigger (Fixed Reflow)
========================================================================== */

document.addEventListener("DOMContentLoaded", () => {
  /* ── 1. CUSTOM SMOOTH CURSOR GLOW ── */
  const cursorGlow = document.getElementById("cursorGlow");
  let mouseX = window.innerWidth / 2;
  let mouseY = window.innerHeight / 2;
  let glowX = mouseX;
  let glowY = mouseY;

  window.addEventListener(
    "mousemove",
    (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
    },
    { passive: true },
  );

  function animateCursor() {
    glowX += (mouseX - glowX) * 0.15;
    glowY += (mouseY - glowY) * 0.15;

    if (cursorGlow) {
      cursorGlow.style.transform = `translate3d(${glowX - 200}px, ${glowY - 200}px, 0)`;
    }
    requestAnimationFrame(animateCursor);
  }
  animateCursor();

  /* ── 2. COMET CANVAS SYSTEM (PIXEL ART) ── */
  const canvas = document.getElementById("cometCanvas");
  const ctx = canvas?.getContext("2d");

  if (canvas && ctx) {
    function resizeCanvas() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    }
    resizeCanvas();
    window.addEventListener("resize", resizeCanvas, { passive: true });

    // VARIABEL BARU: Melacak posisi scroll untuk Parallax
    let currentScrollY = window.scrollY;
    window.addEventListener(
      "scroll",
      () => {
        currentScrollY = window.scrollY;
      },
      { passive: true },
    );

    const MEMBER_CFGS = [
      {
        src: "images/minji.png",
        color: "#294dff",
        speed: 2.2,
        delay: 0,
        laneY: 0.15,
        size: 46,
      },
      {
        src: "images/hanni.png",
        color: "#ec4899",
        speed: 3.0,
        delay: 1500,
        laneY: 0.35,
        size: 40,
      },
      {
        src: "images/danielle.png",
        color: "#ffff24",
        speed: 2.6,
        delay: 3000,
        laneY: 0.55,
        size: 42,
      },
      {
        src: "images/haerin.png",
        color: "#10b981",
        speed: 3.4,
        delay: 1000,
        laneY: 0.75,
        size: 38,
      },
      {
        src: "images/hyein.png",
        color: "#ff47e9",
        speed: 2.8,
        delay: 4000,
        laneY: 0.9,
        size: 36,
      },
    ];

    const TRAIL_LEN = 35;

    function hexToRgb(hex) {
      const r = parseInt(hex.slice(1, 3), 16);
      const g = parseInt(hex.slice(3, 5), 16);
      const b = parseInt(hex.slice(5, 7), 16);
      return { r, g, b };
    }

    class Comet {
      constructor(cfg) {
        this.cfg = cfg;
        this.rgb = hexToRgb(cfg.color);
        this.img = new Image();
        this.ready = false;
        this.active = false;
        this.trail = [];
        this.x = 0;
        this.y = 0;

        this.img.onload = () => {
          this.ready = true;
        };
        this.img.src = cfg.src;

        setTimeout(() => {
          this.reset();
          this.active = true;
        }, cfg.delay);
      }

      reset() {
        const H = canvas.height || window.innerHeight;
        this.x = -this.cfg.size - 100;

        // Sesuaikan posisi Y awal dengan scroll saat di-reset
        const parallaxSpeed = 0.35;
        const scrollOffset = currentScrollY * parallaxSpeed;
        this.y = (((H * this.cfg.laneY - scrollOffset) % H) + H) % H;

        this.trail = [];
      }

      update() {
        if (!this.active) return;
        const W = canvas.width || window.innerWidth;
        const H = canvas.height || window.innerHeight;

        // 1. LOGIKA PARALLAX & SCREEN WRAPPING
        const parallaxSpeed = 0.35; // Bergerak 35% dari kecepatan scroll layar
        const scrollOffset = currentScrollY * parallaxSpeed;
        const baseY = H * this.cfg.laneY;

        // Matematika membungkus Y tanpa menghasilkan nilai negatif
        const newY = (((baseY - scrollOffset) % H) + H) % H;

        // 2. MENCEGAH BUG TRAIL SNAPPING
        if (this.trail.length > 0) {
          const lastY = this.trail[0].y;
          // Jika komet mendadak pindah kutub (dari layar bawah tembus ke atas)
          if (Math.abs(newY - lastY) > H / 2) {
            this.trail = []; // Putus garis jejaknya
          }
        }

        this.y = newY;
        this.x += this.cfg.speed;

        this.trail.unshift({ x: this.x, y: this.y });
        if (this.trail.length > TRAIL_LEN) this.trail.pop();

        if (this.x > W + this.cfg.size + 100) this.reset();
      }

      draw() {
        if (!this.active || this.trail.length === 0) return;
        const { r, g, b } = this.rgb;
        const half = this.cfg.size / 2;

        for (let i = this.trail.length - 1; i >= 0; i--) {
          const pt = this.trail[i];
          const prog = i / Math.max(this.trail.length - 1, 1);
          const alpha = (1 - prog) * 0.4;
          const radius = Math.max((1 - prog) * half * 1.5 + 1, 1);

          ctx.save();
          ctx.globalAlpha = alpha;
          const r1 = Math.max(radius * 2, 1);
          const grd = ctx.createRadialGradient(pt.x, pt.y, 0, pt.x, pt.y, r1);
          grd.addColorStop(0, `rgba(${r},${g},${b},0.8)`);
          grd.addColorStop(1, `rgba(${r},${g},${b},0)`);

          ctx.fillStyle = grd;
          ctx.beginPath();
          ctx.ellipse(pt.x, pt.y, r1, Math.max(r1 * 0.3, 1), 0, 0, Math.PI * 2);
          ctx.fill();
          ctx.restore();
        }

        ctx.save();
        ctx.shadowColor = this.cfg.color;
        ctx.shadowBlur = 15;
        ctx.imageSmoothingEnabled = false;

        if (this.ready) {
          ctx.drawImage(
            this.img,
            this.x - half,
            this.y - half,
            this.cfg.size,
            this.cfg.size,
          );
        } else {
          ctx.fillStyle = this.cfg.color;
          ctx.fillRect(
            this.x - half,
            this.y - half,
            this.cfg.size,
            this.cfg.size,
          );
        }
        ctx.restore();
      }
    }

    const comets = MEMBER_CFGS.map((cfg) => new Comet(cfg));

    function renderLoop() {
      const W = canvas.width || window.innerWidth;
      const H = canvas.height || window.innerHeight;

      ctx.clearRect(0, 0, W, H);
      comets.forEach((c) => {
        c.update();
        c.draw();
      });

      requestAnimationFrame(renderLoop);
    }
    renderLoop();
  }

  /* ── 3. 3D CAROUSEL AUTO-ROTATE LOGIC ── */
  const carouselWrap = document.querySelector(".carousel-wrap");
  const cards = Array.from(
    document.querySelectorAll(".carousel-track .track-card"),
  );
  const dots = Array.from(document.querySelectorAll(".cdot"));
  const prevBtn = document.getElementById("carouselPrev");
  const nextBtn = document.getElementById("carouselNext");

  if (cards.length > 0 && carouselWrap) {
    const total = cards.length;
    let current = 0;
    let autoTimer = null;
    let isHovering = false;

    function getPositionClass(idx, cur, tot) {
      const diff = (((idx - cur) % tot) + tot) % tot;
      if (diff === 0) return "pos-active";
      if (diff === tot - 1) return "pos-prev";
      if (diff === 1) return "pos-next";
      return "pos-hidden";
    }

    function renderCarousel() {
      cards.forEach((card, i) => {
        card.classList.remove(
          "pos-active",
          "pos-prev",
          "pos-next",
          "pos-hidden",
        );
        card.classList.add(getPositionClass(i, current, total));
      });
      dots.forEach((d, i) => d.classList.toggle("active", i === current));
    }

    function goToSlide(idx) {
      current = ((idx % total) + total) % total;
      renderCarousel();
    }

    function startAutoSlide() {
      stopAutoSlide();
      if (isHovering) return;

      autoTimer = setInterval(() => {
        goToSlide(current + 1);
      }, 4000);
    }

    function stopAutoSlide() {
      if (autoTimer) {
        clearInterval(autoTimer);
        autoTimer = null;
      }
    }

    prevBtn?.addEventListener("click", () => {
      goToSlide(current - 1);
      startAutoSlide();
    });
    nextBtn?.addEventListener("click", () => {
      goToSlide(current + 1);
      startAutoSlide();
    });

    dots.forEach((d) => {
      d.addEventListener("click", () => {
        goToSlide(parseInt(d.dataset.idx));
        startAutoSlide();
      });
    });

    cards.forEach((card, i) => {
      card.addEventListener("click", () => {
        if (i !== current) {
          goToSlide(i);
          startAutoSlide();
        }
      });
    });

    carouselWrap.addEventListener("mouseenter", () => {
      isHovering = true;
      stopAutoSlide();
    });

    carouselWrap.addEventListener("mouseleave", () => {
      isHovering = false;
      startAutoSlide();
    });

    renderCarousel();
    startAutoSlide();
  }

  /* ── 4. SCROLL REVEAL & INDICATOR LOGIC ── */
  const navbar = document.getElementById("navbar");
  const scrollIndicator = document.querySelector(".hero-scroll-indicator");

  window.addEventListener(
    "scroll",
    () => {
      const yPos = window.scrollY;
      navbar?.classList.toggle("scrolled", yPos > 50);

      if (yPos > 100) {
        scrollIndicator?.classList.add("hidden");
      } else {
        scrollIndicator?.classList.remove("hidden");
      }
    },
    { passive: true },
  );

  const revealOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  };

  const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("in-view");
        // Hentikan pantauan setelah animasi selesai
        observer.unobserve(entry.target);
      }
    });
  }, revealOptions);

  document.querySelectorAll("[data-reveal], [data-stagger]").forEach((el) => {
    revealObserver.observe(el);
  });

  /* ── 5. DISCOGRAPHY ENGINE (Filter, Sort, Search) ── */
  const releaseList = document.getElementById("releaseList");
  const chips = document.querySelectorAll(".chip");
  const sortSel = document.getElementById("sortSelect");
  const catSel = document.getElementById("catSelect2");
  const searchIn = document.getElementById("searchInput");
  const applyBtn = document.getElementById("applyBtn");
  const resetBtn = document.getElementById("resetBtn");

  let discoTimeout = null;

  function processDiscography() {
    if (!releaseList) return;

    // 1. Bersihkan timeout lama dan matikan observer sementara
    clearTimeout(discoTimeout);
    revealObserver.unobserve(releaseList);

    // 2. Hapus class animasi untuk mereset tampilannya
    releaseList.classList.remove("in-view");

    const cat = catSel?.value || "all";
    const query = (searchIn?.value || "").toLowerCase().trim();
    const sort = sortSel?.value || "newest";

    const rows = Array.from(releaseList.querySelectorAll(".release-row"));

    rows.forEach((row) => {
      const typeMatch = cat === "all" || row.dataset.type === cat;
      const titleEl = row.querySelector(".rr-title");
      const title = titleEl ? titleEl.textContent.toLowerCase() : "";
      const queryMatch = !query || title.includes(query);

      if (typeMatch && queryMatch) {
        row.style.display = "flex";
      } else {
        row.style.display = "none";
      }
    });

    const visibleRows = rows.filter((r) => r.style.display !== "none");
    visibleRows.sort((a, b) => {
      const yearA = parseInt(a.dataset.year) || 0;
      const yearB = parseInt(b.dataset.year) || 0;
      return sort === "newest" ? yearB - yearA : yearA - yearB;
    });

    visibleRows.forEach((r) => releaseList.appendChild(r));

    // 3. FORCE REFLOW: Memaksa browser meregistrasi DOM yang kosong
    void releaseList.offsetWidth;

    // 4. Nyalakan lagi observer setelah delay pendek agar animasi tertembak ulang
    discoTimeout = setTimeout(() => {
      revealObserver.observe(releaseList);
    }, 100);
  }

  chips.forEach((chip) => {
    chip.addEventListener("click", () => {
      chips.forEach((c) => c.classList.remove("active"));
      chip.classList.add("active");
      if (catSel) catSel.value = chip.dataset.filter;
      processDiscography();
    });
  });

  [sortSel, catSel].forEach((el) =>
    el?.addEventListener("change", processDiscography),
  );

  searchIn?.addEventListener("keyup", (e) => {
    if (e.key === "Enter") processDiscography();
  });

  applyBtn?.addEventListener("click", processDiscography);

  resetBtn?.addEventListener("click", () => {
    if (searchIn) searchIn.value = "";
    if (catSel) catSel.value = "all";
    if (sortSel) sortSel.value = "newest";
    chips.forEach((c, i) => c.classList.toggle("active", i === 0));
    processDiscography();
  });

  // Eksekusi pertama kali
  processDiscography();

  /* ── 6. NAVIGATION CTA RE-ANIMATION TRIGGER ── */
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      const targetId = this.getAttribute("href");
      if (targetId === "#") return;

      const targetSection = document.querySelector(targetId);
      if (targetSection) {
        const animatedElements = targetSection.querySelectorAll(
          "[data-reveal], [data-stagger]",
        );

        animatedElements.forEach((el) => {
          // Lepaskan elemen dari observer lama
          revealObserver.unobserve(el);
          // Tarik mundur status animasinya (opacity 0)
          el.classList.remove("in-view");
        });

        // FORCE REFLOW: Mencegah race condition (Browser Paint Batching)
        void targetSection.offsetWidth;

        // Pasang kembali observer setelah browser siap merekayasa ulang animasi
        setTimeout(() => {
          animatedElements.forEach((el) => {
            revealObserver.observe(el);
          });
        }, 100);
      }
    });
  });
});
