/* Shared navigation + scroll reveal behaviour. */
(() => {
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

  const revealElements = document.querySelectorAll(".reveal");

  if (revealElements.length) {
    if (!("IntersectionObserver" in window)) {
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
