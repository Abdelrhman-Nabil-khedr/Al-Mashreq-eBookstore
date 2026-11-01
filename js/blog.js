(async function () {
  const listEl = document.getElementById("blogList");
  if (!listEl) return;

  function normalizeNewlines(s) {
    return (s || "")
      .toString()
      .replace(/\r\n/g, "\n")
      .replace(/\\n/g, "\n");
  }

  // عناوين الأقسام التي تريدها Bold (طابق نصوصك حرفيًا)
  const SECTION_TITLES = new Set([
    "البداية",
    "النمو",
    "العلاقة مع القارئ",
    "العبرة",
    "اليوم... وغدًا",
  ]);

  function renderContentWithBoldHeadings(rawText) {
    const text = normalizeNewlines(rawText);
    const lines = text.split("\n");

    const wrap = document.createElement("div");
    wrap.style.cssText =
      "margin-top:10px;direction:rtl;text-align:right;color:inherit";

    // سطر 1: عنوان المقال داخل المحتوى (Bold أكبر)
    if (lines.length > 0 && lines[0].trim()) {
      const t1 = document.createElement("div");
      t1.style.cssText = "font-weight:800;font-size:1.15rem;line-height:1.8";
      t1.textContent = lines[0];
      wrap.appendChild(t1);
    }

    // سطر 2: السطر الاقتباسي (مائل + Bold خفيف)
    if (lines.length > 1 && lines[1].trim()) {
      const t2 = document.createElement("div");
      t2.style.cssText =
        "font-weight:700;font-size:1.05rem;opacity:.95;line-height:1.8";
      t2.textContent = lines[1];
      wrap.appendChild(t2);
    }

    // باقي السطور: نبنيها سطرًا سطرًا للحفاظ على نفس الشكل
    for (let i = 2; i < lines.length; i++) {
      const line = lines[i];

      // سطر فارغ => مسافة كما هي
      if (line.trim() === "") {
        const spacer = document.createElement("div");
        spacer.style.cssText = "height:.85rem";
        wrap.appendChild(spacer);
        continue;
      }

      // عناوين الأقسام (Bold)
      if (SECTION_TITLES.has(line.trim())) {
        const h = document.createElement("div");
        h.style.cssText =
          "margin-top:.25rem;font-weight:800;font-size:1.05rem;line-height:1.9";
        h.textContent = line.trim();
        wrap.appendChild(h);
        continue;
      }

      // باقي السطور كنص عادي يحافظ على الالتفاف
      const p = document.createElement("div");
      p.style.cssText =
        "white-space:pre-wrap;line-height:1.9;font-size:1rem";
      p.textContent = line;
      wrap.appendChild(p);
    }

    return wrap;
  }

  // 1. Fetch posts from Dynamic API with fallback to static JSON
  let posts = [];
  const endpoints = [
    "https://api.almashreqebookstore.com/api/v1/blog"
  ];

  for (const ep of endpoints) {
    try {
      const res = await fetch(ep, { cache: "no-store", headers: { Accept: "application/json" } });
      if (res.ok) {
        const json = await res.json();
        if (Array.isArray(json.data)) {
          // Dynamic API response shape
          posts = json.data.map(p => ({
            id: p.id,
            title: p.title,
            date: p.published_at ? p.published_at.substring(0, 10) : (p.date || ""),
            excerpt: p.excerpt || "",
            content: p.content || "",
            cover: p.cover_image_url || p.cover_image || null
          }));
          break;
        } else if (Array.isArray(json.posts)) {
          // Legacy static JSON shape
          posts = json.posts;
          break;
        }
      } else {
        console.error(`[blog.js] HTTP ${res.status} from blog endpoint: ${ep}`);
      }
    } catch (e) {
      console.error(`[blog.js] Network/CORS error fetching blog posts from [${ep}]:`, e);
    }
  }

  if (!posts || posts.length === 0) {
    listEl.innerHTML = '<div class="notice">تعذر تحميل مقالات المدونة حالياً.</div>';
    return;
  }

  posts.sort((a, b) => (b.date || "").localeCompare(a.date || ""));
  listEl.innerHTML = "";

  for (const p of posts) {
    const article = document.createElement("article");
    article.className = "card";
    article.style.cssText =
      "border-radius:16px;padding:20px;margin-bottom:16px;overflow:hidden";

    // Optional Cover Image
    if (p.cover && !p.cover.includes("logo.png")) {
      const imgWrap = document.createElement("div");
      imgWrap.style.cssText = "margin:-20px -20px 16px -20px;max-height:280px;overflow:hidden";
      const img = document.createElement("img");
      img.src = p.cover;
      img.alt = p.title || "صورة الغلاف";
      img.style.cssText = "width:100%;height:100%;object-fit:cover;display:block";
      imgWrap.appendChild(img);
      article.appendChild(imgWrap);
    }

    const h2 = document.createElement("h2");
    h2.style.cssText = "margin:0 0 6px;font-size:1.25rem;font-weight:800;color:var(--text-color)";
    h2.textContent = p.title || "";
    article.appendChild(h2);

    const date = document.createElement("div");
    date.style.cssText = "opacity:.75;font-size:13px;margin-bottom:10px;color:var(--muted-color)";
    date.textContent = p.date || "";
    article.appendChild(date);

    const excerpt = document.createElement("p");
    excerpt.style.cssText = "margin:0 0 12px;line-height:1.9;color:var(--text-color)";
    excerpt.textContent = p.excerpt || "";
    article.appendChild(excerpt);

    const details = document.createElement("details");

    const summary = document.createElement("summary");
    summary.style.cssText = "cursor:pointer;font-weight:800;color:var(--blue);padding:6px 0";
    summary.textContent = "اقرأ المقال الكامل";
    details.appendChild(summary);

    // If content has HTML tags, render HTML; otherwise use plain text formatter
    const rawContent = p.content || "";
    if (/<[a-z][\s\S]*>/i.test(rawContent)) {
      const htmlWrap = document.createElement("div");
      htmlWrap.style.cssText = "margin-top:14px;line-height:1.9;color:var(--text-color);font-size:1rem";
      htmlWrap.innerHTML = rawContent;
      details.appendChild(htmlWrap);
    } else {
      details.appendChild(renderContentWithBoldHeadings(rawContent));
    }

    article.appendChild(details);
    listEl.appendChild(article);
  }
})();
