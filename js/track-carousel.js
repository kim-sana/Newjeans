/* Shared 3D track carousel, audio cross-fade and viewport audio control. */
document.addEventListener("DOMContentLoaded", () => {
  const cards = Array.from(document.querySelectorAll(".track-card"));
  const dots = Array.from(document.querySelectorAll(".cdot"));
  const prevButton = document.getElementById("btnPrev3D");
  const nextButton = document.getElementById("btnNext3D");
  const trackSection = document.getElementById("track-session");

  if (!cards.length) return;

  const totalCards = cards.length;
  const MAX_VOLUME = 0.3;
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const sources = [
    { src: "audio/Hype-Boy.mp3", start: 53 },
    { src: "audio/Super-Shy.mp3", start: 51 },
    { src: "audio/ETA.mp3", start: 62 },
  ];

  const audioData = sources.map(({ src, start }) => {
    const audio = new Audio(src);
    audio.preload = "metadata";
    audio.volume = 0;
    return { audio, start };
  });

  const fadeIntervals = audioData.map(() => null);
  let activeIndex = 0;
  let activeAudioIndex = null;
  let autoPlayTimer = null;
  let audioEnabled = false;
  let isVisible = true;

  function fadeOut(audio, index) {
    clearInterval(fadeIntervals[index]);

    let volume = audio.volume;
    fadeIntervals[index] = setInterval(() => {
      if (volume > 0.05) {
        volume -= 0.05;
        audio.volume = Math.max(0, volume);
        return;
      }

      audio.volume = 0;
      audio.pause();
      clearInterval(fadeIntervals[index]);
      fadeIntervals[index] = null;
    }, 50);
  }

  function fadeIn(audio, startTime, index, resume = false) {
    clearInterval(fadeIntervals[index]);
    fadeIntervals[index] = null;

    if (!resume) {
      try {
        audio.currentTime = startTime;
      } catch {
        audio.currentTime = 0;
      }
    }

    audio.volume = 0;

    const playPromise = audio.play();
    if (playPromise === undefined) return;

    playPromise
      .then(() => {
        let volume = 0;
        fadeIntervals[index] = setInterval(() => {
          if (volume < MAX_VOLUME - 0.05) {
            volume += 0.05;
            audio.volume = volume;
            return;
          }

          audio.volume = MAX_VOLUME;
          clearInterval(fadeIntervals[index]);
          fadeIntervals[index] = null;
        }, 50);
      })
      .catch(() => {
        /* Autoplay can be blocked until the visitor interacts with the page. */
      });
  }

  function stopAllAudio() {
    audioData.forEach((track, index) => {
      if (!track.audio.paused) fadeOut(track.audio, index);
    });
    activeAudioIndex = null;
  }

  function updateAudio() {
    if (!audioEnabled || !isVisible) return;
    if (activeAudioIndex === activeIndex) return;

    if (activeAudioIndex !== null) {
      fadeOut(audioData[activeAudioIndex].audio, activeAudioIndex);
    }

    const track = audioData[activeIndex];
    if (!track) return;

    fadeIn(track.audio, track.start, activeIndex);
    activeAudioIndex = activeIndex;
  }

  function updateCarousel() {
    cards.forEach((card) => {
      card.classList.remove("pos-active", "pos-next", "pos-prev", "pos-hidden");
    });
    dots.forEach((dot) => dot.classList.remove("active"));

    const previousIndex = (activeIndex - 1 + totalCards) % totalCards;
    const nextIndex = (activeIndex + 1) % totalCards;

    cards[activeIndex].classList.add("pos-active");
    cards[previousIndex].classList.add("pos-prev");
    cards[nextIndex].classList.add("pos-next");
    dots[activeIndex]?.classList.add("active");

    updateAudio();
  }

  function nextSlide() {
    activeIndex = (activeIndex + 1) % totalCards;
    updateCarousel();
  }

  function previousSlide() {
    activeIndex = (activeIndex - 1 + totalCards) % totalCards;
    updateCarousel();
  }

  function stopTimer() {
    clearInterval(autoPlayTimer);
    autoPlayTimer = null;
  }

  function startTimer() {
    stopTimer();
    if (reduceMotion.matches || !isVisible) return;
    autoPlayTimer = setInterval(nextSlide, 4000);
  }

  function resetTimer() {
    startTimer();
  }

  prevButton?.addEventListener("click", () => {
    previousSlide();
    resetTimer();
  });

  nextButton?.addEventListener("click", () => {
    nextSlide();
    resetTimer();
  });

  dots.forEach((dot, index) => {
    dot.addEventListener("click", () => {
      activeIndex = index;
      updateCarousel();
      resetTimer();
    });
  });

  if (trackSection && "IntersectionObserver" in window) {
    const sectionObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          isVisible = entry.isIntersecting;

          if (isVisible) {
            audioEnabled = true;
            updateAudio();
            startTimer();
          } else {
            audioEnabled = false;
            stopAllAudio();
            stopTimer();
          }
        });
      },
      { threshold: 0.2 }
    );

    sectionObserver.observe(trackSection);
  } else {
    audioEnabled = true;
  }

  document.addEventListener("pointerdown", () => {
    if (!audioEnabled || !isVisible) return;
    const track = audioData[activeIndex];
    if (!track || !track.audio.paused) return;
    fadeIn(track.audio, track.start, activeIndex, Boolean(track.audio.currentTime));
    activeAudioIndex = activeIndex;
  }, { once: true, passive: true });

  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      stopAllAudio();
      stopTimer();
      return;
    }

    if (isVisible) {
      audioEnabled = true;
      updateAudio();
      startTimer();
    }
  });

  updateCarousel();
  startTimer();
});
