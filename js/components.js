(() => {
  const path = window.location.pathname;
  const isNested = path.includes('/web-page/') || path.includes('/gallery-project/');
  const basePath = isNested ? '../' : '';
  const componentTemplates = {
    navbar: `<nav class="navbar" id="dynamicNavbar" aria-label="Main navigation">
  <div class="navbar-inner">
    <a class="brand" href="{{BASE_PATH}}index.html" aria-label="NewJeans home">
      <span class="brand-logo" role="img" aria-label="NewJeans"></span>
    </a>
    <ul class="nav-links" id="navLinks">
      <li><a class="nav-link" href="{{BASE_PATH}}index.html" data-page="index">Home</a></li>
      <li><a class="nav-link" href="{{BASE_PATH}}discography.html" data-page="discography">Discography</a></li>
      <li><a class="nav-link" href="{{BASE_PATH}}schedule.html" data-page="schedule">Schedule</a></li>
      <li><a class="nav-link" href="{{BASE_PATH}}web-page/about.html" data-page="about">About</a></li>
      <li><a class="nav-link" href="{{BASE_PATH}}gallery-project/index.html" data-page="gallery">Gallery</a></li>
    </ul>
    <button
      class="nav-toggle"
      id="navToggle"
      type="button"
      aria-expanded="false"
      aria-controls="navLinks"
      aria-label="Toggle menu"
    >
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>`,
    footer: `<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-top">
      <div class="footer-brand">
        <span class="brand-logo brand-logo--white" role="img" aria-label="NewJeans"></span>
        <p>
          The official fan hub for NewJeans — bringing you the latest music,
          schedules, galleries, and news from the Bunnies universe.
        </p>
        <div class="footer-socials">
          <a href="https://www.instagram.com/newjeans_official" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="Instagram" aria-label="Instagram">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
          </a>
          <a href="https://www.youtube.com/@NewJeans_official" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="YouTube" aria-label="YouTube">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>
          </a>
          <a href="https://x.com/NewJeans_ADOR" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="X (Twitter)" aria-label="X (Twitter)">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <a href="https://www.tiktok.com/@newjeans_official" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="TikTok" aria-label="TikTok">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.17 8.17 0 0 0 4.78 1.52V6.75a4.85 4.85 0 0 1-1.01-.06z"/></svg>
          </a>
        </div>
      </div>

      <div class="footer-col">
        <h4>Navigate</h4>
        <ul>
          <li><a href="{{BASE_PATH}}index.html#hero">Home</a></li>
          <li><a href="{{BASE_PATH}}index.html#track-session">Top tracks</a></li>
          <li><a href="{{BASE_PATH}}index.html#gallery">Gallery</a></li>
          <li><a href="{{BASE_PATH}}index.html#news">News</a></li>
          <li><a href="{{BASE_PATH}}web-page/about.html">About</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Music</h4>
        <ul>
          <li><a href="https://open.spotify.com/artist/6HvZYsbFfjnjFrWF950C9d" target="_blank" rel="noopener noreferrer">Spotify</a></li>
          <li><a href="https://music.apple.com/id/artist/newjeans/1635469693" target="_blank" rel="noopener noreferrer">Apple Music</a></li>
          <li><a href="https://music.youtube.com/channel/UCxOqS3cYg4FaHbobICo7nFQ" target="_blank" rel="noopener noreferrer">YouTube Music</a></li>
          <li><a href="https://www.melon.com/artist/timeline.htm?artistId=3114174" target="_blank" rel="noopener noreferrer">Melon</a></li>
          <li><a href="https://www.genie.co.kr/detail/songInfo?xgnm=102563174" target="_blank" rel="noopener noreferrer">Genie</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>More</h4>
        <ul>
          <li><a href="{{BASE_PATH}}discography.html">Discography</a></li>
          <li><a href="{{BASE_PATH}}schedule.html">Schedule</a></li>
          <li><a href="{{BASE_PATH}}gallery-project/index.html">Gallery page</a></li>
          <li><a href="{{BASE_PATH}}web-page/about.html#members">Members</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <span data-page-year></span> NewJeans Fan Site. Unofficial fan-made website.</p>
      <div class="footer-bottom-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Use</a>
      </div>
    </div>
  </div>
</footer>`
  };
  const pageMap = {
    'index.html': 'index',
    'discography.html': 'discography',
    'schedule.html': 'schedule',
    'about.html': 'about',
    '/gallery-project/': 'gallery',
    '/gallery-project/index.html': 'gallery'
  };

  let activePage = 'index';
  for (const [key, value] of Object.entries(pageMap)) {
    if (path.includes(key) || path.endsWith(key)) {
      activePage = value;
      break;
    }
  }

  function renderComponent(name, placeholderId) {
    const placeholder = document.getElementById(placeholderId);
    if (!placeholder) return;

    const html = componentTemplates[name].split('{{BASE_PATH}}').join(basePath);
    placeholder.outerHTML = html;

    if (name === 'navbar') {
      const activeLink = document.querySelector(`.nav-link[data-page="${activePage}"]`);
      if (activeLink) {
        activeLink.classList.add('is-active');
        activeLink.setAttribute('aria-current', 'page');
      }
    }
  }

  renderComponent('navbar', 'navbar-placeholder');
  renderComponent('footer', 'footer-placeholder');
})();