(async function () {
  "use strict";

  const PAGE_SIZE = 24;
  let currentPage = 1;
  let allBooks = [];

  const results = document.getElementById("latestResults");
  const paginationEl = document.getElementById("pagination");
  const shownEl = document.getElementById("kpiShown");
  const totalEl = document.getElementById("kpiTotal");
  const genEl = document.getElementById("generatedAt");

  let currentLang = document.documentElement.getAttribute("lang") || localStorage.getItem("lang") || "ar";

  const norm = (s) => (s || "").toString().trim();

  function escapeHtml(str) {
    return (str || "").toString()
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function coverSrc(book) {
    if (book.cover_url) {
      if (book.cover_url.startsWith("http")) return book.cover_url;
      if (book.cover_url.startsWith("/storage/") || book.cover_url.startsWith("storage/")) {
        return `https://api.almashreqebookstore.com/${book.cover_url.replace(/^\/+/, "")}`;
      }
      return `https://api.almashreqebookstore.com/${book.cover_url.replace(/^\/+/, "")}`;
    }
    if (book.cover_path) {
      return `https://api.almashreqebookstore.com/storage/${book.cover_path.replace(/^\/+/, "")}`;
    }
    const isbn = norm(book.ISBN || book.isbn);
    return isbn ? `https://api.almashreqebookstore.com/storage/covers/${isbn}.jpg` : "";
  }

  function allLinks(book) {
    if (Array.isArray(book.Links) && book.Links.length) {
      return book.Links
        .map(x => ({ name: norm(x.name), url: norm(x.url) }))
        .filter(x => x.url);
    }
    if (Array.isArray(book.links) && book.links.length) {
      return book.links
        .map(x => ({ name: norm(x.name), url: norm(x.url) }))
        .filter(x => x.url);
    }
    const one = norm(book.Link || book.link);
    return one ? [{ name: "Open", url: one }] : [];
  }

  function renderBookCard(book) {
    const isAr = currentLang === "ar";
    const links = allLinks(book);
    const linksHtml = links.length ? `
      <div class="book-links">
        ${links.map(l => `<a class="link-chip" href="${escapeHtml(l.url)}" target="_blank" rel="noopener">${escapeHtml(l.name || (isAr ? "شراء" : "Buy"))}</a>`).join("")}
      </div>
    ` : "";

    const author = norm(book.Author);
    const language = norm(book.Language);
    const isbn = norm(book.ISBN);

    const isLocal = typeof window !== "undefined" && (
      window.location.hostname === "localhost" ||
      window.location.hostname === "127.0.0.1"
    );
    const bookUrl = isbn
      ? (isLocal ? `book.html?isbn=${encodeURIComponent(isbn)}` : `/book/${encodeURIComponent(isbn)}`)
      : (book._id ? `book.html?id=${encodeURIComponent(book._id)}` : "#");
    const cover = coverSrc(book);

    const bookAriaLabel = escapeHtml(book.Title || (isAr ? "تفاصيل الكتاب" : "Book Details"));
    const fallbackText = escapeHtml(book.Title ? (isAr ? "عرض التفاصيل" : "View Details") : "No cover");
    const coverAlt = isAr
      ? `غلاف كتاب ${escapeHtml(book.Title || "كتاب")}${author ? ` للمؤلف ${escapeHtml(author)}` : ""}`
      : `Cover of ${escapeHtml(book.Title || "Book")}${author ? ` by ${escapeHtml(author)}` : ""}`;

    const badgesHtml = (author || language || isbn) ? `
      <div class="book-badges">
        ${author ? `<span class="badge badge-author">${escapeHtml(author)}</span>` : ""}
        ${language ? `<span class="badge badge-lang">${escapeHtml(language)}</span>` : ""}
        ${isbn ? `<span class="badge badge-isbn">${escapeHtml(isbn)}</span>` : ""}
      </div>
    ` : "";

    return `
      <div class="book-card">
        <div class="book-cover-wrapper">
          <a class="book-cover-link" href="${bookUrl}" aria-label="${bookAriaLabel}">
            ${cover ? `
              <img class="book-cover"
                   src="${escapeHtml(cover)}"
                   alt="${coverAlt}"
                   loading="lazy"
                   onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            ` : ""}
            <div class="cover-fallback" style="${cover ? "display:none" : "display:flex"}">
              <span>${fallbackText}</span>
            </div>
          </a>
        </div>

        <h3><a class="book-title-link" href="${bookUrl}">${escapeHtml(book.Title || "")}</a></h3>
        ${badgesHtml}
        ${linksHtml}
      </div>
    `;
  }

  // ==========================
  // Pagination Helpers
  // ==========================
  function getPageNumbers(current, total) {
    if (total <= 7) {
      return Array.from({ length: total }, (_, i) => i + 1);
    }
    if (current <= 4) {
      return [1, 2, 3, 4, 5, "...", total];
    }
    if (current >= total - 3) {
      return [1, "...", total - 4, total - 3, total - 2, total - 1, total];
    }
    return [1, "...", current - 1, current, current + 1, "...", total];
  }

  function renderPagination(totalCount, page, pageSize) {
    if (!paginationEl) return;

    const totalPages = Math.ceil(totalCount / pageSize);
    if (totalCount === 0 || totalPages <= 1) {
      paginationEl.innerHTML = "";
      return;
    }

    const isAr = currentLang === "ar";
    const pages = getPageNumbers(page, totalPages);
    const prevDisabled = page <= 1;
    const nextDisabled = page >= totalPages;

    const prevLabel = isAr ? "الصفحة السابقة" : "Previous Page";
    const nextLabel = isAr ? "الصفحة التالية" : "Next Page";
    const prevText = isAr ? "السابق &rarr;" : "&larr; Prev";
    const nextText = isAr ? "&larr; التالي" : "Next &rarr;";

    const prevHtml = `
      <button type="button" class="pagination-btn prev-btn" data-page="${page - 1}" ${prevDisabled ? "disabled" : ""} aria-label="${prevLabel}">
        ${prevText}
      </button>
    `;

    const nextHtml = `
      <button type="button" class="pagination-btn next-btn" data-page="${page + 1}" ${nextDisabled ? "disabled" : ""} aria-label="${nextLabel}">
        ${nextText}
      </button>
    `;

    const pagesHtml = pages.map(p => {
      if (p === "...") {
        return `<span class="pagination-ellipsis" aria-hidden="true">&hellip;</span>`;
      }
      const isActive = p === page;
      const pageLabel = isAr ? `الصفحة ${p}` : `Page ${p}`;
      return `
        <button type="button" class="pagination-btn ${isActive ? "is-active" : ""}" data-page="${p}" ${isActive ? 'aria-current="page"' : ""} aria-label="${pageLabel}">
          ${p}
        </button>
      `;
    }).join("");

    const fromItem = (page - 1) * pageSize + 1;
    const toItem = Math.min(page * pageSize, totalCount);

    const infoHtml = isAr
      ? `<div class="pagination-info">عرض ${fromItem} - ${toItem} من أصل ${totalCount.toLocaleString()} كتاب (صفحة ${page} من ${totalPages})</div>`
      : `<div class="pagination-info">Showing ${fromItem} - ${toItem} of ${totalCount.toLocaleString()} books (Page ${page} of ${totalPages})</div>`;

    paginationEl.innerHTML = `
      <div class="pagination-controls">
        ${prevHtml}
        ${pagesHtml}
        ${nextHtml}
      </div>
      ${infoHtml}
    `;
  }

  function renderPage() {
    const totalItems = allBooks.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * PAGE_SIZE;
    const end = start + PAGE_SIZE;
    const pageItems = allBooks.slice(start, end);

    if (shownEl) shownEl.textContent = pageItems.length;

    const isAr = currentLang === "ar";
    const sectionTitle = isAr ? "أحدث الإضافات" : "Latest Additions";

    results.innerHTML = `
      <section class="group">
        <div class="group-head">
          <h2>${sectionTitle}</h2>
          <div class="group-count">${totalItems}</div>
        </div>
        <div class="grid">
          ${pageItems.map(renderBookCard).join("")}
        </div>
      </section>
    `;

    renderPagination(totalItems, currentPage, PAGE_SIZE);
  }

  function goToPage(page) {
    currentPage = page;
    renderPage();

    if (results) {
      const yOffset = -80;
      const y = results.getBoundingClientRect().top + window.pageYOffset + yOffset;
      window.scrollTo({ top: Math.max(0, y), behavior: "smooth" });
    }
  }

  // Click listener for pagination buttons
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".pagination-btn");
    if (!btn || btn.disabled || btn.classList.contains("is-active")) return;
    const targetPage = parseInt(btn.dataset.page, 10);
    if (!isNaN(targetPage) && targetPage >= 1) {
      goToPage(targetPage);
    }
  });

  // Re-render when language changes
  window.addEventListener("languagechange", (e) => {
    if (e.detail && e.detail.lang) {
      currentLang = e.detail.lang;
      renderPage();
    }
  });

  // Fetch and init
  try {
    let books = [];
    let metaCount = 0;
    let metaGen = null;

    const endpoints = [
      "https://api.almashreqebookstore.com/api/v1/books?sort=latest&all=true"
    ];

    let loaded = false;
    for (const ep of endpoints) {
      try {
        const res = await fetch(ep, {
          cache: "no-store",
          headers: { Accept: "application/json" }
        });
        if (res.ok) {
          const data = await res.json();
          books = Array.isArray(data.books) ? data.books : (Array.isArray(data.data) ? data.data : []);
          metaCount = data.count || data.total || books.length;
          metaGen = data.generated_at;
          loaded = true;
          break;
        } else {
          console.error(`[latest.js] HTTP ${res.status} from latest books endpoint: ${ep}`);
        }
      } catch (err) {
        console.error(`[latest.js] Network/CORS error fetching latest books from [${ep}]:`, err);
      }
    }

    if (!loaded || !books.length) {
      if (results) {
        results.innerHTML = `<div class="notice">تعذر تحميل بيانات الكتب / Failed to load books data</div>`;
      }
      return;
    }

    // Sort by latest (highest _id or order in catalog)
    books.sort((a, b) => (Number(b._id || b.id) || 0) - (Number(a._id || a.id) || 0));
    allBooks = books;

    const total = metaCount || allBooks.length;
    if (totalEl) totalEl.textContent = total.toLocaleString();
    if (genEl) genEl.textContent = (metaGen ? metaGen.replace("T", " ").replace("Z", " UTC") : "—");

    renderPage();
  } catch (e) {
    console.error(e);
    if (results) {
      results.innerHTML = `<div class="notice">تعذر تحميل بيانات الكتب / Failed to load books data</div>`;
    }
  }
})();
