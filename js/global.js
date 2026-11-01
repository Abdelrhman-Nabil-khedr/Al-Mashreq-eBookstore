/**
 * Al-Mashreq eBookstore — global.js
 * Universal header, theme management, language support (AR/EN), and footer utilities
 * Shared across ALL pages.
 */
(function () {
  "use strict";

  // ==========================
  // Internationalization (i18n) Dictionary
  // ==========================
  const I18N = {
    ar: {
      "brand.title": "مكتبة المشرق الإلكترونية",
      "nav.home": "الرئيسية",
      "nav.latest": "أحدث الإصدارات",
      "nav.vision": "الرؤية والمهمة",
      "nav.about": "من نحن",
      "nav.blog": "المدونة",
      "nav.contact": "اتصل بنا",
      "nav.menu": "القائمة",
      "nav.close": "إغلاق",
      "theme.toggle": "داكن",
      "themeDark": "داكن",
      "themeLight": "فاتح",
      "themeDarkLabel": "الوضع الداكن",
      "themeLightLabel": "الوضع الفاتح",
      "lang.switch": "English",
      "foot.vision": "الرؤية",
      "foot.latest": "أحدث الإصدارات",
      "foot.blog": "المدونة",
      "foot.contact": "اتصل بنا",
      "foot.terms": "الشروط والأحكام",
      "foot.privacy": "سياسة الخصوصية",
      "footer.desc": "الاحترافية — التناغم — التواصل",
      "footer.copy": "مكتبة المشرق الإلكترونية — جميع الحقوق محفوظة",
      "footer.brandBio": "أكبر مكتبة رقمية عربية تضم آلاف الكتب التراثية والمترجمة وكتب الأطفال لإحياء المجتمع الثقافي العربي بجودة عالية.",
      "footer.colQuick": "روابط سريعة",
      "footer.colExplore": "استكشف الكتب",
      "footer.colSupport": "الدعم والمساعدة",
      "footer.catFiction": "روايات وأدب",
      "footer.catHeritage": "كتب التراث العربي",
      "footer.catTrans": "ترجمات عالمية",
      "footer.catKids": "كتب الأطفال واليافعين",
      "footer.catAll": "الفهرس الشامل",
      "footer.contact": "اتصل بنا",
      "footer.terms": "الشروط والأحكام",
      "footer.privacy": "سياسة الخصوصية",
      "footer.whatsapp": "دعم واتساب",
      "footer.madeWith": "صُنع بعناية للقارئ العربي",

      // Authentication
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

      // Contact Page
      "contact.badge": "تواصل معنا",
      "contact.title": "اتصل بنا",
      "contact.sub": "يسعدنا تواصلكم لأي استفسار أو اقتراح شراكة أو دعم فني للمنصة.",
      "contact.formHeading": "نموذج المراسلة المباشرة",
      "contact.nameLabel": "الاسم الكامل",
      "contact.namePlaceholder": "اكتب اسمك هنا...",
      "contact.emailLabel": "البريد الإلكتروني",
      "contact.emailPlaceholder": "example@domain.com",
      "contact.subjectLabel": "موضوع الرسالة",
      "contact.subjectPlaceholder": "ما هو موضوع استفسارك؟",
      "contact.msgLabel": "نص الرسالة",
      "contact.msgPlaceholder": "اكتب تفاصيل رسالتك أو استفسارك هنا...",
      "contact.sendMail": "إرسال عبر البريد الإلكتروني",
      "contact.sendWa": "محادثة فورية عبر واتساب",
      "contact.note": "ملاحظة: يمكنك إرسال الرسالة عبر تطبيق البريد مباشرة أو فتح محادثة فورية عبر واتساب.",
      "contact.nameHint": "يرجى إدخال اسمك الكامل (حرفين على الأقل)",
      "contact.emailHint": "يرجى إدخال بريد إلكتروني صالح",
      "contact.subjectHint": "يرجى تحديد موضوع الرسالة",
      "contact.msgHint": "يرجى كتابة رسالتك (10 أحرف على الأقل)",

      // About Page
      "about.title": "من نحن",
      "about.p1": "بدأت مكتبة المشرق الإلكترونية نشاطها بجمع وتحرير عدد كبير من كتب التراث لكبار الكُتّاب العرب الذين أثروا بأفكارهم في المجتمع الثقافي العربي في الماضي القريب.",
      "about.h2_offer": "ماذا نقدم؟",
      "about.p2": "نعمل على نشر أكبر مكتبة عربية تضم أهم كتب التراث العربي بعد إعادة إنتاجها في شكل رقمي عصري متميز، لبناء أرشيف رقمي كامل يحافظ على تلك الكنوز من الاندثار.",
      "about.h2_trans": "ترجمات عالمية وكتب للأطفال",
      "about.p3": "توجهت المكتبة أيضًا إلى نشر عدد كبير من الكتب المترجمة لكبار المؤلفين العالميين والتي ترجمها عدد من أعظم الأدباء العرب، وتمت إعادة صياغة وتحرير تلك المؤلفات لتتماشى مع توقعات القارئ العصري. كما تضم المكتبة عددًا كبيرًا من كتب الأطفال واليافعين.",

      // Vision Page
      "vision.title": "الرؤية والمهمة",
      "vision.sub": "نعمل على إحياء المجتمع الثقافي العربي عالميًا عبر محتوى رقمي يليق بالقارئ العصري ويحافظ على كنوز التراث من الاندثار.",
      "vision.h2_vis": "الرؤية",
      "vision.p_vis": "نرغب في إحياء المجتمع الثقافي العربي في جميع أنحاء العالم، وأن نوفر المحتوى الرقمي الذي يساهم في ذلك.",
      "vision.h2_mis": "المهمة",
      "vision.p_mis": "إحياء كتب التراث في شكل جديد يتناسب مع القارئ العصري، ثم إضافة محتوى رقمي متميز لكافة فئات المجتمع.",
      "vision.h2_val": "قيمنا",
      "vision.val1_title": "الاحترافية:",
      "vision.val1_desc": "جودة عالية في الإعداد والتحرير والعرض الرقمي.",
      "vision.val2_title": "التناغم:",
      "vision.val2_desc": "تجربة قراءة سلسة تربط التراث بروح العصر.",
      "vision.val3_title": "التواصل:",
      "vision.val3_desc": "شراكات ودعم مستمر لخدمة القارئ والمنصات.",

      // Book Details Page
      "book.back": "← العودة إلى المكتبة",
      "book.library": "المكتبة",
      "book.details": "تفاصيل الكتاب",
      "book.loading": "جاري تحميل بيانات الكتاب...",
      "book.buyLinks": "روابط الشراء",
      "book.view": "عرض"
    },
    en: {
      "brand.title": "Al-Mashreq eBookstore",
      "nav.home": "Home",
      "nav.latest": "Latest Books",
      "nav.vision": "Vision & Mission",
      "nav.about": "About Us",
      "nav.blog": "Blog",
      "nav.contact": "Contact Us",
      "nav.menu": "Menu",
      "nav.close": "Close",
      "theme.toggle": "Dark",
      "themeDark": "Dark",
      "themeLight": "Light",
      "themeDarkLabel": "Dark Mode",
      "themeLightLabel": "Light Mode",
      "lang.switch": "العربية",
      "foot.vision": "Vision",
      "foot.latest": "Latest Books",
      "foot.blog": "Blog",
      "foot.contact": "Contact Us",
      "foot.terms": "Terms & Conditions",
      "foot.privacy": "Privacy Policy",
      "footer.desc": "Professionalism — Harmony — Communication",
      "footer.copy": "Al-Mashreq eBookstore — All Rights Reserved",
      "footer.brandBio": "The premier digital Arabic library featuring thousands of heritage books, international translations, and young adult literature.",
      "footer.colQuick": "Quick Links",
      "footer.colExplore": "Explore Books",
      "footer.colSupport": "Support & Help",
      "footer.catFiction": "Fiction & Literature",
      "footer.catHeritage": "Arabic Heritage Books",
      "footer.catTrans": "World Translations",
      "footer.catKids": "Children & Youth",
      "footer.catAll": "All Catalog",
      "footer.contact": "Contact Us",
      "footer.terms": "Terms & Conditions",
      "footer.privacy": "Privacy Policy",
      "footer.madeWith": "Crafted for Arabic literature readers",

      // Authentication
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

      // Contact Page
      "contact.badge": "Get In Touch",
      "contact.title": "Contact Us",
      "contact.sub": "We are delighted to hear from you for any inquiries, partnership proposals, or technical support.",
      "contact.formHeading": "Direct Message Form",
      "contact.nameLabel": "Full Name",
      "contact.namePlaceholder": "Type your name here...",
      "contact.emailLabel": "Email Address",
      "contact.emailPlaceholder": "example@domain.com",
      "contact.subjectLabel": "Subject",
      "contact.subjectPlaceholder": "What is your inquiry about?",
      "contact.msgLabel": "Your Message",
      "contact.msgPlaceholder": "Provide details of your message or inquiry...",
      "contact.sendMail": "Send via Email",
      "contact.sendWa": "Instant Chat via WhatsApp",
      "contact.note": "Note: You can submit directly via your email client or start a quick WhatsApp conversation.",
      "contact.nameHint": "Please enter your full name (minimum 2 characters)",
      "contact.emailHint": "Please enter a valid email address",
      "contact.subjectHint": "Please enter a message subject",
      "contact.msgHint": "Please write your message (minimum 10 characters)",

      // About Page
      "about.title": "About Us",
      "about.p1": "Al-Mashreq eBookstore began its journey by collecting and editing Arabic heritage literature from pioneering Arab authors whose thoughts enriched cultural society.",
      "about.h2_offer": "What We Offer",
      "about.p2": "We publish one of the largest Arabic digital libraries of heritage books in modern, high-quality formats, building a permanent archive to preserve these treasures.",
      "about.h2_trans": "World Translations & Young Reader Books",
      "about.p3": "We also curate acclaimed international translations translated by prominent Arab scholars, re-edited for modern readers, alongside an extensive catalog for young adults and children.",

      // Vision Page
      "vision.title": "Vision & Mission",
      "vision.sub": "Reviving the Arab cultural community worldwide through digital literature that honors tradition and meets modern standards.",
      "vision.h2_vis": "Vision",
      "vision.p_vis": "To empower the global Arabic-speaking cultural community with accessible, high-quality digital reading experiences.",
      "vision.h2_mis": "Mission",
      "vision.p_mis": "To digitize Arabic literary heritage in elegant modern editions while introducing premium original publications.",
      "vision.h2_val": "Our Values",
      "vision.val1_title": "Professionalism:",
      "vision.val1_desc": "Highest standards in editorial curation, typesetting, and digital presentation.",
      "vision.val2_title": "Harmony:",
      "vision.val2_desc": "Bridging historic Arab literary heritage with contemporary digital design.",
      "vision.val3_title": "Communication:",
      "vision.val3_desc": "Dedicated partnerships and ongoing support for readers and publishing platforms.",

      // Book Details Page
      "book.back": "← Back to Library",
      "book.library": "Library",
      "book.details": "Book Details",
      "book.loading": "Loading book details...",
      "book.buyLinks": "Purchase Links",
      "book.view": "View"
    }
  };

  window.GLOBAL_I18N = I18N;

  // ==========================
  // Strict Category Translation (Bidirectional AR <-> EN)
  // ==========================
  const CATEGORY_TRANSLATIONS = {
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

  function translateCategoryText(rawCat, lang) {
    if (!rawCat) return "";
    const clean = String(rawCat).trim();
    if (!clean) return "";

    // Strictly check document.documentElement.lang ('ar' or 'en')
    const docLang = (document.documentElement.getAttribute("lang") || document.documentElement.lang || "ar").toLowerCase();
    const targetLang = (lang === "ar" || lang === "en") ? lang : (docLang.startsWith("en") ? "en" : "ar");

    const key = clean.toLowerCase();

    // 1. Direct dictionary match
    if (CATEGORY_TRANSLATIONS[key]) {
      return CATEGORY_TRANSLATIONS[key][targetLang] || clean;
    }

    // 2. Composite category with slashes (e.g. "Fiction / Adventure")
    if (clean.includes("/")) {
      const parts = clean.split("/").map(p => translateCategoryText(p.trim(), targetLang));
      return parts.join(" / ");
    }

    // 3. Prefix / suffix lookup
    for (const [k, trans] of Object.entries(CATEGORY_TRANSLATIONS)) {
      if (key === k || key.startsWith(k + " ") || key.endsWith(" " + k)) {
        return trans[targetLang] || clean;
      }
    }

    return clean;
  }

  function applyCategoryTranslations(lang) {
    const activeLang = (lang || document.documentElement.getAttribute("lang") || document.documentElement.lang || "ar").toLowerCase().startsWith("en") ? "en" : "ar";

    // 1. Strict replacement on all native <select> elements and their <option> tags
    document.querySelectorAll("select").forEach(select => {
      select.querySelectorAll("option").forEach(opt => {
        if (!opt.getAttribute("data-original")) {
          opt.setAttribute("data-original", opt.value || opt.textContent);
        }
        const orig = opt.getAttribute("data-original") || opt.value || opt.textContent;

        if (!opt.value) {
          if (opt.hasAttribute("data-i18n")) {
            const i18nKey = opt.getAttribute("data-i18n");
            if (I18N[activeLang] && I18N[activeLang][i18nKey]) {
              opt.textContent = I18N[activeLang][i18nKey];
              return;
            }
          }
          if (opt.textContent.includes("فرع") || opt.textContent.includes("Subcat")) {
            opt.textContent = activeLang === "ar" ? "كل التصنيفات الفرعية" : "All Subcategories";
            return;
          }
          if (opt.textContent.includes("رئيس") || opt.textContent.includes("Main") || opt.textContent.includes("كل") || opt.textContent.includes("All")) {
            opt.textContent = activeLang === "ar" ? "كل التصنيفات الرئيسية" : "All Main Categories";
            return;
          }
        }

        const translated = translateCategoryText(orig, activeLang);
        if (translated) {
          opt.textContent = translated;
        }
      });
    });

    // 2. Strict replacement on custom dropdown items (role="option" and .dropdown-item)
    document.querySelectorAll('[role="option"], .dropdown-item').forEach(item => {
      if (!item.getAttribute("data-original")) {
        const orig = item.getAttribute("data-value") || item.textContent;
        if (orig) item.setAttribute("data-original", orig);
      }
      const orig = item.getAttribute("data-original") || item.getAttribute("data-value") || item.textContent;
      if (orig) {
        const translated = translateCategoryText(orig, activeLang);
        if (translated) {
          item.textContent = translated;
        }
      }
    });

    // 3. Custom dropdown display values
    document.querySelectorAll(".custom-dropdown").forEach(dd => {
      const name = dd.getAttribute("data-name") || dd.id;
      const isCat = name && (name.includes("mainCat") || name.includes("cat") || name.includes("subject"));
      if (!isCat) return;

      const valSpan = dd.querySelector(".dropdown-value");
      const hiddenInput = dd.querySelector("input[type='hidden']");
      if (!valSpan) return;

      if (hiddenInput && hiddenInput.value) {
        const trans = translateCategoryText(hiddenInput.value, activeLang);
        if (trans) valSpan.textContent = trans;
      } else {
        if (name.includes("mainCat") || name.includes("cat")) {
          valSpan.textContent = activeLang === "ar" ? "كل التصنيفات الرئيسية" : "All Main Categories";
        } else if (name.includes("subject2") || name.includes("subcat")) {
          const isDisabled = dd.classList.contains("is-disabled") || dd.querySelector("button")?.disabled;
          valSpan.textContent = isDisabled
            ? (activeLang === "ar" ? "اختر التصنيف الرئيسي أولاً" : "Select Main Category First")
            : (activeLang === "ar" ? "كل التصنيفات الفرعية" : "All Subcategories");
        }
      }
    });
  }

  window.CATEGORY_TRANSLATIONS = CATEGORY_TRANSLATIONS;
  window.translateCategoryText = translateCategoryText;
  window.applyCategoryTranslations = applyCategoryTranslations;


  // ==========================
  // Current State
  // ==========================
  let currentLang = localStorage.getItem("lang") || "ar";
  let currentTheme = localStorage.getItem("theme") || 
    (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");

  // ==========================
  // Theme Management
  // ==========================
  function updateThemeUI(theme) {
    const isDark = theme === "dark";
    const themeBtn = document.getElementById("themeToggle");
    if (!themeBtn) return;

    const label = isDark
      ? (currentLang === "ar" ? I18N.ar.themeLightLabel : I18N.en.themeLightLabel)
      : (currentLang === "ar" ? I18N.ar.themeDarkLabel : I18N.en.themeDarkLabel);

    themeBtn.setAttribute("aria-label", label);
    themeBtn.setAttribute("title", label);

    const textSpan = themeBtn.querySelector(".action-btn-text");
    if (textSpan) {
      textSpan.textContent = isDark
        ? (currentLang === "ar" ? I18N.ar.themeLight : I18N.en.themeLight)
        : (currentLang === "ar" ? I18N.ar.themeDark : I18N.en.themeDark);
    }

    const existingSvg = themeBtn.querySelector("svg");
    if (existingSvg) {
      existingSvg.outerHTML = isDark
        ? `<svg class="theme-icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`
        : `<svg class="theme-icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
    }
  }

  function applyTheme(theme) {
    currentTheme = theme;
    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem("theme", theme);
    updateThemeUI(theme);

    window.dispatchEvent(new CustomEvent("themechange", { detail: { theme } }));
  }

  // ==========================
  // Language Management
  // ==========================
  function applyLanguage(lang) {
    currentLang = lang;
    document.documentElement.setAttribute("dir", lang === "ar" ? "rtl" : "ltr");
    document.documentElement.setAttribute("lang", lang);
    localStorage.setItem("lang", lang);

    const t = I18N[lang] || I18N.ar;

    // Update text for all elements with data-i18n
    document.querySelectorAll("[data-i18n]").forEach((el) => {
      const key = el.getAttribute("data-i18n");
      if (t[key] !== undefined) {
        el.textContent = t[key];
      }
    });

    // Update placeholders for all elements with data-i18n-placeholder
    document.querySelectorAll("[data-i18n-placeholder]").forEach((el) => {
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

    // Mobile menu toggle text & label
    const menuToggle = document.getElementById("menuToggle");
    if (menuToggle) {
      const menuText = menuToggle.querySelector(".menu-toggle-text");
      const navEl = document.getElementById("mainNav") || document.querySelector(".site-header .nav");
      const isOpen = navEl && navEl.classList.contains("is-open");
      const openLabel = lang === "en" ? "Close" : "إغلاق";
      const closedLabel = lang === "en" ? "Menu" : "القائمة";
      if (menuText) {
        menuText.textContent = isOpen ? openLabel : closedLabel;
      }
      menuToggle.setAttribute("aria-label", isOpen ? openLabel : closedLabel);
      menuToggle.setAttribute("title", isOpen ? openLabel : closedLabel);
    }

    // Refresh theme button text
    updateThemeUI(currentTheme);

    // Strict Category Translation for <select> options and dropdowns
    applyCategoryTranslations(lang);

    // Dispatch event for page-specific controllers (e.g. index.html script.js)
    window.dispatchEvent(new CustomEvent("languagechange", { detail: { lang } }));
  }

  // ==========================
  // Header Elevation Shadow
  // ==========================
  function initHeaderScroll() {
    const siteHeader = document.querySelector(".site-header");
    if (!siteHeader) return;

    const onScroll = () => {
      if (window.scrollY > 15) {
        siteHeader.classList.add("is-scrolled");
      } else {
        siteHeader.classList.remove("is-scrolled");
      }
    };

    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  // ==========================
  // Mobile Navigation Drawer / Dropdown
  // ==========================
  function initMobileNav() {
    const menuToggle = document.getElementById("menuToggle");
    const nav = document.getElementById("mainNav") || document.querySelector(".site-header .nav");
    if (!menuToggle || !nav) return;

    const iconMenu = menuToggle.querySelector(".icon-menu");
    const iconClose = menuToggle.querySelector(".icon-close");

    function setMenuOpen(isOpen) {
      const textEl = menuToggle.querySelector(".menu-toggle-text");
      const currentLang = document.documentElement.lang || "ar";
      const openLabel = currentLang === "en" ? "Close" : "إغلاق";
      const closedLabel = currentLang === "en" ? "Menu" : "القائمة";

      if (isOpen) {
        nav.classList.add("is-open");
        menuToggle.classList.add("is-open");
        menuToggle.setAttribute("aria-expanded", "true");
        menuToggle.setAttribute("aria-label", openLabel);
        menuToggle.setAttribute("title", openLabel);
        if (iconMenu) iconMenu.style.display = "none";
        if (iconClose) iconClose.style.display = "inline-block";
        if (textEl) textEl.textContent = openLabel;
        document.body.classList.add("mobile-menu-active");
      } else {
        nav.classList.remove("is-open");
        menuToggle.classList.remove("is-open");
        menuToggle.setAttribute("aria-expanded", "false");
        menuToggle.setAttribute("aria-label", closedLabel);
        menuToggle.setAttribute("title", closedLabel);
        if (iconMenu) iconMenu.style.display = "inline-block";
        if (iconClose) iconClose.style.display = "none";
        if (textEl) textEl.textContent = closedLabel;
        document.body.classList.remove("mobile-menu-active");
      }
    }

    menuToggle.addEventListener("click", function (e) {
      e.stopPropagation();
      const isOpen = nav.classList.contains("is-open");
      setMenuOpen(!isOpen);
    });

    // Close on any navlink click
    nav.querySelectorAll(".navlink").forEach(function (link) {
      link.addEventListener("click", function () {
        setMenuOpen(false);
      });
    });

    // Close on outside click
    document.addEventListener("click", function (e) {
      if (nav.classList.contains("is-open")) {
        if (!nav.contains(e.target) && !menuToggle.contains(e.target)) {
          setMenuOpen(false);
        }
      }
    });

    // Close on Escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && nav.classList.contains("is-open")) {
        setMenuOpen(false);
        menuToggle.focus();
      }
    });

    // Close when window resized back to desktop (> 980px)
    window.addEventListener("resize", function () {
      if (window.innerWidth > 980 && nav.classList.contains("is-open")) {
        setMenuOpen(false);
      }
    });
  }

  // ==========================
  // Authentication Manager (Sanctum Tokens)
  // ==========================
  const AUTH_TOKEN_KEY = "sanctum_token";
  const AUTH_USER_KEY = "auth_user";

  function getApiBaseUrl() {
    return "https://api.almashreqebookstore.com";
  }

  function getAuthToken() {
    try {
      return localStorage.getItem(AUTH_TOKEN_KEY) || "";
    } catch (_) {
      return "";
    }
  }

  function getAuthUser() {
    try {
      const raw = localStorage.getItem(AUTH_USER_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (_) {
      return null;
    }
  }

  function setAuthSession(token, user) {
    try {
      if (token) localStorage.setItem(AUTH_TOKEN_KEY, token);
      if (user) localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user));
    } catch (e) {
      console.warn("[Auth] Failed to write to localStorage:", e);
    }
    updateNavbarAuthState();
    window.dispatchEvent(new CustomEvent("authstatechange", { detail: { isLoggedIn: Boolean(token), user } }));
  }

  function clearAuthSession() {
    try {
      localStorage.removeItem(AUTH_TOKEN_KEY);
      localStorage.removeItem(AUTH_USER_KEY);
    } catch (_) {}
    updateNavbarAuthState();
    window.dispatchEvent(new CustomEvent("authstatechange", { detail: { isLoggedIn: false, user: null } }));
  }

  function updateNavbarAuthState() {
    const guestEl = document.getElementById("authGuest");
    const userEl = document.getElementById("authUser");
    const nameEl = document.getElementById("authUserName");

    const token = getAuthToken();
    const user = getAuthUser();

    if (token && user) {
      if (guestEl) {
        guestEl.style.setProperty("display", "none", "important");
        guestEl.classList.add("is-hidden");
        guestEl.hidden = true;
      }
      if (userEl) {
        userEl.style.setProperty("display", "inline-flex", "important");
        userEl.classList.remove("is-hidden");
        userEl.hidden = false;
      }
      if (nameEl) nameEl.textContent = user.name || user.email || "";
    } else {
      if (guestEl) {
        guestEl.style.setProperty("display", "inline-flex", "important");
        guestEl.classList.remove("is-hidden");
        guestEl.hidden = false;
      }
      if (userEl) {
        userEl.style.setProperty("display", "none", "important");
        userEl.classList.add("is-hidden");
        userEl.hidden = true;
      }
      if (nameEl) nameEl.textContent = "";
    }
  }

  async function verifyAuthSession() {
    const token = getAuthToken();
    if (!token) return;

    try {
      const res = await fetch(`${getApiBaseUrl()}/api/v1/user`, {
        method: "GET",
        headers: {
          "Accept": "application/json",
          "Authorization": `Bearer ${token}`
        }
      });

      if (res.status === 401) {
        clearAuthSession();
      } else if (res.ok) {
        const data = await res.json();
        if (data && data.user) {
          localStorage.setItem(AUTH_USER_KEY, JSON.stringify(data.user));
          updateNavbarAuthState();
        }
      }
    } catch (_) {
      // Network offline; keep local session
    }
  }

  function openAuthModal(defaultTab = "login") {
    const modal = document.getElementById("authModal");
    if (!modal) return;
    setAuthModalTab(defaultTab);
    clearAuthAlert();
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";

    // Focus first input in active form
    setTimeout(() => {
      const activeForm = modal.querySelector(".auth-form.is-active");
      const firstInput = activeForm ? activeForm.querySelector("input") : null;
      if (firstInput) firstInput.focus();
    }, 150);
  }

  function closeAuthModal() {
    const modal = document.getElementById("authModal");
    if (!modal) return;
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    clearAuthAlert();
  }

  function setAuthModalTab(tab = "login") {
    const tabLogin = document.getElementById("tabBtnLogin");
    const tabRegister = document.getElementById("tabBtnRegister");
    const formLogin = document.getElementById("loginForm");
    const formRegister = document.getElementById("registerForm");

    clearAuthAlert();

    if (tab === "register") {
      tabLogin?.classList.remove("is-active");
      tabLogin?.setAttribute("aria-selected", "false");
      tabRegister?.classList.add("is-active");
      tabRegister?.setAttribute("aria-selected", "true");

      formLogin?.classList.remove("is-active");
      formRegister?.classList.add("is-active");
    } else {
      tabRegister?.classList.remove("is-active");
      tabRegister?.setAttribute("aria-selected", "false");
      tabLogin?.classList.add("is-active");
      tabLogin?.setAttribute("aria-selected", "true");

      formRegister?.classList.remove("is-active");
      formLogin?.classList.add("is-active");
    }
  }

  function showAuthAlert(message, type = "error") {
    const alertEl = document.getElementById("authAlert");
    const textEl = document.getElementById("authAlertText");
    if (!alertEl || !textEl) return;

    alertEl.className = `auth-alert is-${type}`;
    textEl.textContent = message;
    alertEl.style.display = "flex";
  }

  function clearAuthAlert() {
    const alertEl = document.getElementById("authAlert");
    const textEl = document.getElementById("authAlertText");
    if (alertEl) alertEl.style.display = "none";
    if (textEl) textEl.textContent = "";
  }

  async function handleLoginSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = document.getElementById("loginSubmitBtn");
    const email = form.email.value.trim();
    const password = form.password.value;

    const isAr = currentLang === "ar";

    if (!email || !password) {
      showAuthAlert(isAr ? "يرجى ملء جميع الحقول المطلوبة." : "Please fill in all required fields.", "error");
      return;
    }

    setBtnLoading(submitBtn, true, isAr ? "جاري تسجيل الدخول..." : "Logging in...");
    clearAuthAlert();

    try {
      const res = await fetch(`${getApiBaseUrl()}/api/v1/login`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json"
        },
        body: JSON.stringify({ email, password })
      });

      const data = await res.json();

      if (!res.ok) {
        const errorMsg = data.message || (isAr ? "بيانات الاعتماد غير صحيحة." : "Invalid credentials.");
        showAuthAlert(errorMsg, "error");
        return;
      }

      const token = data.access_token || data.token;
      setAuthSession(token, data.user);
      showAuthAlert(isAr ? "تم تسجيل الدخول بنجاح!" : "Logged in successfully!", "success");

      setTimeout(() => {
        closeAuthModal();
        form.reset();
      }, 700);

    } catch (err) {
      console.error("[Auth] Login error:", err);
      showAuthAlert(isAr ? "تعذر الاتصال بالخادم. يرجى المحاولة لاحقاً." : "Network error. Please try again.", "error");
    } finally {
      setBtnLoading(submitBtn, false);
    }
  }

  async function handleRegisterSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = document.getElementById("registerSubmitBtn");
    const name = form.name.value.trim();
    const email = form.email.value.trim();
    const password = form.password.value;
    const password_confirmation = form.password_confirmation.value;

    const isAr = currentLang === "ar";

    if (!name || !email || !password || !password_confirmation) {
      showAuthAlert(isAr ? "يرجى ملء جميع الحقول المطلوبة." : "Please fill in all required fields.", "error");
      return;
    }

    if (password.length < 8) {
      showAuthAlert(isAr ? "يجب ألا تقل كلمة المرور عن 8 أحرف." : "Password must be at least 8 characters.", "error");
      return;
    }

    if (password !== password_confirmation) {
      showAuthAlert(isAr ? "كلمة المرور وتأكيدها غير متطابقين." : "Passwords do not match.", "error");
      return;
    }

    setBtnLoading(submitBtn, true, isAr ? "جاري إنشاء الحساب..." : "Creating account...");
    clearAuthAlert();

    try {
      const res = await fetch(`${getApiBaseUrl()}/api/v1/register`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json"
        },
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation,
          preferred_lang: currentLang
        })
      });

      const data = await res.json();

      if (!res.ok) {
        let msg = data.message || (isAr ? "تعذر إنشاء الحساب." : "Failed to create account.");
        if (data.errors) {
          const firstErr = Object.values(data.errors)[0];
          if (Array.isArray(firstErr) && firstErr[0]) msg = firstErr[0];
        }
        showAuthAlert(msg, "error");
        return;
      }

      const token = data.access_token || data.token;
      setAuthSession(token, data.user);
      showAuthAlert(isAr ? "تم إنشاء الحساب بنجاح!" : "Account created successfully!", "success");

      setTimeout(() => {
        closeAuthModal();
        form.reset();
      }, 700);

    } catch (err) {
      console.error("[Auth] Registration error:", err);
      showAuthAlert(isAr ? "تعذر الاتصال بالخادم. يرجى المحاولة لاحقاً." : "Network error. Please try again.", "error");
    } finally {
      setBtnLoading(submitBtn, false);
    }
  }

  async function handleLogout() {
    const token = getAuthToken();

    if (token) {
      try {
        await fetch(`${getApiBaseUrl()}/api/v1/logout`, {
          method: "POST",
          headers: {
            "Accept": "application/json",
            "Authorization": `Bearer ${token}`
          }
        });
      } catch (_) {}
    }

    clearAuthSession();
  }

  function setBtnLoading(btn, isLoading, loadingText) {
    if (!btn) return;
    btn.disabled = isLoading;
    if (isLoading) {
      btn.dataset.origHtml = btn.innerHTML;
      btn.innerHTML = `<span class="auth-spinner"></span> <span>${loadingText || ""}</span>`;
    } else if (btn.dataset.origHtml) {
      btn.innerHTML = btn.dataset.origHtml;
      delete btn.dataset.origHtml;
    }
  }

  function initAuthUI() {
    updateNavbarAuthState();
    verifyAuthSession();

    // Trigger open button
    document.querySelectorAll("#openAuthModalBtn, .open-auth-modal").forEach(btn => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        openAuthModal("login");
      });
    });

    // Close button & backdrop
    document.getElementById("closeAuthModalBtn")?.addEventListener("click", closeAuthModal);
    document.getElementById("authModalBackdrop")?.addEventListener("click", closeAuthModal);

    // Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        const modal = document.getElementById("authModal");
        if (modal && modal.classList.contains("is-open")) {
          closeAuthModal();
        }
      }
    });

    // Tab buttons
    document.getElementById("tabBtnLogin")?.addEventListener("click", () => setAuthModalTab("login"));
    document.getElementById("tabBtnRegister")?.addEventListener("click", () => setAuthModalTab("register"));

    // Switch links
    document.getElementById("switchToRegisterBtn")?.addEventListener("click", (e) => {
      e.preventDefault();
      setAuthModalTab("register");
    });
    document.getElementById("switchToLoginBtn")?.addEventListener("click", (e) => {
      e.preventDefault();
      setAuthModalTab("login");
    });

    // Forms
    document.getElementById("loginForm")?.addEventListener("submit", handleLoginSubmit);
    document.getElementById("registerForm")?.addEventListener("submit", handleRegisterSubmit);

    // Logout
    document.getElementById("logoutBtn")?.addEventListener("click", (e) => {
      e.preventDefault();
      handleLogout();
    });

    // Password visibility toggles
    document.querySelectorAll(".auth-password-toggle").forEach(toggle => {
      toggle.addEventListener("click", (e) => {
        e.preventDefault();
        const targetId = toggle.getAttribute("data-target");
        const input = document.getElementById(targetId);
        if (!input) return;

        const isPass = input.type === "password";
        input.type = isPass ? "text" : "password";

        toggle.innerHTML = isPass
          ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
          : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
      });
    });
  }

  // ==========================
  // Global Lifecycle Initialization
  // ==========================
  function initGlobal() {
    // Dynamic copyright year
    const yearEl = document.getElementById("year");
    if (yearEl) {
      yearEl.textContent = new Date().getFullYear();
    }

    // Back to top button
    const backToTopBtn = document.getElementById("backToTop");
    if (backToTopBtn) {
      backToTopBtn.addEventListener("click", (e) => {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
    }

    // Theme toggle button
    const themeBtn = document.getElementById("themeToggle");
    if (themeBtn) {
      themeBtn.addEventListener("click", (e) => {
        e.preventDefault();
        const cur = document.documentElement.getAttribute("data-theme") || "light";
        const next = cur === "dark" ? "light" : "dark";
        applyTheme(next);
      });
    }

    // Language toggle button
    const langBtn = document.getElementById("langToggle");
    if (langBtn) {
      langBtn.addEventListener("click", (e) => {
        e.preventDefault();
        const next = currentLang === "ar" ? "en" : "ar";
        applyLanguage(next);
      });
    }

    initHeaderScroll();
    initMobileNav();
    initAuthUI();

    // Hydrate current state
    applyTheme(currentTheme);
    applyLanguage(currentLang);
  }

  // Expose global methods
  window.GlobalApp = {
    applyTheme,
    applyLanguage,
    getTheme: () => currentTheme,
    getLang: () => currentLang,
    I18N
  };

  window.MashreqAuth = {
    getToken: getAuthToken,
    getUser: getAuthUser,
    isLoggedIn: () => Boolean(getAuthToken()),
    openModal: openAuthModal,
    closeModal: closeAuthModal,
    logout: handleLogout,
    updateUI: updateNavbarAuthState
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initGlobal);
  } else {
    initGlobal();
  }
})();
