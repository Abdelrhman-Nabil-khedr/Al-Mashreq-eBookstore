# Technical Backend Proposal & System Design Report
## Al-Mashreq eBookstore — Static-to-Dynamic Conversion

**Prepared For:** Al-Mashreq eBookstore Development Team  
**Date:** October 2026  
**Current System:** Static HTML/CSS/JS + Flat JSON (2,825 books, ~12.6 MB)  
**Hosting:** Namecheap Shared cPanel (Apache, PHP 8.x, MySQL, Node.js via Passenger/CloudLinux)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Framework Recommendation: PHP (Laravel) vs Node.js (NestJS/Express)](#2-framework-recommendation)
3. [Database Schema & Models (ERD)](#3-database-schema--models)
4. [RESTful API Endpoints Architecture](#4-restful-api-endpoints-architecture)
5. [Step-by-Step Execution Roadmap](#5-step-by-step-execution-roadmap)
6. [Security Architecture](#6-security-architecture)
7. [Appendix: Existing Frontend Data Contract](#7-appendix-existing-frontend-data-contract)

---

## 1. Executive Summary

The current system loads a single monolithic **12.6 MB `books.json`** on every page visit. While functional, this architecture has reached its scalability ceiling:

- **Performance:** The browser must parse the full dataset (~2,825 books) even when only 24 are displayed per page.
- **Admin Bottleneck:** Adding a new book requires manually editing a JSON file and re-uploading it.
- **No Authentication:** No user accounts, order tracking, or personalization is possible.
- **No Commerce:** Purchase links are static external redirects with no internal transaction capability.
- **SEO Inefficiency:** Dynamic pages like `/book/ISBN` are rendered client-side with no server-side content.

The proposed backend will replace this with a proper **API-first architecture**, serving paginated, filtered, and sorted data server-side, enabling admin CRUD operations, user authentication, and a platform ready for e-commerce or marketplace features.

---

## 2. Framework Recommendation

### 2.1 Candidate Analysis

#### Option A — Node.js (NestJS or Express)

| Factor | Assessment |
|---|---|
| **cPanel Compatibility** | ⚠️ Requires Node.js via Passenger/CloudLinux. Namecheap supports this, but setup is brittle: process manager, `.htaccess` proxy, persistent process issues on restarts. |
| **Cold Start Reliability** | ⚠️ Node processes on shared hosting frequently get killed by RAM limits and must be manually restarted via cPanel. |
| **Deployment Complexity** | ❌ High. Requires SSH access, npm install on server, Passenger config files, and a proxy `.htaccess`. |
| **Database ORM** | ✅ Excellent (Prisma, TypeORM, Sequelize). |
| **Performance** | ✅ Superior async I/O, ideal for high-concurrency APIs. |
| **Arabic/RTL I18n** | ✅ Native JS string handling handles Arabic perfectly. |
| **Shared Hosting Verdict** | ❌ **Not recommended.** The operational overhead and instability on shared cPanel outweigh the performance gains at this scale. |

#### Option B — PHP (Laravel)

| Factor | Assessment |
|---|---|
| **cPanel Compatibility** | ✅ **Native first-class citizen.** PHP runs directly via Apache mod_php. Zero process configuration needed. |
| **Deployment** | ✅ Upload files via FTP/Git. No daemon, no Passenger, no port binding. It just works. |
| **Stability** | ✅ PHP is stateless; each request is independent. No process crashes, no manual restarts. |
| **Database ORM** | ✅ Eloquent ORM is mature, expressive, and extremely well-documented. |
| **API Framework** | ✅ Laravel is a battle-tested, full-stack framework with built-in Auth (Sanctum/Passport), queues, cache, and REST scaffolding. |
| **Arabic/UTF-8 Support** | ✅ Full UTF-8 support. Laravel's `Str` helpers handle Arabic correctly. |
| **Admin Panel** | ✅ Filament (Laravel ecosystem) provides a production-ready Admin UI in hours, not days. |
| **Shared Hosting Verdict** | ✅ **Strongly recommended.** Laravel is the industry-standard choice for cPanel/shared hosting environments. |

### 2.2 Final Recommendation

> **Use Laravel 11 (PHP 8.2+) with MySQL.**

**Rationale:** On Namecheap shared cPanel, PHP is a zero-configuration runtime. Laravel's architecture maps perfectly to this project's requirements: a clean RESTful API for the existing frontend, a Filament-powered admin panel, Sanctum-based authentication, and Eloquent ORM for the MySQL database. The entire backend can be deployed without SSH access, process managers, or complex server configuration. At the current scale of ~2,825 books, Laravel's performance is more than sufficient, and its ecosystem provides every feature this roadmap requires.

---

## 3. Database Schema & Models

### 3.1 Entity Relationship Overview

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
blog_posts (standalone)
contact_submissions (standalone)
```

---

### 3.2 Model Definitions

#### `users`
The central authentication model.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(255) | NOT NULL | Display name |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Login identifier |
| `password` | VARCHAR(255) | NOT NULL | Hashed (bcrypt) |
| `role` | ENUM | DEFAULT 'customer' | `admin`, `editor`, `customer` |
| `preferred_lang` | ENUM | DEFAULT 'ar' | `ar`, `en` |
| `email_verified_at` | TIMESTAMP | NULLABLE | Verification timestamp |
| `remember_token` | VARCHAR(100) | NULLABLE | |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

**Relationships:**
- `hasMany(Order)`
- `hasMany(ReadingList)`

---

#### `authors`
Normalized author entity extracted from the flat JSON.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `name` | VARCHAR(500) | NOT NULL, INDEX | Author display name |
| `slug` | VARCHAR(500) | UNIQUE, NOT NULL | URL-safe identifier |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Relationships:**
- `hasMany(Book)`

---

#### `publishers`
Normalized publisher entity.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `name` | VARCHAR(500) | NOT NULL, INDEX | |
| `slug` | VARCHAR(500) | UNIQUE, NOT NULL | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Relationships:**
- `hasMany(Book)`

---

#### `categories`
Two-level category hierarchy matching `CategoryMain` → `Subject_2`.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `parent_id` | BIGINT UNSIGNED | NULLABLE, FK → categories.id | NULL = top-level |
| `name` | VARCHAR(255) | NOT NULL | e.g., "Nonfiction", "Fiction" |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Relationships:**
- `belongsTo(Category, 'parent_id')` (self-referential)
- `hasMany(Category)` (children)
- `hasMany(Book)`

---

#### `books`
The core catalog model. All denormalized JSON fields are normalized here.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `isbn` | VARCHAR(20) | UNIQUE, NOT NULL, INDEX | Primary search key |
| `title` | VARCHAR(1000) | NOT NULL, FULLTEXT INDEX | |
| `subtitle` | VARCHAR(1000) | NULLABLE | |
| `series` | VARCHAR(500) | NULLABLE | |
| `synopsis` | LONGTEXT | NULLABLE, FULLTEXT INDEX | |
| `pages` | SMALLINT UNSIGNED | NULLABLE | |
| `language` | VARCHAR(50) | NOT NULL, INDEX | e.g., "Arabic", "English" |
| `age_range` | VARCHAR(50) | NULLABLE | e.g., "Kids", "YA" |
| `cover_path` | VARCHAR(500) | NULLABLE | `/covers/{isbn}.jpg` |
| `search_vector` | LONGTEXT | NULLABLE | Pre-built search string (mirrors `_search`) |
| `is_featured` | TINYINT | DEFAULT 0 | Admin-controllable homepage feature flag |
| `is_published` | TINYINT | DEFAULT 1 | Soft-toggle for catalog visibility |
| `author_id` | BIGINT UNSIGNED | FK → authors.id, INDEX | |
| `publisher_id` | BIGINT UNSIGNED | FK → publishers.id, INDEX | |
| `category_id` | BIGINT UNSIGNED | FK → categories.id, INDEX | Main category |
| `sub_category_id` | BIGINT UNSIGNED | FK → categories.id, NULLABLE | Subject_2 |
| `contributor_name` | VARCHAR(500) | NULLABLE | |
| `contributor_role` | VARCHAR(100) | NULLABLE | e.g., "Editor", "Translator" |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Relationships:**
- `belongsTo(Author)`
- `belongsTo(Publisher)`
- `belongsTo(Category)` (main category)
- `belongsTo(Category, 'sub_category_id')` (sub-category)
- `hasMany(BookPlatformLink)`
- `hasMany(BookBisacCode)`
- `hasMany(BookKeyword)`
- `belongsToMany(ReadingList)` (via `reading_list_book` pivot)

---

#### `book_platform_links`
Replaces the `Links[]` array in the current JSON.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `book_id` | BIGINT UNSIGNED | FK → books.id, INDEX | |
| `platform_name` | VARCHAR(100) | NOT NULL | e.g., "Jarir", "OverDrive", "Amazon" |
| `url` | TEXT | NOT NULL | External purchase/read URL |
| `sort_order` | TINYINT | DEFAULT 0 | Display priority |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Relationships:**
- `belongsTo(Book)`

---

#### `book_bisac_codes`
Stores one or more BISAC codes per book.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `book_id` | BIGINT UNSIGNED FK | |
| `code` | VARCHAR(20) | e.g., "BUS023000" |

---

#### `book_keywords`
Normalized keywords for full-text search indexing.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `book_id` | BIGINT UNSIGNED FK | |
| `keyword` | VARCHAR(255) | Single keyword, indexed |

---

#### `reading_lists` *(Future Phase)*
User-saved book collections (wishlist / reading list).

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `user_id` | BIGINT UNSIGNED | FK → users.id | |
| `name` | VARCHAR(255) | NOT NULL | e.g., "My Wishlist" |
| `is_public` | TINYINT | DEFAULT 0 | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Pivot Table:** `reading_list_book (reading_list_id, book_id)`

---

#### `orders` *(Future Phase)*
Order header for marketplace purchases.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `user_id` | BIGINT UNSIGNED | FK → users.id | NULLABLE for guest |
| `status` | ENUM | NOT NULL | `pending`, `completed`, `failed`, `refunded` |
| `total_amount` | DECIMAL(10,2) | NOT NULL | |
| `currency` | CHAR(3) | DEFAULT 'USD' | |
| `payment_gateway` | VARCHAR(50) | NULLABLE | e.g., "Stripe", "PayPal" |
| `gateway_ref` | VARCHAR(255) | NULLABLE | External transaction ID |
| `customer_email` | VARCHAR(255) | NOT NULL | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

---

#### `order_items` *(Future Phase)*
Line items within an order.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `order_id` | BIGINT UNSIGNED FK | |
| `book_id` | BIGINT UNSIGNED FK | |
| `price_at_purchase` | DECIMAL(10,2) | Snapshot of price at time of sale |
| `platform_link_id` | BIGINT UNSIGNED FK NULLABLE | Which platform was used |

---

#### `blog_posts`
Mirrors the current `blog.json` structure.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `author_id` | BIGINT UNSIGNED | FK → users.id, NULLABLE | Admin author |
| `title` | VARCHAR(1000) | NOT NULL | |
| `slug` | VARCHAR(1000) | UNIQUE, NOT NULL | |
| `excerpt` | TEXT | NULLABLE | |
| `content` | LONGTEXT | NOT NULL | |
| `published_at` | TIMESTAMP | NULLABLE | NULL = draft |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

---

#### `contact_submissions`
Captures messages from the contact form.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(255) | |
| `email` | VARCHAR(255) | |
| `subject` | VARCHAR(500) | |
| `message` | TEXT | |
| `ip_address` | VARCHAR(45) | For rate-limiting |
| `created_at` | TIMESTAMP | |

---

### 3.3 ERD Summary

```
authors ──┐
          ├──→ books ←── categories (parent/child self-referential)
publishers─┘      │
                  ├──→ book_platform_links
                  ├──→ book_bisac_codes
                  ├──→ book_keywords
                  └──→ reading_list_book ←── reading_lists ←── users
                                                                  │
                                                               orders ──→ order_items
blog_posts ←── users
contact_submissions (standalone)
```

---

## 4. RESTful API Endpoints Architecture

All endpoints are prefixed with `/api/v1`. Responses use JSON. Authentication uses **Laravel Sanctum** (stateless token-based for the SPA frontend).

### 4.1 Authentication Routes (`/api/v1/auth`)

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/auth/register` | Public | Register new user (email + password) |
| `POST` | `/auth/login` | Public | Login → returns Bearer token |
| `POST` | `/auth/logout` | 🔐 Bearer | Invalidate current token |
| `GET` | `/auth/me` | 🔐 Bearer | Return authenticated user profile |
| `POST` | `/auth/forgot-password` | Public | Send password reset email |
| `POST` | `/auth/reset-password` | Public | Confirm reset with token + new password |
| `PATCH` | `/auth/profile` | 🔐 Bearer | Update name, language, password |

---

### 4.2 Books Catalog Routes (`/api/v1/books`)

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/books` | Public | Paginated book list with filters |
| `GET` | `/books/{isbn}` | Public | Single book detail (all fields + links) |
| `GET` | `/books/featured` | Public | Featured/hero books for homepage |
| `GET` | `/books/latest` | Public | Most recently added books |
| `GET` | `/books/search` | Public | Full-text search (`?q=...`) |

**`GET /books` Query Parameters:**

| Param | Type | Example | Description |
|---|---|---|---|
| `q` | string | `?q=أدب` | Full-text search (title, synopsis, keywords) |
| `category` | string | `?category=fiction` | Filter by main category slug |
| `subcategory` | string | `?subcategory=business-economics` | Filter by sub-category slug |
| `author` | string | `?author=adam-smith` | Filter by author slug |
| `language` | string | `?language=Arabic` | Filter by language |
| `sort` | string | `?sort=title_asc` | `title_asc`, `title_desc`, `author_asc`, `language_asc` |
| `view_by` | string | `?view_by=author` | `author`, `category`, `language`, `alpha` |
| `page` | int | `?page=2` | Pagination page number |
| `per_page` | int | `?per_page=24` | Results per page (max: 100) |

**Example Response Shape (`GET /books`):**
```json
{
  "data": [
    {
      "isbn": "9781259455636",
      "title": "Analytic Summary of The Wealth of Nations",
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

### 4.3 Category & Taxonomy Routes (`/api/v1/categories`)

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/categories` | Public | All top-level categories with book counts |
| `GET` | `/categories/{slug}` | Public | Category detail + sub-categories |
| `GET` | `/categories/{slug}/books` | Public | Books within a category (paginated) |
| `GET` | `/authors` | Public | All authors with book counts |
| `GET` | `/authors/{slug}/books` | Public | Books by a specific author (paginated) |
| `GET` | `/languages` | Public | Distinct languages with book counts |

---

### 4.4 Blog Routes (`/api/v1/blog`)

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/blog` | Public | All published posts (paginated) |
| `GET` | `/blog/{slug}` | Public | Single post detail |

---

### 4.5 User Account Routes (`/api/v1/user`)

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `GET` | `/user/reading-lists` | 🔐 Bearer | All reading lists for authenticated user |
| `POST` | `/user/reading-lists` | 🔐 Bearer | Create a new reading list |
| `POST` | `/user/reading-lists/{id}/books` | 🔐 Bearer | Add book (by ISBN) to a list |
| `DELETE` | `/user/reading-lists/{id}/books/{isbn}` | 🔐 Bearer | Remove book from a list |
| `GET` | `/user/orders` | 🔐 Bearer | Order history for current user |
| `GET` | `/user/orders/{id}` | 🔐 Bearer | Single order detail |

---

### 4.6 Admin Dashboard Routes (`/api/v1/admin`)

> **All admin routes require:** `🔐 Bearer + role: admin`

#### Book Management

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/admin/books` | All books (including unpublished), full admin view |
| `POST` | `/admin/books` | Create a new book record |
| `GET` | `/admin/books/{isbn}` | Single book admin detail |
| `PUT` | `/admin/books/{isbn}` | Full update of a book |
| `PATCH` | `/admin/books/{isbn}` | Partial update (e.g., toggle `is_published`) |
| `DELETE` | `/admin/books/{isbn}` | Soft-delete a book |
| `POST` | `/admin/books/import` | Bulk import from JSON (migration tool) |
| `POST` | `/admin/books/{isbn}/cover` | Upload cover image for a book |

#### Category Management

| Method | Endpoint | Description |
|---|---|---|
| `GET/POST` | `/admin/categories` | List all / Create new |
| `PUT/DELETE` | `/admin/categories/{id}` | Update / Delete |

#### Blog Management

| Method | Endpoint | Description |
|---|---|---|
| `GET/POST` | `/admin/blog` | List / Create post |
| `PUT/DELETE` | `/admin/blog/{id}` | Update / Delete post |
| `PATCH` | `/admin/blog/{id}/publish` | Toggle published state |

#### User Management

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/admin/users` | All users (paginated) |
| `PATCH` | `/admin/users/{id}/role` | Change user role |
| `DELETE` | `/admin/users/{id}` | Deactivate a user |

#### Analytics & Reports

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/admin/stats` | Dashboard KPIs: total books, users, orders, top categories |
| `GET` | `/admin/contact-submissions` | View contact form messages |

---

### 4.7 Public Utility Routes

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/contact` | Submit contact form |
| `GET` | `/sitemap.json` | Machine-readable sitemap of all book ISBNs (for SSG) |
| `GET` | `/health` | API health check (for monitoring) |

---

## 5. Step-by-Step Execution Roadmap

### Phase 0 — Environment Setup (Week 1)

**Objective:** Establish the local development environment and cPanel infrastructure.

**Steps:**
1. **Local:** Install PHP 8.2, Composer, MySQL (via XAMPP/Herd/Valet), and Laravel CLI.
2. **cPanel:** Confirm PHP version ≥ 8.2 (via MultiPHP Manager). Create a dedicated MySQL database and user with full privileges.
3. **Subdomain or Directory:** Configure a subdomain (`api.almashreqebookstore.com`) in cPanel pointing to the `public/` directory of your Laravel installation. This keeps the API completely separate from the frontend's root.
4. **SSL:** Confirm AutoSSL covers the subdomain.
5. **`.htaccess` for Laravel:** Ensure cPanel's document root points to `public/` so the built-in `public/.htaccess` handles routing correctly.

---

### Phase 1 — Laravel Project Scaffolding (Week 1-2)

**Objective:** Create the base Laravel project with all required packages.

**Commands:**
```bash
composer create-project laravel/laravel almashreq-api
cd almashreq-api
composer require laravel/sanctum                 # Auth
composer require spatie/laravel-query-builder    # Advanced API filtering
composer require spatie/laravel-permission       # Role-based access control
composer require filament/filament               # Admin panel UI
composer require league/flysystem-local          # File storage for covers
```

**Configuration:**
- `config/database.php`: Configure MySQL connection.
- `config/cors.php`: Allow the frontend domain (`almashreqebookstore.com`) for cross-origin API requests.
- `config/sanctum.php`: Configure stateful domains if using cookie-based auth.
- `.env`: Set `APP_URL`, `DB_*`, `FILESYSTEM_DISK=local`.

---

### Phase 2 — Database Migrations & Models (Week 2-3)

**Objective:** Define the full database schema via Laravel migrations.

**Migration Order (dependency-safe):**
1. `create_users_table` (Laravel default + add `role`, `preferred_lang`)
2. `create_authors_table`
3. `create_publishers_table`
4. `create_categories_table` (add `parent_id` self-reference)
5. `create_books_table` (FKs to authors, publishers, categories)
6. `create_book_platform_links_table`
7. `create_book_bisac_codes_table`
8. `create_book_keywords_table`
9. `create_reading_lists_table`
10. `create_reading_list_book_table` (pivot)
11. `create_blog_posts_table`
12. `create_contact_submissions_table`

**Eloquent Model Setup:**
- Define all `$fillable`, `$hidden`, and relationship methods per the schema above.
- Apply FULLTEXT index on `books.title`, `books.synopsis`, `books.search_vector` for fast full-text search.

---

### Phase 3 — Data Migration (Seeder) (Week 3)

**Objective:** Import all 2,825 books from the existing `books.json` into MySQL.

**Strategy:** Write a custom Artisan command (`php artisan books:import /path/to/books.json`) that:
1. Reads and parses `books.json`.
2. For each book, upserts `authors`, `publishers`, and `categories` (deduplicates via `firstOrCreate`).
3. Inserts `books` record.
4. Bulk-inserts `book_platform_links`, `book_bisac_codes`, `book_keywords`.
5. Pre-builds the `search_vector` field by concatenating all searchable fields.

**Performance:** Use `DB::transaction()` and `Book::insertOrIgnore()` for batched inserts. Expect full import in under 2 minutes.

---

### Phase 4 — API Controllers & Routes (Week 3-4)

**Objective:** Build all public and authenticated API endpoints.

**Structure:**
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

**Key Implementation Details:**

- **Filtering:** Use `spatie/laravel-query-builder` to implement all `GET /books` query parameters cleanly without raw SQL.
- **Full-Text Search:** Use MySQL `MATCH...AGAINST` on the `search_vector`, `title`, and `synopsis` FULLTEXT columns, matching the existing `_search` field logic.
- **Pagination:** Use Laravel's `paginate(24)` — response shape mirrors the `meta` block shown in Section 4.2.
- **Priority Sorting:** Replicate the existing frontend logic (Arabic Fiction first) as a `CASE WHEN` clause in the Eloquent query's `orderByRaw`.
- **Resources:** Use Laravel API Resources (`BookResource`, `BookDetailResource`) to transform Eloquent models into the exact JSON shape the frontend currently consumes.

---

### Phase 5 — Authentication & Authorization (Week 4)

**Objective:** Implement secure, token-based auth.

1. **Install Sanctum** and run `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`.
2. **Register/Login:** Standard email + password auth with bcrypt hashing.
3. **Token issuance:** `$user->createToken('api-token')->plainTextToken`.
4. **Middleware:** Protect admin routes with `['auth:sanctum', 'role:admin']` via `spatie/laravel-permission`.
5. **Rate Limiting:** Apply `throttle:60,1` on public routes and `throttle:10,1` on auth routes.

---

### Phase 6 — Admin Panel (Filament) (Week 5)

**Objective:** Give non-technical admins a full CRUD interface without writing a custom frontend.

Filament auto-generates a rich admin panel at `/admin`. Configure:
- **BookResource:** Table with ISBN, title, author, language, published toggle. Form with all book fields, cover upload, and platform links repeater.
- **CategoryResource:** Tree view for parent/child categories.
- **BlogPostResource:** Rich text editor (Quill/TipTap) for blog content.
- **UserResource:** User list, role assignment.
- **Dashboard Widgets:** KPI cards for total books, registered users, and contact messages.

---

### Phase 7 — Frontend Integration (Week 6)

**Objective:** Replace the 12.6 MB static JSON fetch with API calls in the existing JS.

**Changes to existing frontend files:**

**`js/script.js` — Homepage Catalog:**
- Replace `fetch("/data/books.json")` with `fetch("/api/v1/books?page=1&per_page=24")`.
- Move all filtering logic (`applyFilters()`) from client-side JS to server-side query params. Each filter change triggers a new API fetch instead of filtering an in-memory array.
- Pagination now uses `meta.last_page` from the API response.
- Drop-down options for `author`, `language`, `category` are fetched once from `/api/v1/authors`, `/api/v1/languages`, `/api/v1/categories`.

**`js/book.js` — Book Detail Page:**
- Replace `fetch("/data/books.json")` (full 12.6 MB download) with `fetch("/api/v1/books/{isbn}")` (single book fetch).

**`js/global.js` — No changes required.**

**Performance Impact:**
- Homepage initial load: **12.6 MB → ~15 KB** (one page of 24 books).
- Book detail page: **12.6 MB → ~3 KB** (single book record).

---

### Phase 8 — Server-Side Rendering for SEO (Week 6-7)

**Objective:** Fix SSR gap — dynamic `/book/ISBN` pages currently have no server-rendered content.

**Approach:** Add a PHP shim at `book.php` that Laravel serves (or a separate PHP endpoint). When the server receives `GET /book/9781259455636`, it:
1. Queries the book from MySQL.
2. Injects book metadata into the HTML `<head>` server-side (title, description, og:image, JSON-LD).
3. Returns the fully-formed HTML to the crawler.

This requires updating `.htaccess` to route `/book/{isbn}` to a PHP file instead of `book.html`.

---

### Phase 9 — Deployment to cPanel (Week 7)

**Objective:** Deploy Laravel API to Namecheap cPanel.

**Steps:**
1. Create subdomain `api.almashreqebookstore.com` in cPanel → point document root to `public/`.
2. Upload all Laravel files via FTP or cPanel File Manager (or use Git Deploy if available).
3. Run `composer install --optimize-autoloader --no-dev` via cPanel Terminal.
4. Run `php artisan migrate --force` via cPanel Terminal.
5. Run `php artisan books:import` to seed production database.
6. Set correct file permissions: `storage/` and `bootstrap/cache/` → `755`.
7. Add environment variables in cPanel or `.env` file.
8. Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
9. Update frontend JS files to point to `https://api.almashreqebookstore.com/api/v1/...`.
10. Update `config/cors.php` to allow only `https://almashreqebookstore.com`.

---

### Phase 10 — Testing & Monitoring (Week 8)

**Objective:** Validate production correctness and performance.

- **Unit Tests:** PHPUnit tests for all API controllers (provided by Laravel's testing scaffolding).
- **Feature Tests:** Test full request/response cycles for book listing, filtering, auth, and admin routes.
- **Load Test:** Use Apache Bench or k6 to confirm `/api/v1/books` handles concurrent requests under cPanel's shared limits.
- **Monitoring:** Configure a free UptimeRobot monitor on `/api/v1/health`. Set up cPanel's error log alerting.

---

## 6. Security Architecture

| Concern | Mitigation |
|---|---|
| **SQL Injection** | Eloquent ORM uses PDO prepared statements throughout. No raw SQL in user-facing paths. |
| **XSS** | Laravel API returns JSON only; no HTML rendering from user input. |
| **CSRF** | Sanctum uses token-based auth for the SPA. CSRF protection is not applicable to stateless APIs. |
| **Mass Assignment** | All models define explicit `$fillable` arrays. |
| **Rate Limiting** | Laravel's `throttle` middleware on all routes. Auth routes: 10 req/min. Public: 60 req/min. |
| **Authorization** | `spatie/laravel-permission` enforces `admin`/`editor`/`customer` role gates on all admin routes. |
| **File Upload** | Cover images validated for MIME type (`image/jpeg`, `image/png`, `image/webp`), maximum 5 MB, stored with a non-guessable path. |
| **Environment Secrets** | All credentials in `.env`, never in version control. `.env` excluded in `.htaccess`. |
| **PHP Execution Block** | Existing `.htaccess` already blocks PHP execution in non-API directories. API subdomain is separate. |

---

## 7. Appendix: Existing Frontend Data Contract

The following fields are consumed by the current frontend and must be preserved in API responses for backward compatibility during the migration transition period.

### Book Object (from `books.json`)

```json
{
  "Title": "string",
  "ISBN": "string (13-digit)",
  "SubTitle": "string | empty",
  "Series": "string | empty",
  "Author": "string",
  "Contributor": "string",
  "Contributor Role": "string",
  "Publisher": "string",
  "Language": "string",
  "BISAC1": "string",
  "BISAC2": "string",
  "Keywords": "comma-separated string",
  "Synopsis": "string",
  "Pages": "string (numeric)",
  "Age": "string | empty",
  "Category": "string (Main / Sub format)",
  "CategoryMain": "string",
  "Subject_2": "string",
  "Link": "string (URL)",
  "Links": [{ "name": "string", "url": "string" }],
  "_id": "integer (0-indexed)",
  "_search": "string (pre-built lowercase searchable blob)"
}
```

### Filter Parameters Used by Frontend

| Frontend Filter | Mapped API Param |
|---|---|
| `q` (full-text search) | `?q=` |
| `mainCat` dropdown | `?category=` |
| `subject2` dropdown | `?subcategory=` |
| `author` dropdown | `?author=` |
| `lang` dropdown | `?language=` |
| `sortBy` (az/za/author/lang) | `?sort=` |
| `viewBy` (author/category/language/alpha) | `?view_by=` (server-side grouping hint) |
| `PAGE_SIZE = 24` | `?per_page=24` |

### Priority Display Rule

The frontend sorts Arabic Fiction books to the top. This must be replicated in the API's default sort order:

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

*End of Report*
