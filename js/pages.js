/**
 * Al-Mashreq eBookstore — Dynamic Static Pages Loader
 * Fetches and hydrates dynamic content for "About Us" and "Vision & Mission"
 * from the Laravel API backend (https://api.almashreqebookstore.com/api/v1/pages/{slug}).
 * Replaces hardcoded static HTML elements with content managed in Filament Admin.
 */
(function () {
  "use strict";

  // List of possible API host locations (strictly pointing to Laravel backend)
  const API_HOSTS = [
    "https://api.almashreqebookstore.com"
  ];

  /**
   * Transforms rich text HTML into responsive .card sections matching the site design
   */
  function formatRichContentToCards(html) {
    if (!html || !html.trim()) return "";

    // If content already contains formatted cards, return as is
    if (/class=["'][^"']*card[^"']*["']/i.test(html)) {
      return html;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(`<div>${html}</div>`, "text/html");
    const container = doc.body.firstElementChild;
    const children = Array.from(container.children);

    const hasHeadings = children.some(el => el.tagName === "H2" || el.tagName === "H3");

    if (hasHeadings) {
      let cards = [];
      let currentSection = [];

      for (const el of children) {
        if (el.tagName === "H2" || el.tagName === "H3") {
          if (currentSection.length > 0) {
            cards.push(currentSection);
            currentSection = [];
          }
        }
        currentSection.push(el);
      }
      if (currentSection.length > 0) {
        cards.push(currentSection);
      }

      return cards.map(sectionEls => {
        const inner = sectionEls.map(el => {
          if (el.tagName === "H2" || el.tagName === "H3") {
            el.style.cssText = "margin:0 0 10px;font-size:1.25rem;font-weight:800;color:var(--text-color)";
          } else if (el.tagName === "P") {
            el.style.cssText = "margin:0 0 10px;line-height:1.9;color:var(--text-color)";
          }
          return el.outerHTML;
        }).join("");

        return `<section class="card" style="line-height:1.9">${inner}</section>`;
      }).join("\n");
    }

    // Single card fallback for content without headings
    return `<section class="card" style="line-height:1.9">${html}</section>`;
  }

  async function fetchPageData(slug) {
    for (const host of API_HOSTS) {
      const cleanHost = host.replace(/\/+$/, "");
      const endpoints = [
        `${cleanHost}/api/v1/pages/${slug}`,
        `${cleanHost}/api/pages/${slug}`
      ];

      for (const url of endpoints) {
        try {
          const res = await fetch(url, {
            method: "GET",
            headers: {
              "Accept": "application/json"
            },
            cache: "no-store"
          });

          if (res.ok) {
            const json = await res.json();
            if (json && json.data) {
              console.info(`[Pages.js] Successfully loaded dynamic content for '${slug}' from: ${url}`);
              return json.data;
            }
          } else {
            console.error(`[Pages.js] HTTP ${res.status} fetching page '${slug}' from: ${url}`);
          }
        } catch (e) {
          console.error(`[Pages.js] Network/CORS error fetching page '${slug}' from [${url}]:`, e);
        }
      }
    }

    console.warn(`[Pages.js] Could not reach backend API on candidate hosts for page '${slug}'. Retaining static fallback.`);
    return null;
  }

  async function initDynamicPage() {
    // 1. Identify current page slug
    let slug = null;
    const pageContainer = document.getElementById("pageContainer") || document.querySelector("main.page-shell");
    if (pageContainer && pageContainer.getAttribute("data-page-slug")) {
      slug = pageContainer.getAttribute("data-page-slug");
    } else {
      const path = window.location.pathname.toLowerCase();
      if (path.includes("about")) {
        slug = "about";
      } else if (path.includes("vision")) {
        slug = "vision";
      }
    }

    if (!slug) return;

    // 2. Fetch page record from Laravel backend API
    const pageData = await fetchPageData(slug);
    if (!pageData) return;

    // 3. Render and update the DOM
    function render() {
      const currentLang = (window.GlobalApp && window.GlobalApp.getLang()) ||
                          localStorage.getItem("lang") ||
                          "ar";

      if (slug === "about") {
        // --- ARABIC HYDRATION ---
        const titleAr = document.getElementById("aboutTitleAr") || document.querySelector(".lang-ar .page-title");
        if (titleAr && pageData.title) {
          titleAr.textContent = pageData.title;
        }

        const subAr = document.getElementById("aboutSubAr") || document.querySelector(".lang-ar .page-sub");
        if (subAr && pageData.subtitle) {
          subAr.textContent = pageData.subtitle;
        }

        const contentAr = document.getElementById("aboutContentAr");
        if (contentAr && pageData.content) {
          contentAr.innerHTML = formatRichContentToCards(pageData.content);
        } else if (pageData.content) {
          const arContainer = document.querySelector(".lang-ar");
          if (arContainer) {
            const cards = arContainer.querySelectorAll(".card");
            for (let i = 1; i < cards.length; i++) cards[i].remove();
            const wrap = document.createElement("div");
            wrap.id = "aboutContentAr";
            wrap.innerHTML = formatRichContentToCards(pageData.content);
            arContainer.appendChild(wrap);
          }
        }

        // --- ENGLISH HYDRATION ---
        const titleEn = document.getElementById("aboutTitleEn") || document.querySelector(".lang-en .page-title");
        if (titleEn && pageData.title_en) {
          titleEn.textContent = pageData.title_en;
        }

        const subEn = document.getElementById("aboutSubEn") || document.querySelector(".lang-en .page-sub");
        if (subEn && pageData.subtitle_en) {
          subEn.textContent = pageData.subtitle_en;
        }

        const contentEn = document.getElementById("aboutContentEn");
        if (contentEn && pageData.content_en) {
          contentEn.innerHTML = formatRichContentToCards(pageData.content_en);
        } else if (pageData.content_en) {
          const enContainer = document.querySelector(".lang-en");
          if (enContainer) {
            const cards = enContainer.querySelectorAll(".card");
            for (let i = 1; i < cards.length; i++) cards[i].remove();
            const wrap = document.createElement("div");
            wrap.id = "aboutContentEn";
            wrap.innerHTML = formatRichContentToCards(pageData.content_en);
            enContainer.appendChild(wrap);
          }
        }
      } else if (slug === "vision") {
        // --- VISION & MISSION HYDRATION ---
        const title = document.getElementById("visionTitle") || document.querySelector(".page-title");
        if (title) {
          title.textContent = (currentLang === "en" && pageData.title_en) ? pageData.title_en : pageData.title;
        }

        const sub = document.getElementById("visionSub") || document.querySelector(".page-sub");
        if (sub) {
          sub.textContent = (currentLang === "en" && pageData.subtitle_en) ? pageData.subtitle_en : pageData.subtitle;
        }

        const contentWrap = document.getElementById("visionContent");
        const activeContent = (currentLang === "en" && pageData.content_en) ? pageData.content_en : pageData.content;
        if (contentWrap && activeContent) {
          contentWrap.innerHTML = formatRichContentToCards(activeContent);
        }
      }

      // --- META TAGS (SEO) ---
      if (pageData.meta_title) {
        document.title = pageData.meta_title;
      }
      if (pageData.meta_description) {
        const metaDesc = document.querySelector('meta[name="description"]');
        if (metaDesc) metaDesc.setAttribute("content", pageData.meta_description);
      }
    }

    render();

    // Re-render when language toggle button is clicked
    const langBtn = document.getElementById("langToggle");
    if (langBtn) {
      langBtn.addEventListener("click", () => {
        setTimeout(render, 50);
      });
    }

    window.refreshDynamicPages = render;
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initDynamicPage);
  } else {
    initDynamicPage();
  }
})();
