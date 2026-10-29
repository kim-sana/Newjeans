(() => {
  const status = document.getElementById("scrollStatus");
  const hint = document.getElementById("motionHint");
  if (!status) return;

  const state = document.documentElement.dataset.locomotiveScroll || "unavailable";
  status.dataset.state = state;

  if (state === "active") {
    status.textContent = `Locomotive Scroll ${document.documentElement.dataset.locomotiveVersion || "aktif"} · aktif`;
    hint.textContent = "Status aktif: roda mouse dan trackpad memakai easing Locomotive + Lenis.";
  } else if (state === "reduced-motion") {
    status.textContent = "Native scroll · reduced motion";
    hint.textContent = "Reduced motion terdeteksi. Untuk demo, buka URL ini dengan ?smooth=1 agar Locomotive aktif.";
  } else if (state === "error") {
    status.textContent = "Locomotive gagal dimulai";
    hint.textContent = "Buka console untuk melihat error inisialisasi.";
  } else {
    status.textContent = "Locomotive tidak tersedia";
    hint.textContent = "Periksa URL script vendor/locomotive-scroll/locomotive-scroll.min.js.";
  }
})();
