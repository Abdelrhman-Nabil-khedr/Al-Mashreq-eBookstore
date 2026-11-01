(async function(){
  const host = document.getElementById("bookDetail");
  const bcTitle = document.getElementById("bcTitle");

  const buybar = document.getElementById("buybar");
  const buybarLinks = document.getElementById("buybarLinks");

  function esc(s){
    return (s || "").toString()
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;")
      .replaceAll('"',"&quot;")
      .replaceAll("'","&#039;");
  }
  const norm = (s) => (s || "").toString().trim();

  function getParam(name){
    const u = new URL(window.location.href);
    return norm(u.searchParams.get(name));
  }

  // ✅ covers live in /covers or dynamic storage URL
  function coverSrc(book){
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

  function allLinks(book){
    if (Array.isArray(book.Links) && book.Links.length){
      return book.Links
        .map(x => ({ name: norm(x.name) || "Open", url: norm(x.url) }))
        .filter(x => x.url);
    }
    if (Array.isArray(book.links) && book.links.length){
      return book.links
        .map(x => ({ name: norm(x.name) || "Open", url: norm(x.url) }))
        .filter(x => x.url);
    }
    const one = norm(book.Link || book.link);
    return one ? [{ name: "Open", url: one }] : [];
  }

  function getList(v){
    if (Array.isArray(v)) return v.map(norm).filter(Boolean);
    const s = norm(v);
    if (!s) return [];
    return s.split(/[,،;]/).map(x => x.trim()).filter(Boolean);
  }

  function kv(label, value){
    if (!value) return "";
    return `<div class="kv"><div class="k">${esc(label)}</div><div class="v">${esc(value)}</div></div>`;
  }

  function badge(text, tone){
    if (!text) return "";
    const cls = tone ? `badge ${tone}` : "badge";
    return `<span class="${cls}"><span class="dot" aria-hidden="true"></span>${esc(text)}</span>`;
  }

  function chips(label, items){
    if (!items || !items.length) return "";
    return `
      <section class="book-block">
        <h3 class="book-h3">${esc(label)}</h3>
        <div class="chips">
          ${items.map(x => `<span class="chip">${esc(x)}</span>`).join("")}
        </div>
      </section>
    `;
  }

  function renderLinks(links){
    const isAr = (document.documentElement.getAttribute("dir") || "rtl") === "rtl";
    const heading = isAr ? "روابط الشراء والتحميل المتاحة" : "Available Purchase & Reading Links";
    const sub = isAr 
      ? "احصل على نسختك الآن عبر منصات نشر الكتب المعتمدة:" 
      : "Get your copy now directly from authorized eBook distribution platforms:";

    const html = links.map(l => `
      <a class="book-cta-btn" href="${esc(l.url)}" target="_blank" rel="noopener">
        <svg class="cta-cart-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="cta-name">${esc(l.name)}</span>
        <svg class="cta-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="5" y1="12" x2="19" y2="12"></line>
          <polyline points="12 5 19 12 12 19"></polyline>
        </svg>
      </a>
    `).join("");

    return `
      <section class="book-block book-cta-section" id="buyLinks">
        <div class="cta-header">
          <h3 class="book-h3 cta-title">${heading}</h3>
          <p class="cta-sub">${sub}</p>
        </div>
        <div class="book-cta-grid">
          ${html}
        </div>
      </section>
    `;
  }

  function updateDynamicSEO(book, isAr) {
    if (!book) return;
    const title = norm(book.Title) || (isAr ? "كتاب" : "Book");
    const author = norm(book.Author);
    const isbn = norm(book.ISBN);
    const publisher = norm(book.Publisher) || (isAr ? "مكتبة المشرق الإلكترونية" : "Al-Mashreq eBookstore");
    const synopsis = norm(book.Synopsis) || norm(book.Description) || norm(book.Blurb);
    const cover = coverSrc(book);

    const siteName = isAr ? "مكتبة المشرق الإلكترونية" : "Al-Mashreq eBookstore";
    const fullTitle = author ? `${title} - ${author} | ${siteName}` : `${title} | ${siteName}`;
    document.title = fullTitle;

    let metaDesc = synopsis
      ? synopsis.replace(/\s+/g, " ").trim()
      : (isAr ? `تصفح كتاب ${title}${author ? ` بقلم ${author}` : ""} عبر مكتبة المشرق الإلكترونية.` : `Explore ${title}${author ? ` by ${author}` : ""} on Al-Mashreq eBookstore.`);
    if (metaDesc.length > 160) metaDesc = metaDesc.slice(0, 157) + "...";

    const origin = window.location.origin && window.location.origin !== "null"
      ? window.location.origin
      : "https://almashreqebookstore.com";
    const canonicalUrl = isbn ? `${origin}/book/${encodeURIComponent(isbn)}` : window.location.href;
    const coverAbsolute = cover
      ? (cover.startsWith("http") ? cover : `${origin}${cover.startsWith("/") ? "" : "/"}${cover}`)
      : `${origin}/assets/logo.png`;

    function setMeta(key, value, isProperty = false) {
      if (!value) return;
      const selector = isProperty ? `meta[property="${key}"]` : `meta[name="${key}"]`;
      let el = document.querySelector(selector);
      if (!el) {
        el = document.createElement("meta");
        el.setAttribute(isProperty ? "property" : "name", key);
        document.head.appendChild(el);
      }
      el.setAttribute("content", value);
    }

    setMeta("description", metaDesc, false);
    setMeta("og:site_name", siteName, true);
    setMeta("og:type", "book", true);
    setMeta("og:title", fullTitle, true);
    setMeta("og:description", metaDesc, true);
    setMeta("og:url", canonicalUrl, true);
    setMeta("og:image", coverAbsolute, true);
    setMeta("og:locale", isAr ? "ar_AR" : "en_US", true);
    setMeta("twitter:card", "summary_large_image", false);
    setMeta("twitter:title", fullTitle, false);
    setMeta("twitter:description", metaDesc, false);
    setMeta("twitter:image", coverAbsolute, false);

    let canonicalEl = document.querySelector('link[rel="canonical"]');
    if (!canonicalEl) {
      canonicalEl = document.createElement("link");
      canonicalEl.setAttribute("rel", "canonical");
      document.head.appendChild(canonicalEl);
    }
    canonicalEl.setAttribute("href", canonicalUrl);

    // Dynamic Schema.org Book JSON-LD
    const bookSchema = {
      "@context": "https://schema.org",
      "@type": "Book",
      "name": title,
      "headline": title,
      "url": canonicalUrl,
      "image": coverAbsolute,
      "bookFormat": "https://schema.org/EBook",
      "inLanguage": norm(book.Language) || (isAr ? "ar" : "en"),
      ...(author ? { "author": { "@type": "Person", "name": author } } : {}),
      ...(isbn ? { "isbn": isbn } : {}),
      ...(publisher ? { "publisher": { "@type": "Organization", "name": publisher } } : {}),
      "description": synopsis || metaDesc
    };

    let schemaScript = document.getElementById("bookSchemaJsonLd");
    if (!schemaScript) {
      schemaScript = document.createElement("script");
      schemaScript.id = "bookSchemaJsonLd";
      schemaScript.type = "application/ld+json";
      document.head.appendChild(schemaScript);
    }
    schemaScript.textContent = JSON.stringify(bookSchema, null, 2);
  }

  let currentBook = null;

  function render(book){
    currentBook = book;
    const isAr = (document.documentElement.getAttribute("dir") || "rtl") === "rtl";
    const title = norm(book.Title) || "—";
    const subtitle = norm(book.Subtitle);
    const author = norm(book.Author);
    const series = norm(book.Series);
    const isbn = norm(book.ISBN);

    const bisac =
      norm(book.Bisac) ||
      norm(book.BISAC) ||
      norm(book.bisac_code) ||
      norm(book["Bisac codes"]) ||
      norm(book.BisacCodes);

    const synopsis =
      norm(book.Synopsis) ||
      norm(book.Description) ||
      norm(book.Blurb);

    // Newly added metadata fields (Phase 8/9)
    const illustrator = norm(book.illustrator || book.Illustrator);
    const translator = norm(book.translator || book.Translator);
    const editor = norm(book.editor || book.Editor);
    const publisher = norm(book.Publisher || book.publisher?.name || (typeof book.publisher === "string" ? book.publisher : ""));
    const publicationDate = norm(book.PublicationDate || book.publication_date);
    const pages = norm(book.Pages || (book.pages !== null && book.pages !== undefined ? String(book.pages) : ""));
    const format = norm(book.Format || book.format);
    const ageGroup = norm(book.AgeGroup || book.age_group || book.age_range || book.Age);
    const authorBio = norm(book.AuthorBio || book.author_bio || book.author?.biography);
    const seriesPosition = norm(book.SeriesPosition || book.series_position);
    const originalLanguage = norm(book.OriginalLanguage || book.original_language);
    const catalogCode = norm(book.catalog_code || book.CatalogCode);
    const wgsCode = norm(book.wgs_code || book.WgsCode);
    const themaCode = norm(book.thema_code || book.ThemaCode);

    const keywords = getList(book.Keywords || book.keywords);
    const links = allLinks(book);
    const cover = coverSrc(book);

    // Dynamic bilingual labels
    const lblBy = isAr ? "بقلم:" : "By:";
    const lblSeriesBadge = isAr ? "سلسلة:" : "Series:";
    const lblData = isAr ? "بيانات الكتاب ومواصفات الإصدار" : "Book & Edition Details";
    const lblSubtitle = isAr ? "العنوان الفرعي" : "Subtitle";
    const lblAuthor = isAr ? "المؤلف" : "Author";
    const lblIllustrator = isAr ? "الرسام" : "Illustrator";
    const lblTranslator = isAr ? "المترجم" : "Translator";
    const lblEditor = isAr ? "المحرر" : "Editor";
    const lblPub = isAr ? "دار النشر" : "Publisher";
    const lblPublicationDate = isAr ? "تاريخ النشر" : "Publication Date";
    const lblPages = isAr ? "عدد الصفحات" : "Page Count";
    const lblFormat = isAr ? "صيغة الكتاب" : "Format";
    const lblAgeGroup = isAr ? "الفئة العمرية" : "Age Group";
    const lblSeries = isAr ? "السلسلة" : "Series";
    const lblSeriesPos = isAr ? "الموقع في السلسلة" : "Series Position";
    const lblLang = isAr ? "لغة الكتاب" : "Language";
    const lblOriginalLang = isAr ? "اللغة الأصلية" : "Original Language";
    const lblCat = isAr ? "التصنيف الرئيسي" : "Category";
    const lblSubCat = isAr ? "التصنيف الفرعي" : "Subcategory";
    const lblSynopsis = isAr ? "نبذة عن الكتاب" : "Synopsis";
    const lblAuthorBio = isAr ? "نبذة عن المؤلف" : "About the Author";
    const lblNoCover = isAr ? "لا توجد صورة غلاف" : "No cover available";
    const lblNoticeMissing = isAr ? "لم يتم العثور على هذا الكتاب." : "Book not found.";
    const lblNoticeBack = isAr ? "العودة للمكتبة" : "Back to library";

    // Dynamic SEO, Open Graph, Twitter Cards & Schema.org JSON-LD
    updateDynamicSEO(book, isAr);

    const coverAlt = isAr
      ? `غلاف كتاب ${esc(title)}${author ? ` للمؤلف ${esc(author)}` : ""}`
      : `Cover of ${esc(title)}${author ? ` by ${esc(author)}` : ""}`;

    host.innerHTML = `
      <div class="book-layout">
        <div class="book-media card-lite">
          ${cover ? `
            <img class="book-detail-cover" src="${esc(cover)}" alt="${coverAlt}" loading="eager" decoding="async"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="book-cover-missing" style="display:none">${lblNoCover}</div>
          ` : `<div class="book-cover-missing">${lblNoCover}</div>`}
        </div>

        <div class="book-content">
          <header class="book-header">
            <div class="book-headline">
              <div>
                <h1 class="book-h1">${esc(title)}</h1>
                ${subtitle ? `<p class="book-subtitle">${esc(subtitle)}</p>` : ""}
                ${author ? `<p class="book-byline">${lblBy} <strong>${esc(author)}</strong></p>` : ""}
              </div>
            </div>

            <div class="badges" aria-label="ملخص سريع">
              ${badge(norm(book.CategoryMain || book.category?.name), "badge-cat")}
              ${badge(norm(book.Language), "badge-lang")}
              ${badge(format ? `${format}` : "", "badge-format")}
              ${badge(ageGroup ? `${isAr ? "الفئة: " : "Age: "}${ageGroup}` : "", "badge-age")}
              ${badge(series ? `${lblSeriesBadge} ${series}${seriesPosition ? ` (#${seriesPosition})` : ""}` : "", "badge-series")}
              ${badge(isbn ? `ISBN: ${isbn}` : "", "badge-isbn")}
            </div>
          </header>

          <section class="book-block">
            <h3 class="book-h3">${lblData}</h3>
            <div class="kv-grid">
              ${kv(lblSubtitle, subtitle)}
              ${kv(lblAuthor, author)}
              ${kv(lblPub, publisher)}
              ${kv(lblTranslator, translator)}
              ${kv(lblEditor, editor)}
              ${kv(lblIllustrator, illustrator)}
              ${kv(lblSeries, series)}
              ${kv(lblSeriesPos, seriesPosition)}
              ${kv(lblPublicationDate, publicationDate)}
              ${kv(lblPages, pages ? (isAr ? `${pages} صفحة` : `${pages} pages`) : "")}
              ${kv(lblFormat, format)}
              ${kv(lblAgeGroup, ageGroup)}
              ${kv(lblLang, norm(book.Language))}
              ${kv(lblOriginalLang, originalLanguage)}
              ${kv(lblCat, norm(book.CategoryMain || book.category?.name))}
              ${kv(lblSubCat, norm(book.Subject_2 || book.sub_category?.name))}
              ${kv("ISBN", isbn)}
              ${kv("BISAC", bisac)}
              ${kv("WGS Code", wgsCode)}
              ${kv("Thema Code", themaCode)}
              ${kv("Catalog Code", catalogCode)}
            </div>
          </section>

          ${synopsis ? `
            <section class="book-block">
              <h3 class="book-h3">${lblSynopsis}</h3>
              <p class="book-synopsis">${esc(synopsis)}</p>
            </section>
          ` : ""}

          ${authorBio ? `
            <section class="book-block book-author-bio-block">
              <h3 class="book-h3">${lblAuthorBio}</h3>
              <p class="book-author-bio">${esc(authorBio)}</p>
            </section>
          ` : ""}

          ${chips("Keywords", keywords)}

          ${links.length ? renderLinks(links) : ""}
        </div>
      </div>
    `;

    // Sticky buy bar on mobile
    if (buybar && buybarLinks){
      if (links.length){
        buybarLinks.innerHTML = links.map(l => `
          <a class="book-cta-btn book-cta-sm" href="${esc(l.url)}" target="_blank" rel="noopener">${esc(l.name)}</a>
        `).join("");
        buybar.style.display = "";
      } else {
        buybar.style.display = "none";
      }
    }
  }

  // Extract ISBN supporting both URL query parameter (?isbn=...) and clean path (/book/...)
  function extractIsbn() {
    // 1. Try URLSearchParams first (e.g., ?isbn=123... or ?ISBN=123...)
    try {
      const params = new URLSearchParams(window.location.search);
      const qIsbn = norm(params.get("isbn") || params.get("ISBN"));
      if (qIsbn) return qIsbn;
    } catch (_) {}

    // 2. If not found in query params, extract from window.location.pathname (e.g., /book/123...)
    try {
      const pathname = window.location.pathname || "";
      const match = pathname.match(/\/book\/([^/?#]+)/i);
      if (match && match[1]) {
        const clean = decodeURIComponent(match[1]).replace(/\.html$/i, "").trim();
        if (clean) return norm(clean);
      }
    } catch (_) {}

    return "";
  }

  try{
    let isbn = extractIsbn();
    const id = getParam("id") || getParam("ID");
    const target = isbn || id;

    let found = null;

    // 0. Instant Cache Check from sessionStorage (0ms latency if navigated from catalog)
    try {
      const rawCache = sessionStorage.getItem("mashreq_catalog_cache_v3");
      if (rawCache) {
        const parsed = JSON.parse(rawCache);
        if (parsed && Array.isArray(parsed.books)) {
          const cleanIsbn = isbn ? isbn.replace(/[^0-9Xx]/g, "") : "";
          const cachedMatch = parsed.books.find(b => {
            const bIsbn = norm(b.ISBN || b.isbn);
            const bClean = bIsbn ? bIsbn.replace(/[^0-9Xx]/g, "") : "";
            return (
              (isbn && (bIsbn === isbn || (cleanIsbn && bClean === cleanIsbn))) ||
              (id && String(b._id || b.id) === String(id))
            );
          });
          if (cachedMatch) {
            found = cachedMatch;
            render(found); // Render immediately from cache for 0ms initial load
          }
        }
      }
    } catch (_) {}

    // 1. Try fetching directly from Laravel API for this specific book
    if (target) {
      const singleEndpoints = [
        `https://api.almashreqebookstore.com/api/v1/books/${encodeURIComponent(target)}`
      ];

      for (const ep of singleEndpoints) {
        try {
          const res = await fetch(ep, {
            cache: "no-store",
            headers: { Accept: "application/json" }
          });
          if (res.ok) {
            const json = await res.json();
            if (json.data || json.book) {
              found = json.data || json.book;
              render(found);
              break;
            }
          } else {
            console.error(`[book.js] HTTP ${res.status} from book endpoint: ${ep}`);
          }
        } catch (err) {
          console.error(`[book.js] Network/CORS error fetching book from [${ep}]:`, err);
        }
      }
    }

    // 2. Fallback: Search from API using fast targeted query (?q=...) instead of ?all=true
    if (!found && target) {
      const searchEndpoints = [
        `https://api.almashreqebookstore.com/api/v1/books?q=${encodeURIComponent(target)}&per_page=10`
      ];

      for (const listEp of searchEndpoints) {
        try {
          const res = await fetch(listEp, {
            cache: "no-store",
            headers: { Accept: "application/json" }
          });
          if (res.ok) {
            const data = await res.json();
            const all = Array.isArray(data.books) ? data.books : (Array.isArray(data.data) ? data.data : []);
            const cleanIsbn = isbn ? isbn.replace(/[^0-9Xx]/g, "") : "";
            found = all.find(b => {
              const bIsbn = norm(b.ISBN || b.isbn);
              const bClean = bIsbn ? bIsbn.replace(/[^0-9Xx]/g, "") : "";
              return (
                (isbn && (bIsbn === isbn || (cleanIsbn && bClean === cleanIsbn))) ||
                (id && String(b._id || b.id) === String(id))
              );
            });
            if (found) {
              render(found);
              break;
            }
          } else {
            console.error(`[book.js] HTTP ${res.status} from search endpoint: ${listEp}`);
          }
        } catch (err) {
          console.error(`[book.js] Network/CORS error fetching books list from [${listEp}]:`, err);
        }
      }
    }

    if (!found){
      host.innerHTML = `
        <div class="notice">
          لم يتم العثور على هذا الكتاب. <a href="/">العودة للمكتبة</a>
        </div>
      `;
      if (buybar) buybar.style.display = "none";
      return;
    }

    render(found);
  } catch(e){
    console.error(e);
    host.innerHTML = `<div class="notice">تعذر تحميل بيانات الكتاب. تأكد من اتصال الخادم.</div>`;
    if (buybar) buybar.style.display = "none";
  }

  // Hide sticky buybar when footer enters viewport to avoid any overlay or click blockage
  const footerEl = document.querySelector(".site-footer");
  if (buybar && footerEl && window.IntersectionObserver) {
    const footerObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          buybar.classList.add("is-hidden");
        } else {
          buybar.classList.remove("is-hidden");
        }
      });
    }, { rootMargin: "0px 0px 40px 0px" });
    footerObserver.observe(footerEl);
  }

  // Listen to language change to re-render bilingual book fields
  window.addEventListener("languagechange", function(){
    if (currentBook) render(currentBook);
  });

  // Smooth back-to-catalog navigation using browser history
  const backBtn = document.querySelector(".book-back");
  if (backBtn) {
    backBtn.addEventListener("click", function(e) {
      if (window.history.length > 1 && (document.referrer.includes("index") || document.referrer === "" || window.history.state !== null)) {
        e.preventDefault();
        window.history.back();
      }
    });
  }
})();