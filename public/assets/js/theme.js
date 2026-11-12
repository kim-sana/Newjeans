/* Shared navigation + scroll reveal behaviour. */
(() => {
  const reducedMotionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
  const searchParams = new URLSearchParams(window.location.search);
  const smoothScrollSessionKey = "newjeans-smooth-scroll";
  let forceSmoothScroll = searchParams.get("smooth") === "1";
  const scrollDebug = searchParams.get("scrollDebug") === "1";
  const locomotiveConstructor = window.LocomotiveScroll;
  const revealElements = document.querySelectorAll(".reveal");
  let locomotiveScroll;

  try {
    if (forceSmoothScroll) {
      window.sessionStorage.setItem(smoothScrollSessionKey, "1");
    } else if (searchParams.get("smooth") === "0") {
      window.sessionStorage.removeItem(smoothScrollSessionKey);
    } else {
      forceSmoothScroll = window.sessionStorage.getItem(smoothScrollSessionKey) === "1";
    }
  } catch (error) {
    console.warn("[scroll] Session storage is unavailable; smooth override applies only to this URL.");
  }

  console.info("[scroll] prefers-reduced-motion:", reducedMotionQuery.matches);

  if (forceSmoothScroll && reducedMotionQuery.matches) {
    console.warn("[scroll] Smooth override active despite reduced motion preference.");
  }

  if (typeof locomotiveConstructor === "function" && (!reducedMotionQuery.matches || forceSmoothScroll)) {
    try {
      revealElements.forEach((element) => element.setAttribute("data-scroll", ""));
      locomotiveScroll = new locomotiveConstructor({
        lenisOptions: {
          lerp: 0.075,
          smoothWheel: true,
          syncTouch: false,
        },
        scrollCallback: (event) => {
          if (scrollDebug) console.debug("[scroll] event:", event);
        },
      });
      document.documentElement.dataset.locomotiveScroll = "active";
      document.documentElement.dataset.locomotiveVersion = window.locomotiveScrollVersion || "unknown";
      console.info("[scroll] Locomotive Scroll", document.documentElement.dataset.locomotiveVersion);
      console.info("[scroll] instance:", locomotiveScroll);
    } catch (error) {
      document.documentElement.dataset.locomotiveScroll = "error";
      console.error("[scroll] Locomotive Scroll initialization failed:", error);
    }
  } else {
    document.documentElement.dataset.locomotiveScroll = reducedMotionQuery.matches ? "reduced-motion" : "unavailable";
    if (reducedMotionQuery.matches) {
      console.info("[scroll] Using native scrolling because reduced motion is enabled. Add ?smooth=1 to opt in for this page.");
    } else {
      console.error("[scroll] LocomotiveScroll is missing. Check the vendor script path and load order.");
    }
  }

  window.scrollToElement = (element, options = {}) => {
    if (!element) return;

    if (locomotiveScroll?.scrollTo) {
      const { block, ...scrollOptions } = options;
      locomotiveScroll.scrollTo(element, scrollOptions);
      return;
    }

    element.scrollIntoView({
      behavior: reducedMotionQuery.matches && !forceSmoothScroll ? "auto" : "smooth",
      ...options,
    });
  };

  const navbar = document.getElementById("dynamicNavbar");
  const toggle = document.getElementById("navToggle");
  const links = document.getElementById("navLinks");
  const mobileQuery = window.matchMedia("(max-width: 860px)");

  if (navbar) {
    navbar.classList.add("is-visible");
    let lastY = window.scrollY;

    const closeMenu = () => {
      navbar.classList.remove("is-open");
      document.body.classList.remove("nav-open");
      if (toggle) toggle.setAttribute("aria-expanded", "false");
    };

    const onScroll = () => {
      if (mobileQuery.matches) {
        navbar.classList.add("is-visible");
        return;
      }

      const currentY = window.scrollY;

      if (currentY <= 12) {
        navbar.classList.add("is-visible");
      } else if (currentY > lastY + 4 && currentY > 120) {
        navbar.classList.remove("is-visible");
        closeMenu();
      } else if (currentY < lastY - 4) {
        navbar.classList.add("is-visible");
      }

      lastY = currentY;
    };

    const onMouseMove = (event) => {
      if (mobileQuery.matches) return;

      if (event.clientY < 96) {
        navbar.classList.add("is-visible");
      } else if (event.clientY > 190 && window.scrollY > 160) {
        navbar.classList.remove("is-visible");
      }
    };

    if (toggle) {
      toggle.addEventListener("click", () => {
        const isOpen = navbar.classList.toggle("is-open");
        toggle.setAttribute("aria-expanded", String(isOpen));
        document.body.classList.toggle("nav-open", isOpen);
      });
    }

    if (links) {
      links.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", closeMenu);
      });
    }

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") closeMenu();
    });

    mobileQuery.addEventListener("change", () => {
      closeMenu();
      navbar.classList.add("is-visible");
    });

    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("mousemove", onMouseMove, { passive: true });
  }

  if (!locomotiveScroll && revealElements.length) {
    if (!("IntersectionObserver" in window)) {
      revealElements.forEach((element) => element.classList.add("is-visible"));
    } else if (reducedMotionQuery.matches) {
      revealElements.forEach((element) => element.classList.add("is-visible"));
    } else {
      const revealObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add("is-visible");
            revealObserver.unobserve(entry.target);
          });
        },
        { threshold: 0.12, rootMargin: "0px 0px -32px 0px" }
      );

      revealElements.forEach((element) => revealObserver.observe(element));
    }
  }

  document.querySelectorAll("[data-page-year]").forEach((element) => {
    element.textContent = String(new Date().getFullYear());
  });
})();
