# مقترح الواجهة الخلفية التقني وتقرير تصميم النظام
## مكتبة المشرق الإلكترونية — تحويل الموقع من ثابت إلى ديناميكي

**مُعَدٌّ لـ:** فريق تطوير مكتبة المشرق الإلكترونية  
**التاريخ:** أكتوبر 2026  
**النظام الحالي:** HTML/CSS/JS ثابت + ملف JSON مسطح (2,825 كتاباً، ~12.6 ميغابايت)  
**الاستضافة:** Namecheap Shared cPanel (Apache, PHP 8.x, MySQL, Node.js عبر Passenger/CloudLinux)

---

## جدول المحتويات

1. [الملخص التنفيذي](#1-الملخص-التنفيذي)
2. [توصية الإطار: PHP (Laravel) مقابل Node.js (NestJS/Express)](#2-توصية-الإطار)
3. [مخطط قاعدة البيانات والنماذج (ERD)](#3-مخطط-قاعدة-البيانات-والنماذج)
4. [هيكل نقاط نهاية API الـ RESTful](#4-هيكل-نقاط-نهاية-api)
5. [خارطة طريق التنفيذ خطوة بخطوة](#5-خارطة-طريق-التنفيذ)
6. [هيكل الأمان](#6-هيكل-الأمان)
7. [الملحق: عقد بيانات الواجهة الأمامية الحالية](#7-الملحق-عقد-البيانات)

---

## 1. الملخص التنفيذي

يقوم النظام الحالي بتحميل ملف **`books.json` الضخم (12.6 ميغابايت)** في كل زيارة للصفحة. ورغم أنه يؤدي وظيفته، إلا أن هذه البنية قد وصلت إلى حدود قابليتها للتوسع:

- **الأداء:** يضطر المتصفح إلى تحليل مجموعة البيانات الكاملة (~2,825 كتاباً) حتى لو كان المعروض منها 24 كتاباً فقط في الصفحة.
- **عقبة الإدارة:** إضافة كتاب جديد تستلزم تعديل ملف JSON يدوياً وإعادة رفعه.
- **انعدام المصادقة:** لا توجد حسابات مستخدمين، ولا تتبع للطلبات، ولا تخصيص للتجربة.
- **انعدام التجارة:** روابط الشراء مجرد إعادة توجيه خارجية ثابتة بلا أي قدرة على إجراء المعاملات داخلياً.
- **ضعف تحسين محركات البحث:** الصفحات الديناميكية مثل `/book/ISBN` تُعرض على جانب العميل فقط بلا محتوى مُرسَل من الخادم.

تهدف الواجهة الخلفية المقترحة إلى الاستعاضة عن ذلك ببنية **API-first متكاملة**، تقدم البيانات مُرقَّمة ومُصفَّاة ومُرتَّبة من جانب الخادم، وتتيح عمليات CRUD للمسؤولين، ومصادقة المستخدمين، ومنصة جاهزة لميزات التجارة الإلكترونية أو السوق الرقمي.

---

## 2. توصية الإطار

### 2.1 تحليل الخيارات المتاحة

#### الخيار الأول — Node.js (NestJS أو Express)

| العامل | التقييم |
|---|---|
| **التوافق مع cPanel** | ⚠️ يتطلب تشغيل Node.js عبر Passenger/CloudLinux. تدعمه Namecheap، لكن الإعداد هش: مدير العمليات، بروكسي `.htaccess`، ومشاكل الاستمرارية عند إعادة التشغيل. |
| **موثوقية الإقلاع البارد** | ⚠️ كثيراً ما تُوقَف عمليات Node على الاستضافة المشتركة بسبب حدود الذاكرة، وتستلزم إعادة تشغيل يدوياً عبر cPanel. |
| **تعقيد النشر** | ❌ مرتفع. يستلزم وصول SSH، وتثبيت npm على الخادم، وملفات إعداد Passenger، وبروكسي `.htaccess`. |
| **ORM قاعدة البيانات** | ✅ ممتاز (Prisma, TypeORM, Sequelize). |
| **الأداء** | ✅ I/O غير متزامن فائق، مثالي لواجهات API عالية التزامن. |
| **دعم العربية/RTL** | ✅ معالجة JS الأصلية للنصوص تتعامل مع العربية بشكل مثالي. |
| **الحكم على الاستضافة المشتركة** | ❌ **غير موصى به.** تكاليف التشغيل وعدم الاستقرار على cPanel المشترك تفوقان مكاسب الأداء بهذا الحجم. |

#### الخيار الثاني — PHP (Laravel)

| العامل | التقييم |
|---|---|
| **التوافق مع cPanel** | ✅ **مواطن أصيل من الدرجة الأولى.** يعمل PHP مباشرة عبر Apache mod_php. لا حاجة لأي ضبط للعمليات. |
| **النشر** | ✅ رفع الملفات عبر FTP/Git. لا daemon، لا Passenger، لا ربط بمنفذ. يعمل فوراً. |
| **الاستقرار** | ✅ PHP عديم الحالة؛ كل طلب مستقل. لا انهيارات للعمليات، لا إعادة تشغيل يدوية. |
| **ORM قاعدة البيانات** | ✅ Eloquent ORM ناضج وتعبيري وموثق توثيقاً ممتازاً. |
| **إطار API** | ✅ Laravel إطار full-stack مُختبَر في المعارك، يتضمن Auth (Sanctum/Passport) وقوائم الانتظار والتخزين المؤقت وسقالات REST. |
| **دعم العربية/UTF-8** | ✅ دعم كامل لـ UTF-8. مساعدات `Str` في Laravel تتعامل مع العربية بشكل صحيح. |
| **لوحة الإدارة** | ✅ Filament (من نظام Laravel البيئي) يوفر واجهة مسؤول جاهزة للإنتاج في ساعات لا أيام. |
| **الحكم على الاستضافة المشتركة** | ✅ **موصى به بشدة.** Laravel هو الخيار الصناعي القياسي لبيئات cPanel والاستضافة المشتركة. |

### 2.2 التوصية النهائية

> **استخدام Laravel 11 (PHP 8.2+) مع MySQL.**

**المبرر:** على Namecheap cPanel المشترك، يعمل PHP كبيئة تشغيل بلا ضبط. تتوافق بنية Laravel تماماً مع متطلبات هذا المشروع: واجهة RESTful API نظيفة للواجهة الأمامية الحالية، ولوحة إدارة مدعومة بـ Filament، ومصادقة قائمة على Sanctum، وORM Eloquent لقاعدة بيانات MySQL. يمكن نشر الواجهة الخلفية بالكامل دون الحاجة إلى وصول SSH أو مديري العمليات أو ضبط خادم معقد. وبالحجم الحالي البالغ ~2,825 كتاباً، أداء Laravel أكثر من كافٍ، ونظامه البيئي يوفر كل ميزة يتطلبها هذا المسار.

---

## 3. مخطط قاعدة البيانات والنماذج

### 3.1 نظرة عامة على علاقات الكيانات

```
users ←──────────── orders ─────────────→ order_items ←──── books
  │                                                               │
  └──── reading_lists ──────────────────────────────────→ books  │
                                                                  │
books ←──── book_platform_links                                   │
  │                                                               │
  ├──── book_bisac_codes                                          │
  ├──── book_keywords                                             │
  └──── book_subjects                                             │
                                                                  │
categories ←────────────────────────────────────────────── books  │
authors ←───────────────────────────────────────────────── books  │
publishers ←────────────────────────────────────────────── books  │
platform_links ←──────────────────────────────── book_platform_links
blog_posts (مستقل)
contact_submissions (مستقل)
```

---

### 3.2 تعريفات النماذج

#### `users` — المستخدمون
نموذج المصادقة المركزي.

| العمود | النوع | القيود | الملاحظات |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(255) | NOT NULL | الاسم المعروض |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | معرف تسجيل الدخول |
| `password` | VARCHAR(255) | NOT NULL | مشفر (bcrypt) |
| `role` | ENUM | DEFAULT 'customer' | `admin`، `editor`، `customer` |
| `preferred_lang` | ENUM | DEFAULT 'ar' | `ar`، `en` |
| `email_verified_at` | TIMESTAMP | NULLABLE | طابع زمني للتحقق |
| `remember_token` | VARCHAR(100) | NULLABLE | |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

**العلاقات:**
- `hasMany(Order)` — لديه طلبات متعددة
- `hasMany(ReadingList)` — لديه قوائم قراءة متعددة

---

#### `authors` — المؤلفون
كيان المؤلف المُعيَّر، مستخرج من ملف JSON المسطح.

| العمود | النوع | القيود | الملاحظات |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `name` | VARCHAR(500) | NOT NULL, INDEX | اسم المؤلف المعروض |
| `slug` | VARCHAR(500) | UNIQUE, NOT NULL | معرف آمن لعنوان URL |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**العلاقات:**
- `hasMany(Book)` — لديه كتب متعددة

---

#### `publishers` — الناشرون
كيان الناشر المُعيَّر.

| العمود | النوع | القيود | الملاحظات |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `name` | VARCHAR(500) | NOT NULL, INDEX | |
| `slug` | VARCHAR(500) | UNIQUE, NOT NULL | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**العلاقات:**
- `hasMany(Book)` — لديه كتب متعددة

---

#### `categories` — التصنيفات
تسلسل هرمي ثنائي المستوى يتوافق مع `CategoryMain` → `Subject_2`.

| العمود | النوع | القيود | الملاحظات |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `parent_id` | BIGINT UNSIGNED | NULLABLE, FK → categories.id | NULL = المستوى الأعلى |
| `name` | VARCHAR(255) | NOT NULL | مثل: "Nonfiction"، "Fiction" |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**العلاقات:**
- `belongsTo(Category, 'parent_id')` — مرجع ذاتي للأصل
- `hasMany(Category)` — فروع أبناء
- `hasMany(Book)` — لديه كتب متعددة

---

#### `books` — الكتب
النموذج الأساسي للكتالوج. تُعيَّر هنا جميع حقول JSON المسطحة.

| العمود | النوع | القيود | الملاحظات |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `isbn` | VARCHAR(20) | UNIQUE, NOT NULL, INDEX | مفتاح البحث الرئيسي |
| `title` | VARCHAR(1000) | NOT NULL, FULLTEXT INDEX | العنوان |
| `subtitle` | VARCHAR(1000) | NULLABLE | العنوان الفرعي |
| `series` | VARCHAR(500) | NULLABLE | السلسلة |
| `synopsis` | LONGTEXT | NULLABLE, FULLTEXT INDEX | الملخص |
| `pages` | SMALLINT UNSIGNED | NULLABLE | عدد الصفحات |
| `language` | VARCHAR(50) | NOT NULL, INDEX | مثل: "Arabic"، "English" |
| `age_range` | VARCHAR(50) | NULLABLE | مثل: "Kids"، "YA" |
| `cover_path` | VARCHAR(500) | NULLABLE | `/covers/{isbn}.jpg` |
| `search_vector` | LONGTEXT | NULLABLE | نص البحث المُبنى مسبقاً (يعكس `_search`) |
| `is_featured` | TINYINT | DEFAULT 0 | علامة الإبراز في الصفحة الرئيسية |
| `is_published` | TINYINT | DEFAULT 1 | تبديل لطيف لإخفاء الكتاب من الكتالوج |
| `author_id` | BIGINT UNSIGNED | FK → authors.id, INDEX | |
| `publisher_id` | BIGINT UNSIGNED | FK → publishers.id, INDEX | |
| `category_id` | BIGINT UNSIGNED | FK → categories.id, INDEX | التصنيف الرئيسي |
| `sub_category_id` | BIGINT UNSIGNED | FK → categories.id, NULLABLE | التصنيف الفرعي |
| `contributor_name` | VARCHAR(500) | NULLABLE | اسم المساهم |
| `contributor_role` | VARCHAR(100) | NULLABLE | مثل: "Editor"، "Translator" |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**العلاقات:**
- `belongsTo(Author)` — ينتمي إلى مؤلف
- `belongsTo(Publisher)` — ينتمي إلى ناشر
- `belongsTo(Category)` — التصنيف الرئيسي
- `belongsTo(Category, 'sub_category_id')` — التصنيف الفرعي
- `hasMany(BookPlatformLink)` — لديه روابط منصات متعددة
- `hasMany(BookBisacCode)` — لديه أكواد BISAC متعددة
- `hasMany(BookKeyword)` — لديه كلمات مفتاحية متعددة
- `belongsToMany(ReadingList)` — عبر جدول الوسيط `reading_list_book`

---

#### `book_platform_links` — روابط منصات الكتب
يحل محل مصفوفة `Links[]` في ملف JSON الحالي.

| العمود | النوع | القيود | الملاحظات |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `book_id` | BIGINT UNSIGNED | FK → books.id, INDEX | |
| `platform_name` | VARCHAR(100) | NOT NULL | مثل: "Jarir"، "OverDrive"، "Amazon" |
| `url` | TEXT | NOT NULL | رابط الشراء/القراءة الخارجي |
| `sort_order` | TINYINT | DEFAULT 0 | أولوية العرض |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**العلاقات:**
- `belongsTo(Book)` — ينتمي إلى كتاب

---

#### `book_bisac_codes` — أكواد BISAC
تخزين كود BISAC واحد أو أكثر لكل كتاب.

| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `book_id` | BIGINT UNSIGNED FK | |
| `code` | VARCHAR(20) | مثل: "BUS023000" |

---

#### `book_keywords` — كلمات مفتاحية
كلمات مفتاحية مُعيَّرة لفهرسة البحث النصي الكامل.

| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `book_id` | BIGINT UNSIGNED FK | |
| `keyword` | VARCHAR(255) | كلمة مفتاحية واحدة، مفهرسة |

---

#### `reading_lists` — قوائم القراءة *(مرحلة مستقبلية)*
مجموعات الكتب التي يحفظها المستخدم (قائمة الأمنيات / قائمة القراءة).

| العمود | النوع | القيود | الملاحظات |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `user_id` | BIGINT UNSIGNED | FK → users.id | |
| `name` | VARCHAR(255) | NOT NULL | مثل: "قائمة أمنياتي" |
| `is_public` | TINYINT | DEFAULT 0 | عامة/خاصة |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**جدول الوسيط:** `reading_list_book (reading_list_id, book_id)`

---

#### `orders` — الطلبات *(مرحلة مستقبلية)*
رأس الطلب لمشتريات السوق.

| العمود | النوع | القيود | الملاحظات |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `user_id` | BIGINT UNSIGNED | FK → users.id | NULLABLE للزوار |
| `status` | ENUM | NOT NULL | `pending`، `completed`، `failed`، `refunded` |
| `total_amount` | DECIMAL(10,2) | NOT NULL | المبلغ الإجمالي |
| `currency` | CHAR(3) | DEFAULT 'USD' | العملة |
| `payment_gateway` | VARCHAR(50) | NULLABLE | مثل: "Stripe"، "PayPal" |
| `gateway_ref` | VARCHAR(255) | NULLABLE | معرف المعاملة الخارجي |
| `customer_email` | VARCHAR(255) | NOT NULL | بريد العميل |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

---

#### `order_items` — عناصر الطلب *(مرحلة مستقبلية)*
السطور التفصيلية داخل الطلب.

| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `order_id` | BIGINT UNSIGNED FK | |
| `book_id` | BIGINT UNSIGNED FK | |
| `price_at_purchase` | DECIMAL(10,2) | لقطة للسعر وقت البيع |
| `platform_link_id` | BIGINT UNSIGNED FK NULLABLE | المنصة المستخدمة |

---

#### `blog_posts` — مقالات المدونة
يعكس هيكل ملف `blog.json` الحالي.

| العمود | النوع | القيود | الملاحظات |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `author_id` | BIGINT UNSIGNED | FK → users.id, NULLABLE | مؤلف المقال (مسؤول) |
| `title` | VARCHAR(1000) | NOT NULL | العنوان |
| `slug` | VARCHAR(1000) | UNIQUE, NOT NULL | المعرف في URL |
| `excerpt` | TEXT | NULLABLE | مقتطف |
| `content` | LONGTEXT | NOT NULL | المحتوى الكامل |
| `published_at` | TIMESTAMP | NULLABLE | NULL = مسودة |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

---

#### `contact_submissions` — رسائل الاتصال
تلتقط الرسائل المُرسَلة من نموذج الاتصال.

| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | الاسم |
| `email` | VARCHAR(255) | البريد الإلكتروني |
| `subject` | VARCHAR(500) | الموضوع |
| `message` | TEXT | الرسالة |
| `ip_address` | VARCHAR(45) | للحد من معدل الطلبات |
| `created_at` | TIMESTAMP | |

---

### 3.3 ملخص مخطط ERD

```
authors ──┐
          ├──→ books ←── categories (أصل/فرع، مرجع ذاتي)
publishers─┘      │
                  ├──→ book_platform_links
                  ├──→ book_bisac_codes
                  ├──→ book_keywords
                  └──→ reading_list_book ←── reading_lists ←── users
                                                                  │
                                                               orders ──→ order_items
blog_posts ←── users
contact_submissions (مستقل)
```

---

## 4. هيكل نقاط نهاية API الـ RESTful

جميع نقاط النهاية مسبوقة بـ `/api/v1`. الاستجابات بتنسيق JSON. المصادقة تستخدم **Laravel Sanctum** (قائمة على الرمز المميز لواجهة SPA).

### 4.1 مسارات المصادقة (`/api/v1/auth`)

| الطريقة | نقطة النهاية | المصادقة | الوصف |
|---|---|---|---|
| `POST` | `/auth/register` | عامة | تسجيل مستخدم جديد (بريد + كلمة مرور) |
| `POST` | `/auth/login` | عامة | تسجيل الدخول ← يُرجع رمز Bearer |
| `POST` | `/auth/logout` | 🔐 Bearer | إلغاء صلاحية الرمز الحالي |
| `GET` | `/auth/me` | 🔐 Bearer | إرجاع بيانات المستخدم المسجل دخوله |
| `POST` | `/auth/forgot-password` | عامة | إرسال بريد إعادة تعيين كلمة المرور |
| `POST` | `/auth/reset-password` | عامة | تأكيد الإعادة برمز + كلمة مرور جديدة |
| `PATCH` | `/auth/profile` | 🔐 Bearer | تحديث الاسم واللغة وكلمة المرور |

---

### 4.2 مسارات كتالوج الكتب (`/api/v1/books`)

| الطريقة | نقطة النهاية | المصادقة | الوصف |
|---|---|---|---|
| `GET` | `/books` | عامة | قائمة كتب مرقَّمة مع الفلاتر |
| `GET` | `/books/{isbn}` | عامة | تفاصيل كتاب واحد (جميع الحقول + الروابط) |
| `GET` | `/books/featured` | عامة | الكتب المميزة/البطولية للصفحة الرئيسية |
| `GET` | `/books/latest` | عامة | أحدث الكتب المضافة |
| `GET` | `/books/search` | عامة | بحث نصي كامل (`?q=...`) |

**معاملات `GET /books`:**

| المعامل | النوع | مثال | الوصف |
|---|---|---|---|
| `q` | نص | `?q=أدب` | بحث نصي كامل (العنوان، الملخص، الكلمات المفتاحية) |
| `category` | نص | `?category=fiction` | تصفية حسب slug التصنيف الرئيسي |
| `subcategory` | نص | `?subcategory=business-economics` | تصفية حسب slug التصنيف الفرعي |
| `author` | نص | `?author=adam-smith` | تصفية حسب slug المؤلف |
| `language` | نص | `?language=Arabic` | تصفية حسب اللغة |
| `sort` | نص | `?sort=title_asc` | `title_asc`، `title_desc`، `author_asc`، `language_asc` |
| `view_by` | نص | `?view_by=author` | `author`، `category`، `language`، `alpha` |
| `page` | رقم | `?page=2` | رقم الصفحة |
| `per_page` | رقم | `?per_page=24` | النتائج لكل صفحة (الحد الأقصى: 100) |

**مثال على شكل الاستجابة (`GET /books`):**
```json
{
  "data": [
    {
      "isbn": "9781259455636",
      "title": "ملخص تحليلي لثروة الأمم",
      "author": { "name": "Adam Smith", "slug": "adam-smith" },
      "language": "English",
      "category": { "name": "Nonfiction", "slug": "nonfiction" },
      "cover_url": "/covers/9781259455636.jpg",
      "book_url": "/book/9781259455636",
      "platform_links": [
        { "name": "Jarir", "url": "https://jarirreader.com/book/48656/" }
      ]
    }
  ],
  "meta": {
    "total": 2825,
    "shown": 24,
    "page": 1,
    "per_page": 24,
    "last_page": 118,
    "category_count": 12
  }
}
```

---

### 4.3 مسارات التصنيفات والتصنيف الضرائبي (`/api/v1/categories`)

| الطريقة | نقطة النهاية | المصادقة | الوصف |
|---|---|---|---|
| `GET` | `/categories` | عامة | جميع التصنيفات الرئيسية مع أعداد الكتب |
| `GET` | `/categories/{slug}` | عامة | تفاصيل التصنيف + التصنيفات الفرعية |
| `GET` | `/categories/{slug}/books` | عامة | كتب داخل تصنيف (مرقَّمة) |
| `GET` | `/authors` | عامة | جميع المؤلفين مع أعداد الكتب |
| `GET` | `/authors/{slug}/books` | عامة | كتب مؤلف محدد (مرقَّمة) |
| `GET` | `/languages` | عامة | اللغات المتميزة مع أعداد الكتب |

---

### 4.4 مسارات المدونة (`/api/v1/blog`)

| الطريقة | نقطة النهاية | المصادقة | الوصف |
|---|---|---|---|
| `GET` | `/blog` | عامة | جميع المقالات المنشورة (مرقَّمة) |
| `GET` | `/blog/{slug}` | عامة | تفاصيل مقال واحد |

---

### 4.5 مسارات حساب المستخدم (`/api/v1/user`)

| الطريقة | نقطة النهاية | المصادقة | الوصف |
|---|---|---|---|
| `GET` | `/user/reading-lists` | 🔐 Bearer | جميع قوائم القراءة للمستخدم المسجل |
| `POST` | `/user/reading-lists` | 🔐 Bearer | إنشاء قائمة قراءة جديدة |
| `POST` | `/user/reading-lists/{id}/books` | 🔐 Bearer | إضافة كتاب (بالـ ISBN) إلى قائمة |
| `DELETE` | `/user/reading-lists/{id}/books/{isbn}` | 🔐 Bearer | حذف كتاب من القائمة |
| `GET` | `/user/orders` | 🔐 Bearer | سجل طلبات المستخدم الحالي |
| `GET` | `/user/orders/{id}` | 🔐 Bearer | تفاصيل طلب واحد |

---

### 4.6 مسارات لوحة الإدارة (`/api/v1/admin`)

> **جميع مسارات الإدارة تستلزم:** `🔐 Bearer + role: admin`

#### إدارة الكتب

| الطريقة | نقطة النهاية | الوصف |
|---|---|---|
| `GET` | `/admin/books` | جميع الكتب (بما فيها غير المنشورة)، عرض إداري كامل |
| `POST` | `/admin/books` | إنشاء سجل كتاب جديد |
| `GET` | `/admin/books/{isbn}` | تفاصيل كتاب واحد (عرض إداري) |
| `PUT` | `/admin/books/{isbn}` | تحديث كامل للكتاب |
| `PATCH` | `/admin/books/{isbn}` | تحديث جزئي (مثل تبديل `is_published`) |
| `DELETE` | `/admin/books/{isbn}` | حذف لطيف للكتاب |
| `POST` | `/admin/books/import` | استيراد جماعي من JSON (أداة الترحيل) |
| `POST` | `/admin/books/{isbn}/cover` | رفع صورة غلاف الكتاب |

#### إدارة التصنيفات

| الطريقة | نقطة النهاية | الوصف |
|---|---|---|
| `GET/POST` | `/admin/categories` | قائمة الكل / إنشاء جديد |
| `PUT/DELETE` | `/admin/categories/{id}` | تحديث / حذف |

#### إدارة المدونة

| الطريقة | نقطة النهاية | الوصف |
|---|---|---|
| `GET/POST` | `/admin/blog` | قائمة / إنشاء مقال |
| `PUT/DELETE` | `/admin/blog/{id}` | تحديث / حذف مقال |
| `PATCH` | `/admin/blog/{id}/publish` | تبديل حالة النشر |

#### إدارة المستخدمين

| الطريقة | نقطة النهاية | الوصف |
|---|---|---|
| `GET` | `/admin/users` | جميع المستخدمين (مرقَّمون) |
| `PATCH` | `/admin/users/{id}/role` | تغيير دور المستخدم |
| `DELETE` | `/admin/users/{id}` | تعطيل مستخدم |

#### التحليلات والتقارير

| الطريقة | نقطة النهاية | الوصف |
|---|---|---|
| `GET` | `/admin/stats` | مؤشرات الأداء: إجمالي الكتب، المستخدمون، الطلبات، أبرز التصنيفات |
| `GET` | `/admin/contact-submissions` | عرض رسائل نموذج الاتصال |

---

### 4.7 مسارات الأدوات العامة

| الطريقة | نقطة النهاية | الوصف |
|---|---|---|
| `POST` | `/contact` | إرسال نموذج الاتصال |
| `GET` | `/sitemap.json` | خريطة موقع قابلة للقراءة آلياً لجميع ISBNs |
| `GET` | `/health` | فحص صحة الـ API (للمراقبة) |

---

## 5. خارطة طريق التنفيذ خطوة بخطوة

### المرحلة 0 — إعداد البيئة (الأسبوع الأول)

**الهدف:** إنشاء بيئة التطوير المحلية وبنية cPanel الأساسية.

**الخطوات:**
1. **محلياً:** تثبيت PHP 8.2, Composer, MySQL (عبر XAMPP/Herd/Valet), وـ Laravel CLI.
2. **cPanel:** التحقق من أن إصدار PHP ≥ 8.2 (عبر MultiPHP Manager). إنشاء قاعدة بيانات MySQL مخصصة ومستخدم بصلاحيات كاملة.
3. **نطاق فرعي أو مجلد:** تهيئة نطاق فرعي (`api.almashreqebookstore.com`) في cPanel ليشير إلى مجلد `public/` في تثبيت Laravel. يُبقي هذا الـ API منفصلاً تماماً عن جذر الواجهة الأمامية.
4. **SSL:** التأكد من أن AutoSSL يغطي النطاق الفرعي.
5. **`.htaccess` لـ Laravel:** التأكد من أن جذر المستندات في cPanel يشير إلى `public/` ليتولى `public/.htaccess` المدمج معالجة التوجيه.

---

### المرحلة 1 — بناء سقالة مشروع Laravel (الأسبوع 1-2)

**الهدف:** إنشاء مشروع Laravel الأساسي مع جميع الحزم المطلوبة.

**الأوامر:**
```bash
composer create-project laravel/laravel almashreq-api
cd almashreq-api
composer require laravel/sanctum                 # المصادقة
composer require spatie/laravel-query-builder    # تصفية متقدمة لـ API
composer require spatie/laravel-permission       # التحكم بالوصول القائم على الأدوار
composer require filament/filament               # واجهة لوحة الإدارة
composer require league/flysystem-local          # تخزين الملفات للأغلفة
```

**الضبط:**
- `config/database.php`: ضبط اتصال MySQL.
- `config/cors.php`: السماح لنطاق الواجهة الأمامية (`almashreqebookstore.com`) بطلبات API عبر الأصول.
- `config/sanctum.php`: ضبط النطاقات ذات الحالة إذا استُخدم المصادقة القائمة على الكوكيز.
- `.env`: تعيين `APP_URL`, `DB_*`, `FILESYSTEM_DISK=local`.

---

### المرحلة 2 — ترحيل قاعدة البيانات والنماذج (الأسبوع 2-3)

**الهدف:** تعريف مخطط قاعدة البيانات الكامل عبر ترحيلات Laravel.

**ترتيب الترحيل (آمن من ناحية التبعيات):**
1. `create_users_table` (الافتراضي + إضافة `role`، `preferred_lang`)
2. `create_authors_table`
3. `create_publishers_table`
4. `create_categories_table` (إضافة `parent_id` المرجعي الذاتي)
5. `create_books_table` (مفاتيح أجنبية للمؤلفين والناشرين والتصنيفات)
6. `create_book_platform_links_table`
7. `create_book_bisac_codes_table`
8. `create_book_keywords_table`
9. `create_reading_lists_table`
10. `create_reading_list_book_table` (جدول الوسيط)
11. `create_blog_posts_table`
12. `create_contact_submissions_table`

**إعداد نماذج Eloquent:**
- تعريف جميع `$fillable`، `$hidden`، وطرق العلاقات وفق المخطط أعلاه.
- تطبيق فهرس FULLTEXT على `books.title`، `books.synopsis`، `books.search_vector` للبحث النصي الكامل السريع.

---

### المرحلة 3 — ترحيل البيانات (البذر) (الأسبوع الثالث)

**الهدف:** استيراد جميع 2,825 كتاب من `books.json` الحالي إلى MySQL.

**الاستراتيجية:** كتابة أمر Artisan مخصص (`php artisan books:import /path/to/books.json`) يقوم بـ:
1. قراءة `books.json` وتحليله.
2. لكل كتاب، استيعاب `authors` و`publishers` و`categories` (ازالة التكرار عبر `firstOrCreate`).
3. إدراج سجل `books`.
4. إدراج جماعي لـ `book_platform_links`، `book_bisac_codes`، `book_keywords`.
5. بناء حقل `search_vector` مسبقاً بتسلسل جميع الحقول القابلة للبحث.

**الأداء:** استخدام `DB::transaction()` و`Book::insertOrIgnore()` للإدراج المُجمَّع. يُتوقع اكتمال الاستيراد الكامل في أقل من دقيقتين.

---

### المرحلة 4 — وحدات تحكم الـ API ومساراته (الأسبوع 3-4)

**الهدف:** بناء جميع نقاط نهاية الـ API العامة والمصادق عليها.

**الهيكل:**
```
app/Http/Controllers/API/
├── AuthController.php
├── BookController.php
├── CategoryController.php
├── AuthorController.php
├── BlogController.php
├── ContactController.php
└── Admin/
    ├── AdminBookController.php
    ├── AdminCategoryController.php
    ├── AdminBlogController.php
    ├── AdminUserController.php
    └── AdminStatsController.php
```

**تفاصيل التنفيذ الرئيسية:**

- **التصفية:** استخدام `spatie/laravel-query-builder` لتطبيق جميع معاملات `GET /books` بنظافة بدون SQL خام.
- **البحث النصي الكامل:** استخدام MySQL `MATCH...AGAINST` على أعمدة FULLTEXT للـ `search_vector`، `title`، `synopsis`، متوافقاً مع منطق حقل `_search` الحالي.
- **الترقيم:** استخدام `paginate(24)` في Laravel — شكل الاستجابة يعكس كتلة `meta` الموضحة في القسم 4.2.
- **ترتيب الأولوية:** نسخ منطق الواجهة الأمامية الحالي (الروايات العربية أولاً) كجملة `CASE WHEN` في `orderByRaw` لاستعلام Eloquent.
- **الموارد:** استخدام موارد API في Laravel (`BookResource`، `BookDetailResource`) لتحويل نماذج Eloquent إلى شكل JSON الذي تستهلكه الواجهة الأمامية حالياً.

---

### المرحلة 5 — المصادقة والتفويض (الأسبوع الرابع)

**الهدف:** تطبيق مصادقة آمنة قائمة على الرمز المميز.

1. **تثبيت Sanctum** وتشغيل `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`.
2. **التسجيل/الدخول:** مصادقة بريد + كلمة مرور قياسية مع تشفير bcrypt.
3. **إصدار الرمز:** `$user->createToken('api-token')->plainTextToken`.
4. **البرمجيات الوسيطة:** حماية مسارات الإدارة بـ `['auth:sanctum', 'role:admin']` عبر `spatie/laravel-permission`.
5. **تحديد المعدل:** تطبيق `throttle:60,1` على المسارات العامة و`throttle:10,1` على مسارات المصادقة.

---

### المرحلة 6 — لوحة الإدارة (Filament) (الأسبوع الخامس)

**الهدف:** تزويد المسؤولين غير التقنيين بواجهة CRUD كاملة دون كتابة واجهة أمامية مخصصة.

يُنشئ Filament تلقائياً لوحة إدارة غنية على `/admin`. الضبط:
- **BookResource:** جدول يحتوي ISBN، العنوان، المؤلف، اللغة، زر تبديل النشر. نموذج بجميع حقول الكتاب، رفع الغلاف، ومكرر روابط المنصات.
- **CategoryResource:** عرض شجري للتصنيفات الأصل/الفرع.
- **BlogPostResource:** محرر نص غني (Quill/TipTap) لمحتوى المدونة.
- **UserResource:** قائمة المستخدمين، تعيين الأدوار.
- **ودجات لوحة التحكم:** بطاقات KPI لإجمالي الكتب والمستخدمين المسجلين ورسائل الاتصال.

---

### المرحلة 7 — دمج الواجهة الأمامية (الأسبوع السادس)

**الهدف:** استبدال جلب JSON الثابت (12.6 ميغابايت) باستدعاءات API في JavaScript الحالي.

**التغييرات على ملفات الواجهة الأمامية الحالية:**

**`js/script.js` — كتالوج الصفحة الرئيسية:**
- استبدال `fetch("/data/books.json")` بـ `fetch("/api/v1/books?page=1&per_page=24")`.
- نقل منطق التصفية كله (`applyFilters()`) من JS جانب العميل إلى معاملات الاستعلام من جانب الخادم. كل تغيير في الفلتر يُشغّل جلب API جديد بدلاً من تصفية مصفوفة في الذاكرة.
- الترقيم يستخدم الآن `meta.last_page` من استجابة الـ API.
- خيارات القوائم المنسدلة لـ `author`، `language`، `category` تُجلب مرة واحدة من `/api/v1/authors`، `/api/v1/languages`، `/api/v1/categories`.

**`js/book.js` — صفحة تفاصيل الكتاب:**
- استبدال `fetch("/data/books.json")` (تحميل 12.6 ميغابايت كامل) بـ `fetch("/api/v1/books/{isbn}")` (جلب كتاب واحد).

**`js/global.js` — لا تغييرات مطلوبة.**

**تأثير الأداء:**
- التحميل الأولي للصفحة الرئيسية: **12.6 ميغابايت → ~15 كيلوبايت** (صفحة واحدة من 24 كتاباً).
- صفحة تفاصيل الكتاب: **12.6 ميغابايت → ~3 كيلوبايت** (سجل كتاب واحد).

---

### المرحلة 8 — التصيير من جانب الخادم لتحسين SEO (الأسبوع 6-7)

**الهدف:** معالجة فجوة SSR — صفحات `/book/ISBN` الديناميكية ليس لها حالياً محتوى مُصيَّر من الخادم.

**النهج:** إضافة وسيط PHP في `book.php` يخدمه Laravel (أو نقطة نهاية PHP منفصلة). عند استقبال الخادم `GET /book/9781259455636`، يقوم بـ:
1. استعلام الكتاب من MySQL.
2. حقن بيانات وصفية للكتاب في `<head>` HTML من جانب الخادم (العنوان، الوصف، og:image، JSON-LD).
3. إرجاع HTML المُشكَّل كاملاً لبرنامج الزحف.

يستلزم هذا تحديث `.htaccess` لتوجيه `/book/{isbn}` إلى ملف PHP بدلاً من `book.html`.

---

### المرحلة 9 — النشر على cPanel (الأسبوع السابع)

**الهدف:** نشر Laravel API على Namecheap cPanel.

**الخطوات:**
1. إنشاء نطاق فرعي `api.almashreqebookstore.com` في cPanel → توجيه جذر المستند إلى `public/`.
2. رفع جميع ملفات Laravel عبر FTP أو cPanel File Manager (أو Git Deploy إن توفر).
3. تشغيل `composer install --optimize-autoloader --no-dev` عبر cPanel Terminal.
4. تشغيل `php artisan migrate --force` عبر cPanel Terminal.
5. تشغيل `php artisan books:import` لبذر قاعدة البيانات الإنتاجية.
6. تعيين صلاحيات الملفات الصحيحة: `storage/` و`bootstrap/cache/` → `755`.
7. إضافة متغيرات البيئة في cPanel أو ملف `.env`.
8. تشغيل `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
9. تحديث ملفات JS في الواجهة الأمامية للإشارة إلى `https://api.almashreqebookstore.com/api/v1/...`.
10. تحديث `config/cors.php` للسماح لـ `https://almashreqebookstore.com` فقط.

---

### المرحلة 10 — الاختبار والمراقبة (الأسبوع الثامن)

**الهدف:** التحقق من صحة الإنتاج وأدائه.

- **اختبارات الوحدة:** اختبارات PHPUnit لجميع وحدات تحكم الـ API (مقدمة من سقالة اختبار Laravel).
- **اختبارات الميزات:** اختبار دورات الطلب/الاستجابة الكاملة لقائمة الكتب، التصفية، المصادقة، ومسارات الإدارة.
- **اختبار الحمل:** استخدام Apache Bench أو k6 للتأكد من أن `/api/v1/books` يتعامل مع الطلبات المتزامنة ضمن حدود cPanel المشترك.
- **المراقبة:** تهيئة مراقب UptimeRobot مجاني على `/api/v1/health`. إعداد تنبيهات سجل أخطاء cPanel.

---

## 6. هيكل الأمان

| المخاوف | الحلول |
|---|---|
| **حقن SQL** | يستخدم Eloquent ORM عبارات PDO المُعدَّة مسبقاً في كل مكان. لا SQL خام في المسارات المواجهة للمستخدم. |
| **XSS** | واجهة Laravel API تُرجع JSON فقط؛ لا تصيير HTML من مدخلات المستخدم. |
| **CSRF** | يستخدم Sanctum مصادقة قائمة على الرمز لـ SPA. حماية CSRF غير قابلة للتطبيق على APIs عديمة الحالة. |
| **التعيين الجماعي** | جميع النماذج تُعرّف مصفوفات `$fillable` صريحة. |
| **تحديد المعدل** | برمجية `throttle` في Laravel على جميع المسارات. مسارات المصادقة: 10 طلبات/دقيقة. العامة: 60 طلبات/دقيقة. |
| **التفويض** | `spatie/laravel-permission` يُطبّق بوابات أدوار `admin`/`editor`/`customer` على جميع مسارات الإدارة. |
| **رفع الملفات** | صور الأغلفة مُتحقق منها لنوع MIME (`image/jpeg`، `image/png`، `image/webp`)، بحد أقصى 5 ميغابايت، مخزنة بمسار غير قابل للتخمين. |
| **أسرار البيئة** | جميع بيانات الاعتماد في `.env`، لا في التحكم بالإصدار أبداً. `.env` مستثنى في `.htaccess`. |
| **حظر تنفيذ PHP** | `.htaccess` الحالي يحظر بالفعل تنفيذ PHP في المجلدات غير التابعة للـ API. النطاق الفرعي للـ API منفصل. |

---

## 7. الملحق: عقد بيانات الواجهة الأمامية الحالية

الحقول التالية تستهلكها الواجهة الأمامية الحالية ويجب الحفاظ عليها في استجابات الـ API للتوافق مع الإصدارات السابقة خلال فترة الانتقال.

### كائن الكتاب (من `books.json`)

```json
{
  "Title": "نص",
  "ISBN": "نص (13 رقماً)",
  "SubTitle": "نص | فارغ",
  "Series": "نص | فارغ",
  "Author": "نص",
  "Contributor": "نص",
  "Contributor Role": "نص",
  "Publisher": "نص",
  "Language": "نص",
  "BISAC1": "نص",
  "BISAC2": "نص",
  "Keywords": "نص مفصول بفواصل",
  "Synopsis": "نص",
  "Pages": "نص (رقمي)",
  "Age": "نص | فارغ",
  "Category": "نص (تنسيق رئيسي / فرعي)",
  "CategoryMain": "نص",
  "Subject_2": "نص",
  "Link": "نص (URL)",
  "Links": [{ "name": "نص", "url": "نص" }],
  "_id": "رقم صحيح (يبدأ من 0)",
  "_search": "نص (كتلة بحث صغيرة مبنية مسبقاً)"
}
```

### معاملات الفلتر المستخدمة في الواجهة الأمامية

| فلتر الواجهة الأمامية | معامل الـ API المقابل |
|---|---|
| `q` (بحث نصي كامل) | `?q=` |
| القائمة المنسدلة `mainCat` | `?category=` |
| القائمة المنسدلة `subject2` | `?subcategory=` |
| القائمة المنسدلة `author` | `?author=` |
| القائمة المنسدلة `lang` | `?language=` |
| `sortBy` (az/za/author/lang) | `?sort=` |
| `viewBy` (author/category/language/alpha) | `?view_by=` (تلميح تجميع من جانب الخادم) |
| `PAGE_SIZE = 24` | `?per_page=24` |

### قاعدة عرض الأولوية

تُرتب الواجهة الأمامية الروايات العربية في الصدارة. يجب تكرار ذلك في ترتيب الـ API الافتراضي:

```sql
ORDER BY
  CASE
    WHEN (books.language IN ('Arabic','العربية','عربي') 
          AND categories.name IN ('Fiction','روايات','Arabic Fiction'))
    THEN 0
    ELSE 1
  END ASC,
  authors.name ASC,
  books.title ASC
```

---

*نهاية التقرير*
