(() => {
  const path = window.location.pathname;
  const isNested = path.includes('/web-page/') || path.includes('/gallery-project/');
  const basePath = isNested ? '../' : '';
  
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

  async function loadComponent(url, placeholderId) {
    try {
      const response = await fetch(url);
      if (!response.ok) throw new Error(`Failed to load ${url}`);
      let html = await response.text();
      html = html.replace(/\{\{BASE_PATH\}\}/g, basePath);
      
      const placeholder = document.getElementById(placeholderId);
      if (placeholder) {
        placeholder.outerHTML = html;
        
        if (placeholderId === 'navbar-placeholder') {
          const activeLink = document.querySelector(`.nav-link[data-page="${activePage}"]`);
          if (activeLink) {
            activeLink.classList.add('is-active');
            activeLink.setAttribute('aria-current', 'page');
          }
        }
      }
    } catch (err) {
      console.error(`Component load error: ${err.message}`);
    }
  }

  const componentPath = `${basePath}components/`;
  Promise.all([
    loadComponent(`${componentPath}navbar.html`, 'navbar-placeholder'),
    loadComponent(`${componentPath}footer.html`, 'footer-placeholder')
  ]);
})();
