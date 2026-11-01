(async function () {

  // ==========================
  // Priority display logic ONLY (Arabic Fiction first)
  // ==========================
  const PRIORITY_CAT = ["Fiction", "روايات", "الروايات", "Fiction = روايات", "Arabic Fiction"];
  const PRIORITY_LANG = ["Arabic", "العربية", "عربي", "AR", "ar"];

  function norm(s) { return (s || "").toString().trim(); }

  function isPriorityBook(b) {
    const cat = norm(b.CategoryMain);
    const lang = norm(b.Language);

    const catMatch = PRIORITY_CAT.some(x =>
      cat === x || cat.toLowerCase().includes(String(x).toLowerCase())
    );
    const langMatch = PRIORITY_LANG.some(x =>
      lang === x || lang.toLowerCase().includes(String(x).toLowerCase())
    );

    return catMatch && langMatch;
  }

  // ==========================
  // DOM Elements
  // ==========================
  const els = {
    q: document.getElementById("q"),
    mainCat: document.getElementById("mainCat"),
    subject2: document.getElementById("subject2"),
    author: document.getElementById("author"),
    lang: document.getElementById("lang"),
    viewBy: document.getElementById("viewBy"),
    sortBy: document.getElementById("sortBy"),
    reset: document.getElementById("reset"),
    results: document.getElementById("results"),
    pagination: document.getElementById("pagination"),
    kpiTotal: document.getElementById("kpiTotal"),
    kpiShown: document.getElementById("kpiShown"),
    kpiCats: document.getElementById("kpiCats"),
    generatedAt: document.getElementById("generatedAt"),
  };

  const PAGE_SIZE = 24;
  let currentPage = 1;
  let totalBooksCount = 0;
  let ALL = [];
  let META = { generated_at: null, count: 0 };
  let customDropdowns = null;
  let apiAuthors = [];
  let apiCategories = [];
  let isFetching = false;

  // ==========================
  // Internationalization (i18n) & Language Support
  // ==========================
  let currentLang = localStorage.getItem("lang") || "ar";

  const I18N = {
    ar: {
      "brand.title": "مكتبة المشرق الإلكترونية",
      "nav.home": "الرئيسية",
      "nav.latest": "أحدث الإصدارات",
      "nav.vision": "الرؤية والمهمة",
      "nav.about": "من نحن",
      "nav.blog": "المدونة",
      "nav.contact": "اتصل بنا",
      "hero.sub": "كلّ فكرة تستحق أن تُروى، وكلّ قصة تستحق أن تُسمع.",
      "kpi.total": "إجمالي الكتب",
      "kpi.shown": "المعروض الآن",
      "kpi.cats": "التصنيفات",
      "filters.heading": "بحث وتصفية الكتب",
      "filters.toggle": "خيارات التصفية",
      "filters.hide": "إخفاء الفلاتر",
      "filters.searchLabel": "بحث شامل",
      "filters.searchPlaceholder": "ابحث بالعنوان، المؤلف، ISBN، كلمات مفتاحية...",
      "filters.mainCatLabel": "التصنيف الرئيسي",
      "filters.subCatLabel": "التصنيف الفرعي",
      "filters.authorLabel": "اسم المؤلف",
      "filters.langLabel": "اللغة",
      "filters.viewLabel": "العرض حسب",
      "filters.sortLabel": "الترتيب",
      "filters.resetBtn": "إعادة ضبط",
      "filters.mainCatPlaceholder": "كل التصنيفات الرئيسية",
      "filters.subCatDisabled": "اختر التصنيف الرئيسي أولاً",
      "filters.subCatAll": "كل التصنيفات الفرعية",
      "filters.subCatNone": "لا توجد تصنيفات فرعية لهذا القسم",
      "filters.authorPlaceholder": "كل المؤلفين",
      "filters.langPlaceholder": "كل اللغات",
      "filters.viewAuthor": "المؤلف",
      "filters.sortAuthor": "حسب المؤلف",
      "foot.vision": "الرؤية",
      "foot.latest": "أحدث الإصدارات",
      "foot.blog": "المدونة",
      "foot.contact": "اتصل بنا",
      "foot.terms": "الشروط والأحكام",
      "foot.privacy": "سياسة الخصوصية",
      "footer.desc": "الاحترافية — التناغم — التواصل",
      "footer.copy": "مكتبة المشرق الإلكترونية — جميع الحقوق محفوظة",
      "footer.generated": "Generated from Excel",
      "auth.loginOrRegister": "تسجيل الدخول / حساب جديد",
      "auth.hello": "مرحباً،",
      "auth.logout": "تسجيل الخروج",
      "auth.loginTitle": "تسجيل الدخول",
      "auth.registerTitle": "حساب جديد",
      "auth.modalSub": "سجل الدخول أو أنشئ حساباً للاستمتاع بجميع ميزات المكتبة",
      "auth.email": "البريد الإلكتروني",
      "auth.password": "كلمة المرور",
      "auth.passwordConfirm": "تأكيد كلمة المرور",
      "auth.name": "الاسم الكامل",
      "auth.submitLogin": "تسجيل الدخول",
      "auth.submitRegister": "إنشاء حساب جديد",
      "auth.noAccount": "ليس لديك حساب؟",
      "auth.haveAccount": "لديك حساب بالفعل؟",
      themeDark: "داكن",
      themeLight: "فاتح",
      viewBy: {
        author: "المؤلف",
        category: "التصنيف",
        language: "اللغة",
        alpha: "الترتيب الأبجدي"
      },
      sortBy: {
        author: "حسب المؤلف",
        az: "A → Z (العنوان)",
        za: "Z → A (العنوان)",
        lang: "حسب اللغة"
      },
      noResults: "لا توجد نتائج مطابقة للبحث."
    },
    en: {
      "brand.title": "Al-Mashreq eBookstore",
      "nav.home": "Home",
      "nav.latest": "Latest Books",
      "nav.vision": "Vision & Mission",
      "nav.about": "About Us",
      "nav.blog": "Blog",
      "nav.contact": "Contact Us",
      "hero.sub": "Every idea deserves to be told, and every story deserves to be heard.",
      "kpi.total": "Total Books",
      "kpi.shown": "Books Shown",
      "kpi.cats": "Categories",
      "filters.heading": "Search & Filter Books",
      "filters.toggle": "Filter Options",
      "filters.hide": "Hide Filters",
      "filters.searchLabel": "Global Search",
      "filters.searchPlaceholder": "Search by title, author, ISBN, keywords...",
      "filters.mainCatLabel": "Main Category",
      "filters.subCatLabel": "Subcategory",
      "filters.authorLabel": "Author Name",
      "filters.langLabel": "Language",
      "filters.viewLabel": "Group By",
      "filters.sortLabel": "Sort By",
      "filters.resetBtn": "Reset",
      "filters.mainCatPlaceholder": "All Main Categories",
      "filters.subCatDisabled": "Select Main Category First",
      "filters.subCatAll": "All Subcategories",
      "filters.subCatNone": "No subcategories for this section",
      "filters.authorPlaceholder": "All Authors",
      "filters.langPlaceholder": "All Languages",
      "filters.viewAuthor": "Author",
      "filters.sortAuthor": "By Author",
      "foot.vision": "Vision",
      "foot.latest": "Latest Books",
      "foot.blog": "Blog",
      "foot.contact": "Contact Us",
      "foot.terms": "Terms & Conditions",
      "foot.privacy": "Privacy Policy",
      "footer.desc": "Professionalism — Harmony — Communication",
      "footer.copy": "Al-Mashreq eBookstore — All Rights Reserved",
      "footer.generated": "Generated from Excel",
      "auth.loginOrRegister": "Login / Register",
      "auth.hello": "Hello,",
      "auth.logout": "Logout",
      "auth.loginTitle": "Login",
      "auth.registerTitle": "Create Account",
      "auth.modalSub": "Log in or create an account to access all library features",
      "auth.email": "Email Address",
      "auth.password": "Password",
      "auth.passwordConfirm": "Confirm Password",
      "auth.name": "Full Name",
      "auth.submitLogin": "Log In",
      "auth.submitRegister": "Create Account",
      "auth.noAccount": "Don't have an account?",
      "auth.haveAccount": "Already have an account?",
      themeDark: "Dark",
      themeLight: "Light",
      viewBy: {
        author: "Author",
        category: "Category",
        language: "Language",
        alpha: "Alphabetical"
      },
      sortBy: {
        author: "By Author",
        az: "Title: A → Z",
        za: "Title: Z → A",
        lang: "By Language"
      },
      noResults: "No books match your search."
    }
  };

  // ==========================
  // Theme Management (Dark / Light)
  // ==========================
  function updateThemeToggleUI(theme) {
    const themeBtn = document.getElementById("themeToggle");
    if (!themeBtn) return;

    const isDark = theme === "dark";
    const label = isDark
      ? (currentLang === "ar" ? "الوضع الفاتح" : "Light Mode")
      : (currentLang === "ar" ? "الوضع الداكن" : "Dark Mode");
    themeBtn.setAttribute("aria-label", label);
    themeBtn.setAttribute("title", label);

    const textSpan = themeBtn.querySelector(".action-btn-text");
    if (textSpan) {
      textSpan.textContent = isDark ? I18N[currentLang].themeLight : I18N[currentLang].themeDark;
    }

    const existingSvg = themeBtn.querySelector("svg");
    if (existingSvg) {
      existingSvg.outerHTML = isDark
        ? `<svg class="theme-icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`
        : `<svg class="theme-icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
    }
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem("theme", theme);
    updateThemeToggleUI(theme);
  }

  // ==========================
  // Strict Category Translation Mapping (Bidirectional AR <-> EN)
  // ==========================
  const CATEGORY_TRANSLATIONS = window.CATEGORY_TRANSLATIONS || {
    "علوم": { ar: "علوم", en: "Science" },
    "science": { ar: "علوم", en: "Science" },
    "علوم وتكنولوجيا": { ar: "علوم وتكنولوجيا", en: "Science & Technology" },
    "science & technology": { ar: "علوم وتكنولوجيا", en: "Science & Technology" },
    "أدب عربي": { ar: "أدب عربي", en: "Arabic Literature" },
    "arabic literature": { ar: "أدب عربي", en: "Arabic Literature" },
    "أدب التراث العربي": { ar: "أدب التراث العربي", en: "Arab Classic Literature" },
    "arab classic literature": { ar: "أدب التراث العربي", en: "Arab Classic Literature" },
    "كتب التراث العربي": { ar: "كتب التراث العربي", en: "Arabic Heritage Books" },
    "arabic heritage books": { ar: "كتب التراث العربي", en: "Arabic Heritage Books" },
    "تراث": { ar: "تراث", en: "Heritage" },
    "heritage": { ar: "تراث", en: "Heritage" },
    "روايات": { ar: "روايات", en: "Fiction" },
    "fiction": { ar: "روايات", en: "Fiction" },
    "روايات وأدب": { ar: "روايات وأدب", en: "Fiction & Literature" },
    "fiction & literature": { ar: "روايات وأدب", en: "Fiction & Literature" },
    "أدب": { ar: "أدب", en: "Literature" },
    "literature": { ar: "أدب", en: "Literature" },
    "أدب كلاسيكي": { ar: "أدب كلاسيكي", en: "Classic Literature" },
    "classic literature": { ar: "أدب كلاسيكي", en: "Classic Literature" },
    "كلاسيكيات": { ar: "كلاسيكيات", en: "Classics" },
    "classics": { ar: "كلاسيكيات", en: "Classics" },
    "رواية": { ar: "رواية", en: "Novel" },
    "novel": { ar: "رواية", en: "Novel" },
    "روايات": { ar: "روايات", en: "Novels" },
    "novels": { ar: "روايات", en: "Novels" },
    "كتب عامة وفكرية": { ar: "كتب عامة وفكرية", en: "Nonfiction" },
    "nonfiction": { ar: "كتب عامة وفكرية", en: "Nonfiction" },
    "غير روائي": { ar: "غير روائي", en: "Nonfiction" },
    "nonfiction": { ar: "غير روائي", en: "Nonfiction" },
    "قصص وكتب أطفال": { ar: "قصص وكتب أطفال", en: "Children's Fiction" },
    "children's fiction": { ar: "قصص وكتب أطفال", en: "Children's Fiction" },
    "كتب أطفال": { ar: "كتب أطفال", en: "Children's Books" },
    "children's books": { ar: "كتب أطفال", en: "Children's Books" },
    "قصص أطفال": { ar: "قصص أطفال", en: "Children's Stories" },
    "children's stories": { ar: "قصص أطفال", en: "Children's Stories" },
    "كتب الأطفال واليافعين": { ar: "كتب الأطفال واليافعين", en: "Children & Youth" },
    "children & youth": { ar: "كتب الأطفال واليافعين", en: "Children & Youth" },
    "روايات الناشئة واليافعين": { ar: "روايات الناشئة واليافعين", en: "Young Adult Fiction" },
    "young adult fiction": { ar: "روايات الناشئة واليافعين", en: "Young Adult Fiction" },
    "كتب اليافعين": { ar: "كتب اليافعين", en: "Young Adult" },
    "young adult": { ar: "كتب اليافعين", en: "Young Adult" },
    "يافعين": { ar: "يافعين", en: "Young Adult" },
    "young adult": { ar: "يافعين", en: "Young Adult" },
    "شعر": { ar: "شعر", en: "Poetry" },
    "poetry": { ar: "شعر", en: "Poetry" },
    "شعر وأدب شعري": { ar: "شعر وأدب شعري", en: "Poetry & Poetic Literature" },
    "poetry & poetic literature": { ar: "شعر وأدب شعري", en: "Poetry & Poetic Literature" },
    "تاريخ": { ar: "تاريخ", en: "History" },
    "history": { ar: "تاريخ", en: "History" },
    "تاريخ وحضارة": { ar: "تاريخ وحضارة", en: "History & Civilization" },
    "history & civilization": { ar: "تاريخ وحضارة", en: "History & Civilization" },
    "فلسفة": { ar: "فلسفة", en: "Philosophy" },
    "philosophy": { ar: "فلسفة", en: "Philosophy" },
    "فلسفة وفكر": { ar: "فلسفة وفكر", en: "Philosophy & Thought" },
    "philosophy & thought": { ar: "فلسفة وفكر", en: "Philosophy & Thought" },
    "دراسات إسلامية": { ar: "دراسات إسلامية", en: "Islamic Studies" },
    "islamic studies": { ar: "دراسات إسلامية", en: "Islamic Studies" },
    "دراسات إسلامية ودينية": { ar: "دراسات إسلامية ودينية", en: "Religion & Spirituality" },
    "religion & spirituality": { ar: "دراسات إسلامية ودينية", en: "Religion & Spirituality" },
    "دين": { ar: "دين", en: "Religion" },
    "religion": { ar: "دين", en: "Religion" },
    "سير وتراجم": { ar: "سير وتراجم", en: "Biography & Autobiography" },
    "biography & autobiography": { ar: "سير وتراجم", en: "Biography & Autobiography" },
    "سيرة ذاتية": { ar: "سيرة ذاتية", en: "Autobiography" },
    "autobiography": { ar: "سيرة ذاتية", en: "Autobiography" },
    "تنمية بشرية": { ar: "تنمية بشرية", en: "Self-Improvement" },
    "self-improvement": { ar: "تنمية بشرية", en: "Self-Improvement" },
    "تطوير الذات": { ar: "تطوير الذات", en: "Self-Help" },
    "self-help": { ar: "تطوير الذات", en: "Self-Help" },
    "تنمية بشرية وتطوير الذات": { ar: "تنمية بشرية وتطوير الذات", en: "Self-Improvement & Development" },
    "self-improvement & development": { ar: "تنمية بشرية وتطوير الذات", en: "Self-Improvement & Development" },
    "علوم سياسية": { ar: "علوم سياسية", en: "Political Science" },
    "political science": { ar: "علوم سياسية", en: "Political Science" },
    "سياسة": { ar: "سياسة", en: "Politics" },
    "politics": { ar: "سياسة", en: "Politics" },
    "اقتصاد": { ar: "اقتصاد", en: "Economics" },
    "economics": { ar: "اقتصاد", en: "Economics" },
    "اقتصاد وإدارة أعمال": { ar: "اقتصاد وإدارة أعمال", en: "Business & Economics" },
    "business & economics": { ar: "اقتصاد وإدارة أعمال", en: "Business & Economics" },
    "فنون": { ar: "فنون", en: "Arts" },
    "arts": { ar: "فنون", en: "Arts" },
    "فنون وموسيقى": { ar: "فنون وموسيقى", en: "Arts & Music" },
    "arts & music": { ar: "فنون وموسيقى", en: "Arts & Music" },
    "ترجمات عالمية": { ar: "ترجمات عالمية", en: "World Translations" },
    "world translations": { ar: "ترجمات عالمية", en: "World Translations" },
    "حركة ومغامرات": { ar: "حركة ومغامرات", en: "Action & Adventure" },
    "action & adventure": { ar: "حركة ومغامرات", en: "Action & Adventure" },
    "مغامرات": { ar: "مغامرات", en: "Adventure" },
    "adventure": { ar: "مغامرات", en: "Adventure" },
    "عالم الحيوان": { ar: "عالم الحيوان", en: "Animals" },
    "animals": { ar: "عالم الحيوان", en: "Animals" },
    "مختارات أدبية": { ar: "مختارات أدبية", en: "Anthologies" },
    "anthologies": { ar: "مختارات أدبية", en: "Anthologies" },
    "جرائم وبوليسي": { ar: "جرائم وبوليسي", en: "Crime" },
    "crime": { ar: "جرائم وبوليسي", en: "Crime" },
    "دراما ومسرح": { ar: "دراما ومسرح", en: "Drama" },
    "drama": { ar: "دراما ومسرح", en: "Drama" },
    "مسرح": { ar: "مسرح", en: "Theater" },
    "theater": { ar: "مسرح", en: "Theater" },
    "فانتازيا وخيال": { ar: "فانتازيا وخيال", en: "Fantasy" },
    "fantasy": { ar: "فانتازيا وخيال", en: "Fantasy" },
    "خيال وسحر": { ar: "خيال وسحر", en: "Fantasy & Magic" },
    "fantasy & magic": { ar: "خيال وسحر", en: "Fantasy & Magic" },
    "فولكلور وأساطير": { ar: "فولكلور وأساطير", en: "Folklore & Mythology" },
    "folklore & mythology": { ar: "فولكلور وأساطير", en: "Folklore & Mythology" },
    "أساطير": { ar: "أساطير", en: "Mythology" },
    "mythology": { ar: "أساطير", en: "Mythology" },
    "حكايات خرافية وشعبية وأساطير": { ar: "حكايات خرافية وشعبية وأساطير", en: "Fairy Tales, Folk Tales, Legends & Mythology" },
    "fairy tales, folk tales, legends & mythology": { ar: "حكايات خرافية وشعبية وأساطير", en: "Fairy Tales, Folk Tales, Legends & Mythology" },
    "روايات تاريخية": { ar: "روايات تاريخية", en: "Historical Fiction" },
    "historical fiction": { ar: "روايات تاريخية", en: "Historical Fiction" },
    "رعب": { ar: "رعب", en: "Horror" },
    "horror": { ar: "رعب", en: "Horror" },
    "أدب ساخر وفكاهي": { ar: "أدب ساخر وفكاهي", en: "Humorous" },
    "humorous": { ar: "أدب ساخر وفكاهي", en: "Humorous" },
    "سخرية": { ar: "سخرية", en: "Satire" },
    "satire": { ar: "سخرية", en: "Satire" },
    "نقد أدبي": { ar: "نقد أدبي", en: "Literary Criticism" },
    "literary criticism": { ar: "نقد أدبي", en: "Literary Criticism" },
    "غموض وتحقيق": { ar: "غموض وتحقيق", en: "Mystery" },
    "mystery": { ar: "غموض وتحقيق", en: "Mystery" },
    "غموض وبوليسي": { ar: "غموض وبوليسي", en: "Mystery & Detective" },
    "mystery & detective": { ar: "غموض وبوليسي", en: "Mystery & Detective" },
    "أدب نفسي": { ar: "أدب نفسي", en: "Psychological" },
    "psychological": { ar: "أدب نفسي", en: "Psychological" },
    "علم النفس": { ar: "علم النفس", en: "Psychology" },
    "psychology": { ar: "علم النفس", en: "Psychology" },
    "علم الاجتماع": { ar: "علم الاجتماع", en: "Sociology" },
    "sociology": { ar: "علم الاجتماع", en: "Sociology" },
    "علوم اجتماعية": { ar: "علوم اجتماعية", en: "Social Science" },
    "social science": { ar: "علوم اجتماعية", en: "Social Science" },
    "قضايا اجتماعية": { ar: "قضايا اجتماعية", en: "Social Themes" },
    "social themes": { ar: "قضايا اجتماعية", en: "Social Themes" },
    "رومانسية": { ar: "رومانسية", en: "Romance" },
    "romance": { ar: "رومانسية", en: "Romance" },
    "خيال علمي وفانتازيا": { ar: "خيال علمي وفانتازيا", en: "Science Fiction & Fantasy" },
    "science fiction & fantasy": { ar: "خيال علمي وفانتازيا", en: "Science Fiction & Fantasy" },
    "خيال علمي": { ar: "خيال علمي", en: "Science Fiction" },
    "science fiction": { ar: "خيال علمي", en: "Science Fiction" },
    "قصص قصيرة": { ar: "قصص قصيرة", en: "Short Stories" },
    "short stories": { ar: "قصص قصيرة", en: "Short Stories" },
    "تشويق وإثارة": { ar: "تشويق وإثارة", en: "Suspense" },
    "suspense": { ar: "تشويق وإثارة", en: "Suspense" },
    "إثارة وتشويق": { ar: "إثارة وتشويق", en: "Thrillers" },
    "thrillers": { ar: "إثارة وتشويق", en: "Thrillers" },
    "مقالات": { ar: "مقالات", en: "Essays" },
    "essays": { ar: "مقالات", en: "Essays" },
    "تربية وتعليم": { ar: "تربية وتعليم", en: "Education" },
    "education": { ar: "تربية وتعليم", en: "Education" },
    "قانون": { ar: "قانون", en: "Law" },
    "law": { ar: "قانون", en: "Law" },
    "طب وصحة": { ar: "طب وصحة", en: "Medical" },
    "medical": { ar: "طب وصحة", en: "Medical" },
    "طب": { ar: "طب", en: "Medicine" },
    "medicine": { ar: "طب", en: "Medicine" },
    "صحة ولياقة": { ar: "صحة ولياقة", en: "Health & Fitness" },
    "health & fitness": { ar: "صحة ولياقة", en: "Health & Fitness" },
    "علوم وطبيعة": { ar: "علوم وطبيعة", en: "Science & Nature" },
    "science & nature": { ar: "علوم وطبيعة", en: "Science & Nature" },
    "كتب القراءة والمطالعة": { ar: "كتب القراءة والمطالعة", en: "Readers" },
    "readers": { ar: "كتب القراءة والمطالعة", en: "Readers" },
    "تحليلات ودراسات": { ar: "تحليلات ودراسات", en: "Analysis" },
    "analysis": { ar: "تحليلات ودراسات", en: "Analysis" },
    "عصور قديمة وكلاسيكية": { ar: "عصور قديمة وكلاسيكية", en: "Ancient & Classical" },
    "ancient & classical": { ar: "عصور قديمة وكلاسيكية", en: "Ancient & Classical" },
    "العالم القديم": { ar: "العالم القديم", en: "Ancient World" },
    "ancient world": { ar: "العالم القديم", en: "Ancient World" },
    "قديم": { ar: "قديم", en: "Ancient" },
    "ancient": { ar: "قديم", en: "Ancient" },
    "مجموعات ومختارات أدبية": { ar: "مجموعات ومختارات أدبية", en: "Literary Collection" },
    "literary collection": { ar: "مجموعات ومختارات أدبية", en: "Literary Collection" },
    "روايات الغرب الأمريكي": { ar: "روايات الغرب الأمريكي", en: "Westerns" },
    "westerns": { ar: "روايات الغرب الأمريكي", en: "Westerns" },
    "الأسرة والعلاقات": { ar: "الأسرة والعلاقات", en: "Family & Relationships" },
    "family & relationships": { ar: "الأسرة والعلاقات", en: "Family & Relationships" },
    "علوم اللغة وفنون التعبير": { ar: "علوم اللغة وفنون التعبير", en: "Language Arts & Disciplines" },
    "language arts & disciplines": { ar: "علوم اللغة وفنون التعبير", en: "Language Arts & Disciplines" },
    "أدب الرحلات": { ar: "أدب الرحلات", en: "Travel Literature" },
    "travel literature": { ar: "أدب الرحلات", en: "Travel Literature" },
    "الجسد والعقل والروح": { ar: "الجسد والعقل والروح", en: "Body, Mind & Spirit" },
    "body, mind & spirit": { ar: "الجسد والعقل والروح", en: "Body, Mind & Spirit" },
    "الطبيعة وعالم الأحياء": { ar: "الطبيعة وعالم الأحياء", en: "Nature & the Natural World" },
    "nature & the natural world": { ar: "الطبيعة وعالم الأحياء", en: "Nature & the Natural World" },
    "الشرق الأوسط": { ar: "الشرق الأوسط", en: "Middle Eastern" },
    "middle eastern": { ar: "الشرق الأوسط", en: "Middle Eastern" },
    "موسيقى": { ar: "موسيقى", en: "Music" },
    "music": { ar: "موسيقى", en: "Music" },
    "نباتات وزهور": { ar: "نباتات وزهور", en: "Flowers & Plants" },
    "flowers & plants": { ar: "نباتات وزهور", en: "Flowers & Plants" },
    "حيوانات المزرعة": { ar: "حيوانات المزرعة", en: "Farm Animals" },
    "farm animals": { ar: "حيوانات المزرعة", en: "Farm Animals" },
    "كائنات بحرية": { ar: "كائنات بحرية", en: "Marine Life" },
    "marine life": { ar: "كائنات بحرية", en: "Marine Life" },
    "حشرات": { ar: "حشرات", en: "Insects, Spiders, etc" },
    "insects, spiders, etc": { ar: "حشرات", en: "Insects, Spiders, etc" },
    "وحوش وكائنات أسطورية": { ar: "وحوش وكائنات أسطورية", en: "Monsters" },
    "monsters": { ar: "وحوش وكائنات أسطورية", en: "Monsters" },
    "مفاهيم وتعليم مبكر": { ar: "مفاهيم وتعليم مبكر", en: "Concepts" },
    "concepts": { ar: "مفاهيم وتعليم مبكر", en: "Concepts" },
    "طبخ ومأكولات": { ar: "طبخ ومأكولات", en: "Cooking & Food" },
    "cooking & food": { ar: "طبخ ومأكولات", en: "Cooking & Food" },
    "أعياد ومناسبات": { ar: "أعياد ومناسبات", en: "Holidays & Celebrations" },
    "holidays & celebrations": { ar: "أعياد ومناسبات", en: "Holidays & Celebrations" },
    "ألعاب وخيال": { ar: "ألعاب وخيال", en: "Imagination & Play" },
    "imagination & play": { ar: "ألعاب وخيال", en: "Imagination & Play" },
    "قصص قبل النوم وأحلام": { ar: "قصص قبل النوم وأحلام", en: "Bedtime & Dreams" },
    "bedtime & dreams": { ar: "قصص قبل النوم وأحلام", en: "Bedtime & Dreams" },
    "روايات الناشئين": { ar: "روايات الناشئين", en: "Juvenile Fiction" },
    "juvenile fiction": { ar: "روايات الناشئين", en: "Juvenile Fiction" },
    "قصص وأساطير شعبية": { ar: "قصص وأساطير شعبية", en: "Legends, Myths, Fables" },
    "legends, myths, fables": { ar: "قصص وأساطير شعبية", en: "Legends, Myths, Fables" },
    "تنوع أحيائي": { ar: "تنوع أحيائي", en: "Biological Diversity" },
    "biological diversity": { ar: "تنوع أحيائي", en: "Biological Diversity" },
    "طيور": { ar: "طيور", en: "Birds" },
    "birds": { ar: "طيور", en: "Birds" },
    "مهن ووظائف": { ar: "مهن ووظائف", en: "Careers" },
    "careers": { ar: "مهن ووظائف", en: "Careers" },
    "ذوو الاحتياجات الخاصة": { ar: "ذوو الاحتياجات الخاصة", en: "Disabilities & Special Needs" },
    "disabilities & special needs": { ar: "ذوو الاحتياجات الخاصة", en: "Disabilities & Special Needs" }
  };

  function translateCategory(rawCat, lang) {
    if (window.translateCategoryText) {
      return window.translateCategoryText(rawCat, lang);
    }
    if (!rawCat) return "";
    const clean = String(rawCat).trim();
    const docLang = (document.documentElement.getAttribute("lang") || document.documentElement.lang || currentLang || "ar").toLowerCase();
    const targetLang = (lang === "ar" || lang === "en") ? lang : (docLang.startsWith("en") ? "en" : "ar");
    const key = clean.toLowerCase();

    if (CATEGORY_TRANSLATIONS[key]) {
      return CATEGORY_TRANSLATIONS[key][targetLang] || clean;
    }
    if (clean.includes("/")) {
      const parts = clean.split("/").map(p => translateCategory(p.trim(), targetLang));
      return parts.join(" / ");
    }
    for (const [k, trans] of Object.entries(CATEGORY_TRANSLATIONS)) {
      if (key === k || key.startsWith(k + " ") || key.endsWith(" " + k)) {
        return trans[targetLang] || clean;
      }
    }
    return clean;
  }

  // ==========================
  // Language Management (AR / EN)
  // ==========================
  function applyLanguage(lang) {
    currentLang = lang;
    document.documentElement.setAttribute("dir", lang === "ar" ? "rtl" : "ltr");
    document.documentElement.setAttribute("lang", lang);
    localStorage.setItem("lang", lang);

    const t = I18N[lang];
    if (!t) return;

    // Update data-i18n texts
    document.querySelectorAll("[data-i18n]").forEach(el => {
      const key = el.getAttribute("data-i18n");
      if (t[key] !== undefined) {
        el.textContent = t[key];
      }
    });

    // Update data-i18n-placeholder attributes
    document.querySelectorAll("[data-i18n-placeholder]").forEach(el => {
      const key = el.getAttribute("data-i18n-placeholder");
      if (t[key] !== undefined) {
        el.placeholder = t[key];
      }
    });

    // Language toggle button text
    const langBtnText = document.getElementById("langToggleText");
    if (langBtnText) {
      langBtnText.textContent = lang === "ar" ? "English" : "العربية";
    }

    // Update theme toggle label
    const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
    updateThemeToggleUI(currentTheme);

    // Update mobile filter toggle text if present
    const toggleFilters = document.getElementById("toggleFilters");
    if (toggleFilters) {
      const isExpanded = toggleFilters.getAttribute("aria-expanded") === "true";
      const toggleText = toggleFilters.querySelector(".toggle-text");
      if (toggleText) {
        toggleText.textContent = isExpanded ? (t["filters.hide"] || "إخفاء الفلاتر") : (t["filters.toggle"] || "خيارات التصفية");
      }
    }

    // Update custom dropdown options for viewBy and sortBy if initialized
    if (customDropdowns) {
      if (customDropdowns.viewBy) {
        customDropdowns.viewBy.setOptions([
          { value: "author", label: t.viewBy.author },
          { value: "category", label: t.viewBy.category },
          { value: "language", label: t.viewBy.language },
          { value: "alpha", label: t.viewBy.alpha }
        ], "", false);
      }

      if (customDropdowns.sortBy) {
        customDropdowns.sortBy.setOptions([
          { value: "author", label: t.sortBy.author },
          { value: "az", label: t.sortBy.az },
          { value: "za", label: t.sortBy.za },
          { value: "lang", label: t.sortBy.lang }
        ], "", false);
      }

      // Dynamically re-populate and translate mainCat, subject2, author, and lang dropdowns
      populateDropdownOptions();
      if (window.applyCategoryTranslations) {
        window.applyCategoryTranslations(lang);
      }
      renderCatalog();
    }
  }

  function escapeHtml(str) {
    return (str || "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function uniq(arr) {
    return Array.from(new Set(arr))
      .filter(Boolean)
      .sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: "base" }));
  }

  // ==========================
  // Cards (UPDATED: badges for meta)
  // ==========================
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
    const cover = coverSrc(book);

    const openDefault = isAr ? "فتح" : "Open";
    const linksHtml = links.length ? `
      <div class="book-links">
        ${links.map(l =>
          `<a class="link-chip" href="${escapeHtml(l.url)}" target="_blank" rel="noopener">
            ${escapeHtml(l.name || openDefault)}
          </a>`
        ).join("")}
      </div>
    ` : "";

    // ✅ NEW: badges instead of raw text lines
    const author = norm(book.Author);
    const language = norm(book.Language);
    const isbn = norm(book.ISBN);

    // Environment-aware URL: localhost/127.0.0.1 uses fallback book.html?isbn= to prevent local 404s, production uses clean /book/
    const isLocal = typeof window !== "undefined" && (
      window.location.hostname === "localhost" ||
      window.location.hostname === "127.0.0.1"
    );
    const bookUrl = isbn
      ? (isLocal ? `book.html?isbn=${encodeURIComponent(isbn)}` : `/book/${encodeURIComponent(isbn)}`)
      : (book._id ? `book.html?id=${encodeURIComponent(book._id)}` : "#");
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
  // Custom Dropdown System
  // ==========================
  class CustomDropdown {
    static instances = [];

    static closeAll(exceptInstance) {
      for (const inst of CustomDropdown.instances) {
        if (inst !== exceptInstance && inst.isOpen) {
          inst.close();
        }
      }
    }

    constructor(container, options = {}) {
      if (!container) return;
      this.container = container;
      this.hiddenInput = container.querySelector("input[type='hidden']");
      this.trigger = container.querySelector(".dropdown-trigger");
      this.valueSpan = container.querySelector(".dropdown-value");
      this.menu = container.querySelector(".dropdown-menu");
      this.field = container.closest(".field");

      this.defaultPlaceholder = options.placeholder || (this.valueSpan ? this.valueSpan.textContent.trim() : "");
      this.defaultValue = this.hiddenInput ? this.hiddenInput.value : "";
      this.onChange = options.onChange || null;

      this.isOpen = false;
      this.items = [];

      CustomDropdown.instances.push(this);
      this.init();
    }

    init() {
      // If HTML already has <li> options (e.g. viewBy, sortBy)
      if (this.menu) {
        const lis = this.menu.querySelectorAll(".dropdown-item");
        if (lis.length > 0) {
          lis.forEach(li => {
            const val = li.getAttribute("data-value") !== null ? li.getAttribute("data-value") : "";
            const label = li.textContent.trim();
            this.items.push({ value: val, label: label, el: li });

            li.addEventListener("click", (e) => {
              e.stopPropagation();
              this.select(val);
              this.close();
            });
          });
        }
      }

      if (this.trigger) {
        this.trigger.addEventListener("click", (e) => {
          e.stopPropagation();
          if (this.isDisabled()) return;
          this.toggle();
        });

        this.trigger.addEventListener("keydown", (e) => {
          if (e.key === "Escape") {
            this.close();
          } else if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            if (!this.isDisabled()) this.toggle();
          } else if (e.key === "ArrowDown") {
            e.preventDefault();
            if (!this.isOpen && !this.isDisabled()) {
              this.open();
            }
            const firstItem = this.menu?.querySelector(".dropdown-item");
            if (firstItem) firstItem.focus();
          }
        });
      }

      this.syncDisplay();
    }

    isDisabled() {
      return this.container.classList.contains("is-disabled") || Boolean(this.trigger?.disabled);
    }

    setDisabled(disabled, placeholderText) {
      if (disabled) {
        this.container.classList.add("is-disabled");
        if (this.trigger) this.trigger.disabled = true;
        this.close();
      } else {
        this.container.classList.remove("is-disabled");
        if (this.trigger) this.trigger.disabled = false;
      }
      if (placeholderText) {
        this.defaultPlaceholder = placeholderText;
        if (!this.getValue()) {
          if (this.valueSpan) this.valueSpan.textContent = placeholderText;
        }
      }
    }

    toggle() {
      if (this.isOpen) this.close();
      else this.open();
    }

    open() {
      if (this.isOpen || this.isDisabled()) return;
      CustomDropdown.closeAll(this);
      this.isOpen = true;
      this.container.classList.add("is-open");
      if (this.field) this.field.classList.add("dropdown-active");
      if (this.trigger) this.trigger.setAttribute("aria-expanded", "true");

      const selectedEl = this.menu?.querySelector(".is-selected");
      if (selectedEl) {
        selectedEl.scrollIntoView({ block: "nearest" });
      }
    }

    close() {
      if (!this.isOpen) return;
      this.isOpen = false;
      this.container.classList.remove("is-open");
      if (this.field) this.field.classList.remove("dropdown-active");
      if (this.trigger) this.trigger.setAttribute("aria-expanded", "false");
    }

    getValue() {
      return this.hiddenInput ? this.hiddenInput.value : "";
    }

    setValue(val, triggerChange = true) {
      const stringVal = (val === null || val === undefined) ? "" : String(val);
      if (this.hiddenInput) {
        this.hiddenInput.value = stringVal;
      }
      const companionSelect = this.container.querySelector("select");
      if (companionSelect && companionSelect.value !== stringVal) {
        companionSelect.value = stringVal;
      }
      this.syncDisplay();
      if (triggerChange && typeof this.onChange === "function") {
        this.onChange(stringVal);
      }
    }

    select(val) {
      this.setValue(val, true);
    }

    syncDisplay() {
      const curVal = this.getValue();
      let matchedLabel = "";

      for (const item of this.items) {
        const isMatch = item.value === curVal;
        if (item.el) {
          item.el.classList.toggle("is-selected", isMatch);
          item.el.setAttribute("aria-selected", isMatch ? "true" : "false");
        }
        if (isMatch) matchedLabel = item.label;
      }

      if (this.valueSpan) {
        this.valueSpan.textContent = matchedLabel || this.defaultPlaceholder;
      }
    }

    setOptions(optionsList, placeholderText, includeEmptyDefault = true) {
      if (!this.menu) return;
      this.menu.innerHTML = "";
      this.items = [];

      if (placeholderText) {
        this.defaultPlaceholder = placeholderText;
      }

      // Default "All / reset" item
      if (includeEmptyDefault) {
        const defaultLi = document.createElement("li");
        defaultLi.className = "dropdown-item dropdown-item-default";
        defaultLi.setAttribute("role", "option");
        defaultLi.setAttribute("data-value", "");
        defaultLi.textContent = this.defaultPlaceholder;
        defaultLi.addEventListener("click", (e) => {
          e.stopPropagation();
          this.select("");
          this.close();
        });
        this.menu.appendChild(defaultLi);
        this.items.push({ value: "", label: this.defaultPlaceholder, el: defaultLi });
      }

      // Add options
      for (const item of optionsList) {
        let valStr = "";
        let labelStr = "";

        if (typeof item === "object" && item !== null) {
          valStr = norm(item.value);
          labelStr = norm(item.label) || valStr;
        } else {
          valStr = norm(item);
          labelStr = valStr;
        }

        if (!valStr) continue;

        const li = document.createElement("li");
        li.className = "dropdown-item";
        li.setAttribute("role", "option");
        li.setAttribute("data-value", valStr);
        li.textContent = labelStr;
        li.addEventListener("click", (e) => {
          e.stopPropagation();
          this.select(valStr);
          this.close();
        });
        this.menu.appendChild(li);
        this.items.push({ value: valStr, label: labelStr, el: li });
      }

      this.syncDisplay();
    }
  }

  // Close dropdowns on outside click or Escape
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".custom-dropdown")) {
      CustomDropdown.closeAll();
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      CustomDropdown.closeAll();
    }
  });

  // Initialize Custom Dropdowns
  const tInitial = I18N[currentLang] || I18N.ar;
  customDropdowns = {
    mainCat: new CustomDropdown(document.getElementById("dropdown-mainCat"), {
      placeholder: tInitial["filters.mainCatPlaceholder"],
      onChange: () => {
        refreshSubject2Select();
        applyFilters();
      }
    }),
    subject2: new CustomDropdown(document.getElementById("dropdown-subject2"), {
      placeholder: tInitial["filters.subCatDisabled"],
      onChange: () => applyFilters()
    }),
    author: new CustomDropdown(document.getElementById("dropdown-author"), {
      placeholder: tInitial["filters.authorPlaceholder"],
      onChange: () => applyFilters()
    }),
    lang: new CustomDropdown(document.getElementById("dropdown-lang"), {
      placeholder: tInitial["filters.langPlaceholder"],
      onChange: () => applyFilters()
    }),
    viewBy: new CustomDropdown(document.getElementById("dropdown-viewBy"), {
      placeholder: tInitial.viewBy.author,
      onChange: () => renderCatalog()
    }),
    sortBy: new CustomDropdown(document.getElementById("dropdown-sortBy"), {
      placeholder: tInitial.sortBy.author,
      onChange: () => applyFilters()
    })
  };

  // ==========================
  // Subject_2: disable until main selected + reset on main change
  // ==========================
  function subject2ValuesForMain(mainCat) {
    if (!mainCat) return [];
    const normMain = norm(mainCat).toLowerCase();
    const transAr = (translateCategory(mainCat, "ar") || "").toLowerCase();
    const transEn = (translateCategory(mainCat, "en") || "").toLowerCase();

    if (apiCategories.length) {
      const cat = apiCategories.find(c => {
        const cn = norm(c.name).toLowerCase();
        const cs = norm(c.slug).toLowerCase();
        return cn === normMain || cs === normMain || cn === transAr || cn === transEn;
      });
      if (cat && Array.isArray(cat.children) && cat.children.length) {
        return uniq(cat.children.map(ch => norm(ch.name)));
      }
    }
    const base = ALL.filter(b => {
      const bCat = norm(b.CategoryMain).toLowerCase();
      return bCat === normMain || bCat === transAr || bCat === transEn;
    });
    return uniq(base.map(b => norm(b.Subject_2)));
  }

  function refreshSubject2Select() {
    const dd = customDropdowns?.subject2;
    if (!dd) return;

    const mainCat = norm(els.mainCat?.value);
    const activeLang = document.documentElement.getAttribute("lang") || currentLang || "ar";
    const t = I18N[activeLang] || I18N.ar;

    if (!mainCat) {
      dd.setDisabled(true, t["filters.subCatDisabled"]);
      dd.setOptions([], t["filters.subCatDisabled"]);
      dd.setValue("", false);
      return;
    }

    const values = subject2ValuesForMain(mainCat);
    const placeholder = values.length ? t["filters.subCatAll"] : t["filters.subCatNone"];

    const subOptions = [];
    const seenSub = new Set();
    for (const val of values) {
      const label = translateCategory(val, activeLang);
      const key = label.toLowerCase();
      if (!seenSub.has(key)) {
        seenSub.add(key);
        subOptions.push({ value: val, label: label });
      }
    }
    subOptions.sort((a, b) => a.label.localeCompare(b.label, activeLang === "ar" ? "ar" : "en", { sensitivity: "base" }));

        // Keep native select in sync
    const subSelect = document.getElementById("subject2Select");
    if (subSelect) {
      subSelect.disabled = !subOptions.length;
      subSelect.innerHTML = `<option value="">${placeholder}</option>` +
        subOptions.map(o => `<option value="${escapeHtml(o.value)}" data-original="${escapeHtml(o.value)}">${escapeHtml(o.label)}</option>`).join("");
      const curSub = dd.getValue();
      if (curSub) subSelect.value = curSub;
      else subSelect.value = "";
    }

    const currentSubVal = dd.getValue();
    dd.setDisabled(!subOptions.length, placeholder);
    dd.setOptions(subOptions, placeholder);
    if (currentSubVal) {
      dd.setValue(currentSubVal, false);
    } else {
      dd.setValue("", false);
    }
  }

  // ==========================
  // Sorting / Grouping
  // ==========================
  function compareBy(fieldA, fieldB, dir = "asc") {
    const a = norm(fieldA);
    const b = norm(fieldB);
    const r = a.localeCompare(b, undefined, { numeric: true, sensitivity: "base" });
    return dir === "desc" ? -r : r;
  }

  function sortBooks(list) {
    const mode = norm(els.sortBy?.value) || "author";

    const cmp = (a, b) => {
      // Always keep priority bucket first, but sort INSIDE each bucket by selected mode
      const pa = isPriorityBook(a) ? 0 : 1;
      const pb = isPriorityBook(b) ? 0 : 1;
      if (pa !== pb) return pa - pb;

      if (mode === "az") {
        const r = compareBy(a.Title, b.Title, "asc");
        return r !== 0 ? r : compareBy(a.Author, b.Author, "asc");
      }
      if (mode === "za") {
        const r = compareBy(a.Title, b.Title, "desc");
        return r !== 0 ? r : compareBy(a.Author, b.Author, "asc");
      }
      if (mode === "lang") {
        const r = compareBy(a.Language, b.Language, "asc");
        return r !== 0 ? r : compareBy(a.Title, b.Title, "asc");
      }

      // default: author
      const r = compareBy(a.Author, b.Author, "asc");
      return r !== 0 ? r : compareBy(a.Title, b.Title, "asc");
    };

    return list.slice().sort(cmp);
  }

  function groupKey(book, mode) {
    if (mode === "category") return translateCategory(book.CategoryMain, currentLang) || "—";
    if (mode === "language") return norm(book.Language) || "—";
    if (mode === "alpha") {
      const t = norm(book.Title);
      return t ? t[0].toUpperCase() : "—";
    }
    // default: author
    return norm(book.Author) || "—";
  }

  function renderGrouped(list) {
    if (!list || list.length === 0) {
      const emptyMsg = (I18N[currentLang] && I18N[currentLang].noResults) || "لا توجد نتائج مطابقة للبحث.";
      els.results.innerHTML = `<div class="empty">${escapeHtml(emptyMsg)}</div>`;
      return;
    }

    const mode = norm(els.viewBy?.value) || "author";

    const map = new Map();
    for (const b of list) {
      const k = groupKey(b, mode);
      if (!map.has(k)) map.set(k, []);
      map.get(k).push(b);
    }

    const keys = Array.from(map.keys()).sort((a, b) =>
      a.localeCompare(b, undefined, { numeric: true, sensitivity: "base" })
    );

    const html = keys.map(k => {
      const items = map.get(k);
      return `
        <section class="group">
          <h2 class="group-title">${escapeHtml(k)}</h2>
          <div class="grid">
            ${items.map(renderBookCard).join("")}
          </div>
        </section>
      `;
    }).join("");

    els.results.innerHTML = html;
  }

  // ==========================
  // Pagination System
  // ==========================
  function getPaginationEl() {
    let el = els.pagination || document.getElementById("pagination");
    const ariaLabel = currentLang === "ar" ? "تنقل الصفحات" : "Pagination Navigation";
    if (!el && els.results) {
      el = document.createElement("nav");
      el.id = "pagination";
      el.className = "pagination-container";
      el.setAttribute("aria-label", ariaLabel);
      els.results.after(el);
      els.pagination = el;
    }
    if (el) {
      el.setAttribute("aria-label", ariaLabel);
    }
    return el;
  }

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

  // ==========================
  // Catalog Caching & Storage (Phase 9)
  // ==========================
  const CACHE_KEY = "mashreq_catalog_cache_v3";

  function saveCatalogCache() {
    try {
      const cacheData = {
        books: ALL,
        totalBooksCount: totalBooksCount,
        currentPage: currentPage,
        meta: META,
        filters: {
          q: els.q?.value || "",
          mainCat: els.mainCat?.value || "",
          subject2: els.subject2?.value || "",
          author: els.author?.value || "",
          lang: els.lang?.value || "",
          viewBy: els.viewBy?.value || "author",
          sortBy: els.sortBy?.value || "author"
        },
        scrollY: window.scrollY || window.pageYOffset || 0,
        savedAt: Date.now()
      };
      sessionStorage.setItem(CACHE_KEY, JSON.stringify(cacheData));
    } catch (err) {
      // Storage quota fallback: cache first 48 books
      try {
        const compact = {
          books: ALL.slice(0, 48),
          totalBooksCount: totalBooksCount,
          currentPage: currentPage,
          meta: META,
          filters: {
            q: els.q?.value || "",
            mainCat: els.mainCat?.value || "",
            subject2: els.subject2?.value || "",
            author: els.author?.value || "",
            lang: els.lang?.value || "",
            viewBy: els.viewBy?.value || "author",
            sortBy: els.sortBy?.value || "author"
          },
          scrollY: window.scrollY || window.pageYOffset || 0,
          savedAt: Date.now()
        };
        sessionStorage.setItem(CACHE_KEY, JSON.stringify(compact));
      } catch (_) {}
    }
  }

  function tryRestoreFromCache() {
    try {
      const raw = sessionStorage.getItem(CACHE_KEY);
      if (!raw) return false;
      const cache = JSON.parse(raw);
      if (!cache || !Array.isArray(cache.books) || cache.books.length === 0) return false;

      // Expire if older than 30 minutes
      if (Date.now() - (cache.savedAt || 0) > 30 * 60 * 1000) {
        sessionStorage.removeItem(CACHE_KEY);
        return false;
      }

      ALL = cache.books;
      totalBooksCount = cache.totalBooksCount || ALL.length;
      currentPage = cache.currentPage || 1;
      META = cache.meta || META;

      // Restore form filters
      if (cache.filters) {
        if (els.q && cache.filters.q !== undefined) els.q.value = cache.filters.q;
        if (customDropdowns) {
          if (customDropdowns.mainCat && cache.filters.mainCat) customDropdowns.mainCat.setValue(cache.filters.mainCat, false);
          if (customDropdowns.subject2 && cache.filters.subject2) customDropdowns.subject2.setValue(cache.filters.subject2, false);
          if (customDropdowns.author && cache.filters.author) customDropdowns.author.setValue(cache.filters.author, false);
          if (customDropdowns.lang && cache.filters.lang) customDropdowns.lang.setValue(cache.filters.lang, false);
          if (customDropdowns.viewBy && cache.filters.viewBy) customDropdowns.viewBy.setValue(cache.filters.viewBy, false);
          if (customDropdowns.sortBy && cache.filters.sortBy) customDropdowns.sortBy.setValue(cache.filters.sortBy, false);
        }
      }

      renderCatalog();
      updateKpis();
      updateFilterActiveStates();

      // Instant scroll restoration
      if (typeof cache.scrollY === "number" && cache.scrollY > 0) {
        const targetY = cache.scrollY;
        requestAnimationFrame(() => {
          window.scrollTo({ top: targetY, behavior: "instant" });
          setTimeout(() => {
            window.scrollTo({ top: targetY, behavior: "instant" });
          }, 80);
        });
      }

      return true;
    } catch (err) {
      console.error("[script.js] Failed to restore from cache:", err);
      return false;
    }
  }

  // ==========================
  // Pagination & Load More Rendering
  // ==========================
  function renderPagination(totalCount, page, pageSize, loadedCount) {
    const pagEl = getPaginationEl();
    if (!pagEl) return;

    if (!totalCount || totalCount === 0) {
      pagEl.innerHTML = "";
      return;
    }

    const totalPages = Math.ceil(totalCount / pageSize);
    const isAr = currentLang === "ar";

    // 1. Load More button (if there are more books left to fetch)
    const canLoadMore = loadedCount < totalCount;
    const loadMoreHtml = canLoadMore ? `
      <div class="load-more-container">
        <button type="button" class="load-more-btn" id="loadMoreBtn" aria-label="${isAr ? 'تحميل المزيد من الكتب' : 'Load More Books'}">
          <svg class="load-more-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
          <span>${isAr ? "تحميل المزيد من الكتب" : "Load More Books"}</span>
          <span class="load-more-badge">(${loadedCount.toLocaleString()} / ${totalCount.toLocaleString()})</span>
        </button>
      </div>
    ` : "";

    // 2. Numeric Page Controls
    if (totalPages <= 1) {
      pagEl.innerHTML = loadMoreHtml;
      return;
    }

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

    const infoHtml = isAr
      ? `<div class="pagination-info">عرض ${loadedCount.toLocaleString()} من أصل ${totalCount.toLocaleString()} كتاب (صفحة ${page} من ${totalPages.toLocaleString()})</div>`
      : `<div class="pagination-info">Showing ${loadedCount.toLocaleString()} of ${totalCount.toLocaleString()} books (Page ${page} of ${totalPages.toLocaleString()})</div>`;

    pagEl.innerHTML = `
      ${loadMoreHtml}
      <div class="pagination-controls">
        ${prevHtml}
        ${pagesHtml}
        ${nextHtml}
      </div>
      ${infoHtml}
    `;
  }

  function renderCatalog() {
    if (!ALL || ALL.length === 0) {
      const emptyMsg = (I18N[currentLang] && I18N[currentLang].noResults) || "لا توجد نتائج مطابقة للبحث.";
      if (els.results) {
        els.results.innerHTML = `<div class="empty">${escapeHtml(emptyMsg)}</div>`;
      }
      renderPagination(0, 1, PAGE_SIZE, 0);
      return;
    }

    renderGrouped(ALL);
    renderPagination(totalBooksCount, currentPage, PAGE_SIZE, ALL.length);
  }

  // ==========================
  // API Fetch with Pagination
  // ==========================
  function buildBooksApiUrl(page = 1, perPage = PAGE_SIZE) {
    const params = new URLSearchParams();
    params.set("page", page);
    params.set("per_page", perPage);

    const q = norm(els.q?.value);
    if (q) params.set("q", q);

    const mainCat = norm(els.mainCat?.value);
    if (mainCat) params.set("category", mainCat);

    const subject2 = norm(els.subject2?.value);
    if (subject2) params.set("subcategory", subject2);

    const author = norm(els.author?.value);
    if (author) params.set("author", author);

    const lang = norm(els.lang?.value);
    if (lang) params.set("language", lang);

    const sortVal = norm(els.sortBy?.value) || "default";
    if (sortVal && sortVal !== "default") params.set("sort", sortVal);

    return `https://api.almashreqebookstore.com/api/v1/books?${params.toString()}`;
  }

  async function fetchCatalogPage({ page = 1, append = false } = {}) {
    if (isFetching) return;
    isFetching = true;

    const url = buildBooksApiUrl(page, PAGE_SIZE);

    if (!append && els.results) {
      els.results.innerHTML = `
        <div class="notice" style="text-align:center;padding:48px 0;">
          <span>${currentLang === "ar" ? "جاري تحميل الكتب..." : "Loading books..."}</span>
        </div>
      `;
    }

    try {
      const headers = { Accept: "application/json" };
      const authToken = localStorage.getItem("sanctum_token");
      if (authToken) {
        headers["Authorization"] = `Bearer ${authToken}`;
      }

      const res = await fetch(url, {
        cache: "no-store",
        headers
      });

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }

      const json = await res.json();
      const newBooks = Array.isArray(json.books) ? json.books : (Array.isArray(json.data) ? json.data : []);
      totalBooksCount = json.total || json.count || (json.meta ? json.meta.total : newBooks.length);
      META = {
        generated_at: json.generated_at || META.generated_at,
        count: totalBooksCount
      };

      currentPage = page;

      if (append) {
        ALL.push(...newBooks);
      } else {
        ALL = newBooks;
      }

      renderCatalog();
      updateKpis();
      updateFilterActiveStates();
      saveCatalogCache();
    } catch (err) {
      console.error("[script.js] Network/API error fetching books:", err);
      if (!append && els.results) {
        els.results.innerHTML = `<div class="empty">${currentLang === "ar" ? "تعذر تحميل الكتب. يرجى التأكد من اتصال الخادم." : "Failed to load books. Please check server connection."}</div>`;
      }
    } finally {
      isFetching = false;
    }
  }

  function applyFilters(page = 1) {
    currentPage = page;
    fetchCatalogPage({ page: 1, append: false });
  }

  function updateFilterActiveStates() {
    const filters = [
      { el: els.q, active: Boolean(norm(els.q?.value)) },
      { el: els.mainCat, active: Boolean(norm(els.mainCat?.value)) },
      { el: els.subject2, active: Boolean(norm(els.subject2?.value)) },
      { el: els.author, active: Boolean(norm(els.author?.value)) },
      { el: els.lang, active: Boolean(norm(els.lang?.value)) },
      { el: els.viewBy, active: Boolean(norm(els.viewBy?.value) && norm(els.viewBy?.value) !== "author") },
      { el: els.sortBy, active: Boolean(norm(els.sortBy?.value) && norm(els.sortBy?.value) !== "author") },
    ];

    let count = 0;
    for (const f of filters) {
      const field = f.el?.closest(".field");
      if (field) {
        field.classList.toggle("is-active", f.active);
      }
      if (f.active) count++;
    }

    const badge = document.getElementById("filtersActiveCount");
    if (badge) {
      if (count > 0) {
        badge.textContent = count;
        badge.style.display = "inline-flex";
      } else {
        badge.style.display = "none";
      }
    }
  }

  function updateKpis() {
    if (els.kpiTotal) els.kpiTotal.textContent = (totalBooksCount || 0).toLocaleString();
    if (els.kpiShown) els.kpiShown.textContent = (ALL.length || 0).toLocaleString();
    if (els.kpiCats) {
      const catsCount = apiCategories.length || uniq(ALL.map(b => norm(b.CategoryMain))).length;
      els.kpiCats.textContent = catsCount.toLocaleString();
    }
    if (els.generatedAt) els.generatedAt.textContent = META.generated_at || "—";
  }

  // ==========================
  // Fetch Authors & Categories for Dropdowns
  // ==========================
  async function loadCategories() {
    const endpoints = [
      "https://api.almashreqebookstore.com/api/v1/categories"
    ];
    for (const ep of endpoints) {
      try {
        const res = await fetch(ep, { cache: "no-store", headers: { Accept: "application/json" } });
        if (res.ok) {
          const json = await res.json();
          if (Array.isArray(json.data)) {
            apiCategories = json.data;
            break;
          }
        }
      } catch (err) {
        console.error(`[script.js] Categories fetch error:`, err);
      }
    }
  }

  async function loadAuthors() {
    const endpoints = [
      "https://api.almashreqebookstore.com/api/v1/authors"
    ];
    for (const ep of endpoints) {
      try {
        const res = await fetch(ep, { cache: "no-store", headers: { Accept: "application/json" } });
        if (res.ok) {
          const json = await res.json();
          if (Array.isArray(json.data)) {
            apiAuthors = json.data;
            break;
          }
        }
      } catch (err) {
        console.error(`[script.js] Authors fetch error:`, err);
      }
    }
  }

  function populateDropdownOptions() {
    const activeLang = document.documentElement.getAttribute("lang") || currentLang || "ar";
    const tCur = I18N[activeLang] || I18N.ar;

    // Categories (Bilingual Dynamic Translation based on active <html lang>)
    if (customDropdowns?.mainCat) {
      const allCatNames = uniq([
        ...apiCategories.map(c => norm(c.name)),
        ...ALL.map(b => norm(b.CategoryMain))
      ]).filter(Boolean);

      const currentVal = customDropdowns.mainCat.getValue();

      // Build options translated into active language
      const options = [];
      const seenLabels = new Set();

      for (const cat of allCatNames) {
        const translatedLabel = translateCategory(cat, activeLang);
        if (!translatedLabel) continue;
        const key = translatedLabel.toLowerCase();
        if (!seenLabels.has(key)) {
          seenLabels.add(key);
          options.push({
            value: cat,
            label: translatedLabel
          });
        }
      }

      // Sort alphabetically in active language
      options.sort((a, b) => a.label.localeCompare(b.label, activeLang === "ar" ? "ar" : "en", { sensitivity: "base" }));

            // Keep native select in sync
      const mainSelect = document.getElementById("mainCatSelect");
      if (mainSelect) {
        mainSelect.innerHTML = `<option value="">${tCur["filters.mainCatPlaceholder"]}</option>` +
          options.map(o => `<option value="${escapeHtml(o.value)}" data-original="${escapeHtml(o.value)}">${escapeHtml(o.label)}</option>`).join("");
        if (currentVal) mainSelect.value = currentVal;
        else mainSelect.value = "";
      }

      customDropdowns.mainCat.setOptions(options, tCur["filters.mainCatPlaceholder"]);
      if (currentVal) {
        customDropdowns.mainCat.setValue(currentVal, false);
      } else {
        customDropdowns.mainCat.syncDisplay();
      }
      refreshSubject2Select();
    }

    // Authors
    if (customDropdowns?.author) {
      const allAuthorNames = uniq([
        ...apiAuthors.map(a => norm(a.name)),
        ...ALL.map(b => norm(b.Author))
      ]).filter(Boolean);
      allAuthorNames.sort((a, b) => a.localeCompare(b, activeLang === "ar" ? "ar" : "en", { sensitivity: "base" }));
      const currentAuthorVal = customDropdowns.author.getValue();
      customDropdowns.author.setOptions(allAuthorNames, tCur["filters.authorPlaceholder"]);
      if (currentAuthorVal) customDropdowns.author.setValue(currentAuthorVal, false);
      else customDropdowns.author.syncDisplay();
    }

    // Languages (Bilingual Dynamic Translation)
    if (customDropdowns?.lang) {
      const langMap = {
        "arabic": { ar: "العربية", en: "Arabic" },
        "العربية": { ar: "العربية", en: "Arabic" },
        "english": { ar: "الإنجليزية", en: "English" },
        "الإنجليزية": { ar: "الإنجليزية", en: "English" },
        "french": { ar: "الفرنسية", en: "French" },
        "الفرنسية": { ar: "الفرنسية", en: "French" }
      };

      const rawLangs = uniq([
        "Arabic", "English", "French",
        ...ALL.map(b => norm(b.Language))
      ]).filter(Boolean);

      const langOptions = [];
      const seenLangs = new Set();
      for (const l of rawLangs) {
        const lKey = l.toLowerCase();
        const label = langMap[lKey] ? langMap[lKey][activeLang] : l;
        const normKey = label.toLowerCase();
        if (!seenLangs.has(normKey)) {
          seenLangs.add(normKey);
          langOptions.push({ value: l, label: label });
        }
      }
      const currentLangVal = customDropdowns.lang.getValue();
      customDropdowns.lang.setOptions(langOptions, tCur["filters.langPlaceholder"]);
      if (currentLangVal) customDropdowns.lang.setValue(currentLangVal, false);
      else customDropdowns.lang.syncDisplay();
    }
  }

  // ==========================
  // Event listeners
  // ==========================
  let searchDebounceTimer = null;
  const onSearchChange = () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
      fetchCatalogPage({ page: 1, append: false });
    }, 350);
  };
  ["input", "change"].forEach(ev => els.q?.addEventListener(ev, onSearchChange));

  document.getElementById("mainCatSelect")?.addEventListener("change", (e) => {
    if (customDropdowns?.mainCat) {
      customDropdowns.mainCat.setValue(e.target.value);
    }
  });

  document.getElementById("subject2Select")?.addEventListener("change", (e) => {
    if (customDropdowns?.subject2) {
      customDropdowns.subject2.setValue(e.target.value);
    }
  });

  els.reset?.addEventListener("click", (e) => {
    e.preventDefault();
    if (els.q) els.q.value = "";
    if (customDropdowns?.mainCat) customDropdowns.mainCat.setValue("", false);
    if (customDropdowns?.author) customDropdowns.author.setValue("", false);
    if (customDropdowns?.lang) customDropdowns.lang.setValue("", false);
    if (customDropdowns?.viewBy) customDropdowns.viewBy.setValue("author", false);
    if (customDropdowns?.sortBy) customDropdowns.sortBy.setValue("author", false);

    sessionStorage.removeItem(CACHE_KEY);
    refreshSubject2Select();
    fetchCatalogPage({ page: 1, append: false });
  });

  // Global click delegation for Load More & Pagination Buttons
  document.addEventListener("click", async (e) => {
    // 1. Load More button
    const loadMoreBtn = e.target.closest("#loadMoreBtn");
    if (loadMoreBtn && !loadMoreBtn.disabled) {
      loadMoreBtn.disabled = true;
      const isAr = currentLang === "ar";
      const originalText = loadMoreBtn.innerHTML;
      loadMoreBtn.innerHTML = `<span>${isAr ? "جاري تحميل المزيد..." : "Loading more books..."}</span>`;

      const nextPage = Math.floor(ALL.length / PAGE_SIZE) + 1;
      await fetchCatalogPage({ page: nextPage, append: true });
      return;
    }

    // 2. Numeric pagination button
    const pageBtn = e.target.closest(".pagination-btn");
    if (pageBtn && !pageBtn.disabled && !pageBtn.classList.contains("is-active")) {
      const targetPage = parseInt(pageBtn.dataset.page, 10);
      if (!isNaN(targetPage) && targetPage >= 1) {
        await fetchCatalogPage({ page: targetPage, append: false });
        if (els.results) {
          const yOffset = -80;
          const y = els.results.getBoundingClientRect().top + window.pageYOffset + yOffset;
          window.scrollTo({ top: Math.max(0, y), behavior: "smooth" });
        }
      }
      return;
    }

    // 3. Capture scroll position when user clicks on any book link to navigate to book.html
    const bookLink = e.target.closest("a.book-cover-link, a.book-title-link, a[href*='book']");
    if (bookLink) {
      saveCatalogCache();
    }
  });

  // Save scroll and catalog state before leaving the page
  window.addEventListener("beforeunload", saveCatalogCache);

  // Listen to GlobalApp events if global.js is loaded
  window.addEventListener("languagechange", (e) => {
    if (e.detail && e.detail.lang) {
      applyLanguage(e.detail.lang);
    }
  });

  window.addEventListener("themechange", (e) => {
    if (e.detail && e.detail.theme) {
      updateThemeToggleUI(e.detail.theme);
    }
  });

  // Fallback Theme Toggle Button if global.js isn't loaded
  const themeBtn = document.getElementById("themeToggle");
  if (themeBtn && !window.GlobalApp) {
    themeBtn.addEventListener("click", () => {
      const cur = document.documentElement.getAttribute("data-theme") || "light";
      const next = cur === "dark" ? "light" : "dark";
      applyTheme(next);
    });
  }

  // Fallback Language Toggle Button if global.js isn't loaded
  const langBtn = document.getElementById("langToggle");
  if (langBtn && !window.GlobalApp) {
    langBtn.addEventListener("click", () => {
      const next = currentLang === "ar" ? "en" : "ar";
      applyLanguage(next);
    });
  }

  // ==========================
  // Programmatic Category Filter & Footer Routing
  // ==========================
  function applyCategoryFilter(catName) {
    if (!customDropdowns?.mainCat) return;

    if (!catName) {
      customDropdowns.mainCat.setValue("", true);
      return;
    }

    const clean = norm(catName).toLowerCase();

    // Check if it matches Heritage / كتب التراث العربي
    if (clean === "heritage" || clean === "تراث" || clean.includes("تراث")) {
      const foundItem = customDropdowns.mainCat.items.find(it => {
        const val = it.value.toLowerCase();
        const lbl = (it.label || "").toLowerCase();
        return val.includes("heritage") || val.includes("تراث") || lbl.includes("heritage") || lbl.includes("تراث");
      });
      if (foundItem) {
        customDropdowns.mainCat.setValue(foundItem.value, true);
      } else {
        if (els.q) {
          els.q.value = (currentLang === "ar" ? "تراث" : "Heritage");
          fetchCatalogPage({ page: 1, append: false });
        }
      }
      return;
    }

    // Normal category match: check item value, label, or translations
    let matchedVal = "";
    for (const it of customDropdowns.mainCat.items) {
      if (!it.value) continue;
      const v = it.value.toLowerCase();
      const l = (it.label || "").toLowerCase();
      const tAr = (translateCategory(it.value, "ar") || "").toLowerCase();
      const tEn = (translateCategory(it.value, "en") || "").toLowerCase();

      if (v === clean || l === clean || tAr === clean || tEn === clean) {
        matchedVal = it.value;
        break;
      }
    }

    if (matchedVal) {
      customDropdowns.mainCat.setValue(matchedVal, true);
    } else {
      customDropdowns.mainCat.setValue(catName, true);
    }
  }

  function initFooterCategoryLinks() {
    document.querySelectorAll(".footer-cat-link").forEach(link => {
      link.addEventListener("click", (e) => {
        const isIndex = window.location.pathname.endsWith("index.html") ||
                        window.location.pathname === "/" ||
                        window.location.pathname.endsWith("/");
        if (isIndex && customDropdowns?.mainCat) {
          e.preventDefault();
          const targetCat = link.getAttribute("data-cat") || "";
          applyCategoryFilter(targetCat);
          const catalogEl = document.getElementById("catalog") || document.querySelector(".page-shell") || document.querySelector(".filters-card");
          if (catalogEl) {
            const yOffset = -70;
            const y = catalogEl.getBoundingClientRect().top + window.pageYOffset + yOffset;
            window.scrollTo({ top: Math.max(0, y), behavior: "smooth" });
          }
          const newUrl = targetCat ? `index.html?category=${encodeURIComponent(targetCat)}` : "index.html";
          window.history.pushState({ category: targetCat }, "", newUrl);
        }
      });
    });

    window.addEventListener("popstate", () => {
      const params = new URLSearchParams(window.location.search);
      const cat = params.get("category") || params.get("cat") || "";
      applyCategoryFilter(cat);
    });
  }

  function checkUrlParamsAndFilter() {
    const params = new URLSearchParams(window.location.search);
    const cat = params.get("category") || params.get("cat");
    const q = params.get("q") || params.get("search");

    let didFilter = false;

    if (q && els.q) {
      els.q.value = q;
      didFilter = true;
    }

    if (cat && customDropdowns?.mainCat) {
      applyCategoryFilter(cat);
      didFilter = true;
    } else if (didFilter) {
      fetchCatalogPage({ page: 1, append: false });
    }

    if (didFilter) {
      setTimeout(() => {
        const catalogEl = document.getElementById("catalog") || document.querySelector(".page-shell") || document.querySelector(".filters-card");
        if (catalogEl) {
          const yOffset = -70;
          const y = catalogEl.getBoundingClientRect().top + window.pageYOffset + yOffset;
          window.scrollTo({ top: Math.max(0, y), behavior: "smooth" });
        }
      }, 350);
    }
  }

  // Initial hydration for Theme & Language
  const savedTheme = localStorage.getItem("theme") || document.documentElement.getAttribute("data-theme") || "light";
  applyTheme(savedTheme);
  applyLanguage(currentLang);

  // Initialize footer category navigation listeners
  initFooterCategoryLinks();

  // ==========================
  // Initialization Lifecycle
  // ==========================
  const urlHasParams = Boolean(new URLSearchParams(window.location.search).get("category") ||
                               new URLSearchParams(window.location.search).get("cat") ||
                               new URLSearchParams(window.location.search).get("q"));

  const restored = !urlHasParams && tryRestoreFromCache();

  // If not restored from cache, load initial page 1 from API
  if (!restored && !urlHasParams) {
    fetchCatalogPage({ page: 1, append: false });
  }

  // Load Categories & Authors in background to populate dropdowns & trigger URL param filters
  Promise.allSettled([loadCategories(), loadAuthors()]).then(() => {
    populateDropdownOptions();
    updateKpis();
    if (urlHasParams) {
      checkUrlParamsAndFilter();
    }
  });

  // ==========================
  // Authentication API Methods (Phase 11)
  // ==========================
  const AUTH_STORAGE_KEY = "sanctum_token";
  const AUTH_USER_KEY = "auth_user";

  function getAuthApiHost() {
    return "https://api.almashreqebookstore.com";
  }

  window.MashreqCatalogAuth = {
    login: async function(email, password) {
      const res = await fetch(`${getAuthApiHost()}/api/v1/login`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
        },
        body: JSON.stringify({ email, password })
      });
      const data = await res.json();
      if (res.ok && (data.token || data.access_token)) {
        const token = data.access_token || data.token;
        localStorage.setItem(AUTH_STORAGE_KEY, token);
        if (data.user) {
          localStorage.setItem(AUTH_USER_KEY, JSON.stringify(data.user));
        }
        window.MashreqAuth?.updateUI?.();
      }
      return { ok: res.ok, status: res.status, data };
    },

    register: async function(userData) {
      const res = await fetch(`${getAuthApiHost()}/api/v1/register`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
        },
        body: JSON.stringify(userData)
      });
      const data = await res.json();
      if (res.ok && (data.token || data.access_token)) {
        const token = data.access_token || data.token;
        localStorage.setItem(AUTH_STORAGE_KEY, token);
        if (data.user) {
          localStorage.setItem(AUTH_USER_KEY, JSON.stringify(data.user));
        }
        window.MashreqAuth?.updateUI?.();
      }
      return { ok: res.ok, status: res.status, data };
    },

    logout: async function() {
      const token = localStorage.getItem(AUTH_STORAGE_KEY);
      if (token) {
        try {
          await fetch(`${getAuthApiHost()}/api/v1/logout`, {
            method: "POST",
            headers: {
              "Accept": "application/json",
              "Authorization": `Bearer ${token}`
            }
          });
        } catch (_) {}
      }
      localStorage.removeItem(AUTH_STORAGE_KEY);
      localStorage.removeItem(AUTH_USER_KEY);
      window.MashreqAuth?.updateUI?.();
    },

    getUser: async function() {
      const token = localStorage.getItem(AUTH_STORAGE_KEY);
      if (!token) return null;
      try {
        const res = await fetch(`${getAuthApiHost()}/api/v1/user`, {
          method: "GET",
          headers: {
            "Accept": "application/json",
            "Authorization": `Bearer ${token}`
          }
        });
        if (res.status === 401) {
          localStorage.removeItem(AUTH_STORAGE_KEY);
          localStorage.removeItem(AUTH_USER_KEY);
          window.MashreqAuth?.updateUI?.();
          return null;
        }
        if (res.ok) {
          const data = await res.json();
          if (data && data.user) {
            localStorage.setItem(AUTH_USER_KEY, JSON.stringify(data.user));
            return data.user;
          }
        }
      } catch (_) {}
      return null;
    }
  };

  // ==========================
  // Auth Modal & Event Listeners in script.js (Phase 11 Task 2)
  // ==========================
  function setupAuthListeners() {
    const modal = document.getElementById("authModal");

    function openModal(tab = "login") {
      if (window.MashreqAuth && typeof window.MashreqAuth.openModal === "function") {
        window.MashreqAuth.openModal(tab);
        return;
      }
      if (!modal) return;
      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";

      const tabLogin = document.getElementById("tabBtnLogin");
      const tabRegister = document.getElementById("tabBtnRegister");
      const formLogin = document.getElementById("loginForm");
      const formRegister = document.getElementById("registerForm");
      const alertEl = document.getElementById("authAlert");
      if (alertEl) alertEl.style.display = "none";

      if (tab === "register") {
        tabLogin?.classList.remove("is-active");
        tabRegister?.classList.add("is-active");
        formLogin?.classList.remove("is-active");
        formRegister?.classList.add("is-active");
      } else {
        tabRegister?.classList.remove("is-active");
        tabLogin?.classList.add("is-active");
        formRegister?.classList.remove("is-active");
        formLogin?.classList.add("is-active");
      }

      setTimeout(() => {
        const activeForm = modal.querySelector(".auth-form.is-active");
        const firstInput = activeForm ? activeForm.querySelector("input") : null;
        if (firstInput) firstInput.focus();
      }, 120);
    }

    function closeModal() {
      if (window.MashreqAuth && typeof window.MashreqAuth.closeModal === "function") {
        window.MashreqAuth.closeModal();
        return;
      }
      if (!modal) return;
      modal.classList.remove("is-open");
      modal.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
      const alertEl = document.getElementById("authAlert");
      if (alertEl) alertEl.style.display = "none";
    }

    // 1. Direct event listener on the openAuthModalBtn element
    const openBtn = document.getElementById("openAuthModalBtn");
    if (openBtn) {
      openBtn.addEventListener("click", function(e) {
        e.preventDefault();
        openModal("login");
      });
    }

    // 2. Global click delegation on document for open / close modal
    document.addEventListener("click", function(e) {
      const openTrigger = e.target.closest("#openAuthModalBtn, .open-auth-modal");
      if (openTrigger) {
        e.preventDefault();
        openModal(openTrigger.dataset.tab || "login");
        return;
      }

      const closeTrigger = e.target.closest("#closeAuthModalBtn, #authModalBackdrop");
      if (closeTrigger) {
        e.preventDefault();
        closeModal();
        return;
      }
    });

    // 3. Tab switching & close handlers
    document.getElementById("closeAuthModalBtn")?.addEventListener("click", closeModal);
    document.getElementById("authModalBackdrop")?.addEventListener("click", closeModal);
    document.getElementById("tabBtnLogin")?.addEventListener("click", () => openModal("login"));
    document.getElementById("tabBtnRegister")?.addEventListener("click", () => openModal("register"));
    document.getElementById("switchToRegisterBtn")?.addEventListener("click", (e) => {
      e.preventDefault();
      openModal("register");
    });
    document.getElementById("switchToLoginBtn")?.addEventListener("click", (e) => {
      e.preventDefault();
      openModal("login");
    });

    // 4. Escape key handler
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        closeModal();
      }
    });
  }

  setupAuthListeners();

})();