# 🔍 Comprehensive Code Audit Report
## Al-Mashreq eBookstore — Laravel 11 Backend + Vanilla JS Frontend
**Audit Date:** 2026-10-21 | **Auditor:** Senior Laravel/Filament Backend + Vanilla JS Security Review

---

> [!CAUTION]
> **5 Critical / 7 High / 8 Medium / 6 Low** severity issues found. Read all CRITICAL items immediately — two of them can result in a full server compromise or admin panel bypass.

---

## Table of Contents
1. [🔴 CRITICAL — Security Vulnerabilities](#1--critical--security-vulnerabilities)
2. [🟠 HIGH — Security Issues](#2--high--security-issues)
3. [🟡 MEDIUM — Performance & N+1 Queries](#3--medium--performance--n1-queries)
4. [🔵 LOW — Logic, Edge Cases & Best Practices](#4--low--logic-edge-cases--best-practices)
5. [✅ What Is Done Right](#5--what-is-done-right)
6. [📋 Fix Priority Checklist](#6--fix-priority-checklist)

---

## 1. 🔴 CRITICAL — Security Vulnerabilities

---

### CRIT-01 · **Zip Slip (Directory Traversal) — Unvalidated ZIP Extraction**
**File:** [`ListBooks.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Filament/Resources/BookResource/Pages/ListBooks.php#L143)
**Line:** 143

**The Problem:**
```php
// ❌ VULNERABLE — No path validation before extraction
$zip->extractTo($tempExtractDir);
$zip->close();
```
`ZipArchive::extractTo()` does **not** sanitize ZIP entry names. An attacker can craft a ZIP file containing entries with path-traversal sequences like `../../etc/passwd` or `../../public/index.php`. When extracted, these will be written **outside** `$tempExtractDir`, potentially overwriting arbitrary files on the server including the Laravel `index.php`, `.env`, or even a PHP webshell.

**Proof of Concept Attack:**
A crafted ZIP containing `../../public/evil.php` with PHP code will:
1. Extract to `storage/app/public/evil.php`
2. Be publicly accessible at `https://yourserver.com/storage/evil.php`
3. Grant the attacker Remote Code Execution (RCE).

**The Fix — Validate every entry name BEFORE extraction:**
```php
// ✅ SAFE — Validate all entries before extracting anything
$zip = new ZipArchive();
$openResult = $zip->open($zipPath);
if ($openResult !== true) { /* ... error handling ... */ }

$tempExtractDir = storage_path('app/temp-extract-' . uniqid());
$realTempDir = realpath(dirname($tempExtractDir)) . DIRECTORY_SEPARATOR . basename($tempExtractDir);
if (!is_dir($realTempDir)) {
    mkdir($realTempDir, 0755, true);
}

// --- SECURITY: Validate all entries before extracting ---
for ($i = 0; $i < $zip->numFiles; $i++) {
    $entryName = $zip->getNameIndex($i);
    // Resolve what the actual destination path would be
    $destPath = realpath($realTempDir) . DIRECTORY_SEPARATOR . $entryName;
    // Check that the destination stays inside the temp dir
    if (strpos($destPath, realpath($realTempDir) . DIRECTORY_SEPARATOR) !== 0) {
        $zip->close();
        @unlink($zipPath);
        Notification::make()->danger()
            ->title('ملف ZIP مشبوه')
            ->body('رُفض الملف: يحتوي على مسارات مشبوهة (Zip Slip Attack).')
            ->send();
        return;
    }
}

$zip->extractTo($realTempDir);
$zip->close();
@unlink($zipPath);
```

---

### CRIT-02 · **CORS Wildcard `'*'` with `supports_credentials: true` — Complete CORS Bypass**
**File:** [`cors.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/config/cors.php#L30)
**Line:** 30

**The Problem:**
```php
// ❌ CRITICAL — These two together are a security disaster
'allowed_origins' => [
    // ... specific domains ...
    '*',   // LINE 30 — This wildcards everything
],
'supports_credentials' => true,  // LINE 43
```
The W3C CORS specification **explicitly forbids** using `*` as an allowed origin when `credentials: true`. Browsers ignore this and refuse the request, BUT the Laravel CORS library (`fruitcake/laravel-cors`) may process the `*` origin **before** checking credentials, sending `Access-Control-Allow-Origin: *`. This effectively strips authentication from every cross-origin API call AND is a classic credential-leaking CORS misconfiguration. Any malicious website can now make authenticated API calls using the visitor's Sanctum cookie-session.

**The Fix:**
```php
// ✅ CORRECT cors.php
'allowed_origins' => [
    'http://127.0.0.1:5500',
    'http://localhost:5500',
    'http://127.0.0.1:8000',
    'http://localhost:8000',
    'http://127.0.0.1:5173',
    'http://localhost:5173',
    'https://almashreqebookstore.com',
    'http://almashreqebookstore.com',
    // ❌ REMOVED: '*'  <-- DELETE THIS LINE
],
'allowed_origins_patterns' => [
    '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
],
'supports_credentials' => true,
```

---

### CRIT-03 · **Ghost Role `'admin'` Creates an Undocumented Access Bypass**
**File:** [`User.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Models/User.php#L78)
**Lines:** 78, 118, 142

**The Problem:**
```php
// Line 78 — canAccessPanel grants access to role 'admin' (not in ROLES constant)
public function canAccessPanel(Panel $panel): bool
{
    return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_EDITOR, 'admin'], true);
}

// Line 118 — isSuperAdmin() also allows 'admin'
public function isSuperAdmin(): bool
{
    return in_array($this->role, [self::ROLE_SUPER_ADMIN, 'admin'], true);
}

// Line 142 — isRegularUser() allows role 'customer'
public function isRegularUser(): bool
{
    return in_array($this->role, [self::ROLE_USER, 'customer'], true);
}
```
The `ROLES` constant on line 62-66 declares only `['super_admin', 'editor', 'user']`, but `canAccessPanel` and `isSuperAdmin` silently allow `'admin'` too. If any legacy record or migration seeds a user with `role = 'admin'`, they gain **full super admin privileges** silently, without appearing in the `UserResource`'s role filter for `'super_admin'`. The `'customer'` role in `isRegularUser` has the same ghost-role problem.

**The Fix — Be explicit and exhaustive:**
```php
// ✅ User.php — Remove all ghost roles
public const ROLES = [
    self::ROLE_SUPER_ADMIN,
    self::ROLE_EDITOR,
    self::ROLE_USER,
];

public function canAccessPanel(Panel $panel): bool
{
    // No ghost roles. Period.
    return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_EDITOR], true);
}

public function isSuperAdmin(): bool
{
    return $this->role === self::ROLE_SUPER_ADMIN;
}

public function isAdmin(): bool
{
    return $this->isSuperAdmin();
}

public function isRegularUser(): bool
{
    return $this->role === self::ROLE_USER;
}
```

---

### CRIT-04 · **Sanctum Token Expiration is Disabled — Tokens Live Forever**
**File:** [`sanctum.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/config/sanctum.php#L53)
**Line:** 53

**The Problem:**
```php
'expiration' => null,  // ❌ Tokens never expire
```
With `expiration => null`, any issued Sanctum token is valid **indefinitely**. If a user's token is ever stolen (e.g., via XSS, phishing, breach of the client's `localStorage`), the attacker has **permanent API access**. There is also no token rotation mechanism.

**The Fix:**
```php
// ✅ sanctum.php — Set a reasonable expiration
// 10080 minutes = 7 days. Adjust to your security policy.
'expiration' => 10080,
```
Also add a scheduled pruning job to clean up expired tokens:
```php
// routes/console.php
use Laravel\Sanctum\PersonalAccessToken;

Schedule::call(function () {
    PersonalAccessToken::where('created_at', '<', now()->subDays(30))->delete();
})->daily();
```

---

### CRIT-05 · **`.env` in Production: `APP_DEBUG=true` and Empty DB Password**
**File:** [`.env`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/.env#L4)
**Lines:** 4, 26

**The Problem:**
```dotenv
APP_DEBUG=true    # Line 4 — ❌ Shows full stack traces to the public
DB_PASSWORD=      # Line 26 — ❌ Root MySQL with no password
```
With `APP_DEBUG=true` in production:
- Full stack traces with file paths, environment variables, and DB queries are shown to any visitor who triggers a 500 error.
- Laravel's Ignition debug page exposes `.env` values in the "Environment" tab.
- **No password on the root DB user** means any process on the host can connect to MySQL and dump all data.

**The Fix:**
```dotenv
# ✅ .env for PRODUCTION
APP_ENV=production
APP_DEBUG=false
DB_USERNAME=almashreq_user  # ← dedicated least-privilege user
DB_PASSWORD=SomeStr0ngPassword!  # ← set a real password
SESSION_ENCRYPT=true  # ← also enable session encryption
```

---

## 2. 🟠 HIGH — Security Issues

---

### HIGH-01 · **Auth Token Stored in `localStorage` — XSS Token Theft Risk**
**File:** [`global.js`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/js/global.js#L401)
**Lines:** 401, 417, 434

**The Problem:**
```javascript
// global.js line 417 — ❌ Token readable by ANY script on the page
function getAuthToken() {
    return localStorage.getItem('sanctum_token') || '';
}
```
`localStorage` is accessible by **any JavaScript** on the page. A single successful XSS attack (in a blog post's `content` field rendered as HTML, a third-party script, or a browser extension) can read `localStorage.getItem('sanctum_token')` and exfiltrate the token to an attacker server.

**The Recommended Fix:**
For a Vanilla JS frontend, the recommended pattern is **HttpOnly cookie authentication** (Sanctum SPA mode). Since a refactor may be expensive, the minimum mitigation is to move to `sessionStorage` (tab-scoped, slightly less persistent) and implement strict CSP headers:

```javascript
// global.js — Replace localStorage with sessionStorage for token
const AUTH_TOKEN_KEY = 'sanctum_token';
const AUTH_USER_KEY = 'auth_user';

function getAuthToken() {
    try {
        // sessionStorage: cleared on tab close, not accessible cross-tab
        return sessionStorage.getItem(AUTH_TOKEN_KEY) || '';
    } catch (_) { return ''; }
}

function setAuthSession(token, user) {
    try {
        if (token) sessionStorage.setItem(AUTH_TOKEN_KEY, token);
        if (user) sessionStorage.setItem(AUTH_USER_KEY, JSON.stringify(user));
    } catch (e) { console.warn('[Auth] Storage write failed:', e); }
    updateNavbarAuthState();
    window.dispatchEvent(new CustomEvent('authstatechange', { detail: { isLoggedIn: Boolean(token), user } }));
}

function clearAuthSession() {
    try {
        sessionStorage.removeItem(AUTH_TOKEN_KEY);
        sessionStorage.removeItem(AUTH_USER_KEY);
    } catch (_) {}
    // ...
}
```
And add in your Laravel response headers (middleware or `public/.htaccess`):
```
Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none';
```

---

### HIGH-02 · **`/api/register` Has No Rate Limiting — Brute Force & Account Flooding**
**File:** [`api.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/routes/api.php#L25)
**Lines:** 25–26

**The Problem:**
```php
// ❌ No throttle middleware on auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
```
There is no rate limiting on the login or registration endpoints. An attacker can:
1. Brute-force passwords on any existing account (login endpoint).
2. Create thousands of dummy accounts (register endpoint), flooding the `users` table.

**The Fix:**
```php
// ✅ api.php — Add throttle middleware
Route::prefix('v1')->group(function () {

    // Auth: 10 attempts per minute per IP
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login',    [AuthController::class, 'login'])->name('api.v1.auth.login');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user',    [AuthController::class, 'user'])->middleware('auth:sanctum');

    // ... book/author/etc routes with a generous rate limit
    Route::middleware('throttle:120,1')->group(function () {
        Route::get('/books',          [BookController::class, 'index']);
        Route::get('/books/{isbn}',   [BookController::class, 'show']);
        // ... etc
    });
});
```

---

### HIGH-03 · **`BlogPostResource` Role Check Bug — `isSuperAdmin() ?? false` Fallacy**
**File:** [`BlogPostResource.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Filament/Resources/BlogPostResource.php#L51)
**Line:** 51

**The Problem:**
```php
public static function canViewAny(): bool
{
    $user = auth()->user();
    // ❌ isSuperAdmin() returns bool — it can NEVER be null.
    // The '?? false' is dead code that masks intent.
    return $user && ($user->isSuperAdmin() ?? false);
}
```
Beyond the dead `?? false` (since `isSuperAdmin()` always returns `bool`), the bigger issue is that **Editors cannot access the Blog Post resource at all**. The comment in `AdminPanelProvider` implies editors are content managers, yet they cannot create or edit blog posts. This is almost certainly a bug that forces editors to bug the super admin for every post.

**The Fix:**
```php
// ✅ BlogPostResource.php — Allow editors to manage blog posts
public static function canViewAny(): bool
{
    $user = auth()->user();
    // Super admins and editors can manage blog content
    return $user instanceof \App\Models\User 
        && in_array($user->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_EDITOR], true);
}

public static function shouldRegisterNavigation(): bool
{
    return static::canViewAny();
}
```

---

### HIGH-04 · **`UserResource` Bulk Delete Has No Self-Deletion Guard**
**File:** [`UserResource.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Filament/Resources/UserResource.php#L173)
**Lines:** 173–177

**The Problem:**
```php
// ❌ BulkActionGroup has no filter to exclude the authenticated user's own record
->bulkActions([
    Tables\Actions\BulkActionGroup::make([
        Tables\Actions\DeleteBulkAction::make(),
    ]),
])
```
The individual row `DeleteAction` is guarded (`visible: auth()->id() !== $record->id`), but the `DeleteBulkAction` has **no such guard**. A super admin can select-all and bulk delete, which will include their own account, locking themselves out of the panel.

**The Fix:**
```php
// ✅ UserResource.php — Guard the bulk delete action
->bulkActions([
    Tables\Actions\BulkActionGroup::make([
        Tables\Actions\DeleteBulkAction::make()
            ->before(function (Tables\Actions\DeleteBulkAction $action, \Illuminate\Database\Eloquent\Collection $records) {
                // Remove the currently authenticated user from the selection
                $currentUserId = auth()->id();
                if ($records->contains('id', $currentUserId)) {
                    Notification::make()
                        ->warning()
                        ->title('تحذير')
                        ->body('تم استبعاد حسابك الشخصي من قائمة الحذف.')
                        ->send();
                    // Desaturate the collection to exclude self
                    $action->records($records->reject(fn ($r) => $r->id === $currentUserId));
                }
            }),
    ]),
])
```

---

### HIGH-05 · **Unrestricted File Type in Excel Import (MIME Sniffing)**
**File:** [`ListBooks.php`](file:///c:/Users/Aio/Downloads/Final V6 (2)/FinalV6/backend/app/Filament/Resources/BookResource/Pages/ListBooks.php#L38)
**Lines:** 38–46

**The Problem:**
```php
// ❌ MIME types are set in the frontend form, but never validated server-side
FileUpload::make('excel_file')
    ->acceptedFileTypes([
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/csv',
        'text/plain',  // ← 'text/plain' is extremely broad
    ])
```
The `acceptedFileTypes` is a **browser-side HTML attribute** only. It can be bypassed trivially with any HTTP client (Postman, curl). The server then calls `BookExcelImportService::import()` on the file without any server-side MIME or extension validation. A crafted file with a valid `.xlsx` extension but with a PHP payload could potentially be parsed in unexpected ways.

**The Fix — Add server-side validation in the action:**
```php
// ✅ ListBooks.php action — validate file server-side
->action(function (array $data, BookExcelImportService $service): void {
    $relativePath = $data['excel_file'] ?? null;
    if (is_array($relativePath)) {
        $relativePath = array_values($relativePath)[0] ?? null;
    }
    if (!$relativePath) return;

    $fullPath = Storage::disk('local')->path($relativePath);
    if (!file_exists($fullPath)) {
        Notification::make()->danger()->title('خطأ')->body('الملف غير موجود.')->send();
        return;
    }

    // ✅ SERVER-SIDE: Validate extension
    $allowedExtensions = ['xlsx', 'xls', 'csv', 'txt'];
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        @unlink($fullPath);
        Notification::make()->danger()->title('نوع الملف غير مدعوم')
            ->body('يُسمح فقط بملفات xlsx و xls و csv.')->send();
        return;
    }

    $result = $service->import($fullPath);
    @unlink($fullPath);
    // ... rest of logic
})
```

---

### HIGH-06 · **`web.php` Publicly Redirects `/` to `/admin` — Information Disclosure**
**File:** [`web.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/routes/web.php#L14)
**Line:** 14

**The Problem:**
```php
// ❌ Publicly announces admin panel location to every bot/attacker
Route::redirect('/', '/admin');
```
Every request to the root URL gets a `302` redirect to `/admin`, broadcasting the admin panel's existence and location to every web crawler, security scanner, and bot. This enables **targeted admin panel attacks** and increases attack surface awareness.

**The Fix:**
```php
// ✅ web.php — Return a proper response from root, not an admin redirect
Route::get('/', function () {
    return response()->json(['status' => 'ok', 'message' => 'Al-Mashreq eBookstore API'], 200);
});
```

---

### HIGH-07 · **`preferred_lang` is Accepted Unvalidated from Registration**
**File:** [`AuthController.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Http/Controllers/API/AuthController.php#L42)
**Line:** 42

**The Problem:**
```php
// ❌ preferred_lang is accepted without validation
$user = User::create([
    // ...
    'preferred_lang' => $request->input('preferred_lang', 'ar'),
]);
```
`preferred_lang` is pulled directly from the request without validation. An attacker could submit any arbitrary string (e.g., `../../../../etc/passwd`, a 10KB string, or SQL fragments). While the `role` field is correctly hardcoded to `'user'`, this field is user-controlled.

**The Fix:**
```php
// ✅ AuthController.php — Add preferred_lang to validation
$validated = $request->validate([
    'name'       => ['required', 'string', 'max:255'],
    'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
    'password'   => ['required', 'string', 'min:8', 'confirmed'],
    // ✅ Add this line:
    'preferred_lang' => ['nullable', 'string', 'in:ar,en'],
]);

$user = User::create([
    'name'           => trim($validated['name']),
    'email'          => strtolower(trim($validated['email'])),
    'password'       => Hash::make($validated['password']),
    'role'           => 'user',
    'preferred_lang' => $validated['preferred_lang'] ?? 'ar',
]);
```

---

## 3. 🟡 MEDIUM — Performance & N+1 Queries

---

### PERF-01 · **`StatsOverviewWidget` Fires 6 Separate COUNT Queries on Every Dashboard Load**
**File:** [`StatsOverviewWidget.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Filament/Widgets/StatsOverviewWidget.php#L23)
**Lines:** 25–30

**The Problem:**
```php
// ❌ 6 separate synchronous COUNT(*) queries hit the DB on every admin dashboard load
$totalBooks      = Book::count();
$publishedBooks  = Book::published()->count();
$totalAuthors    = Author::count();
$totalCategories = Category::count();
$publishedPosts  = BlogPost::published()->count();
$totalPages      = Page::published()->count();
```
Every time any admin opens the dashboard, 6 queries run. With a large dataset, this is noticeable.

**The Fix — Cache the stats with a short TTL:**
```php
// ✅ StatsOverviewWidget.php
use Illuminate\Support\Facades\Cache;

protected function getStats(): array
{
    // Cache the counts for 5 minutes (300 seconds)
    $stats = Cache::remember('dashboard_stats', 300, function () {
        return [
            'total_books'       => Book::count(),
            'published_books'   => Book::published()->count(),
            'total_authors'     => Author::count(),
            'total_categories'  => Category::count(),
            'published_posts'   => BlogPost::published()->count(),
            'total_pages'       => Page::published()->count(),
        ];
    });

    return [
        Stat::make('إجمالي الكتب', number_format($stats['total_books']))
            ->description("{$stats['published_books']} كتاب معروض للزوار")
            // ...
    ];
}
```

---

### PERF-02 · **`BookResource::getEloquentQuery()` Eagerly Loads Only `author` & `category` — Missing `platformLinks`**
**File:** [`BookResource.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Filament/Resources/BookResource.php#L526)
**Lines:** 526–530

**The Problem:**
```php
// In the table columns:
TextColumn::make('platform_links_count')->counts('platformLinks')  // Line 457

// In getEloquentQuery:
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['author', 'category']);  // ❌ Missing 'platformLinks' for the count column
}
```
The `counts('platformLinks')` table column causes a `COUNT(*)` subquery for every row when not preloaded. With 2,800 books per page, this is 2,800 extra queries.

**The Fix:**
```php
// ✅ BookResource.php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['author', 'category'])
        ->withCount('platformLinks');  // ← adds a single JOIN-based count
}
```

---

### PERF-03 · **`AuthorController::show()` Loads ALL Books for an Author with No Pagination**
**File:** [`AuthorController.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Http/Controllers/API/AuthorController.php#L41)
**Line:** 41–42

**The Problem:**
```php
$author = Author::with([
    // ❌ Returns ALL published books in one query — no limit
    'books' => fn ($q) => $q->published()->orderBy('title', 'asc'),
])->...->first();
```
An author with 500 books returns all 500 in one API response. This creates a huge JSON payload.

**The Fix — Paginate or limit books on the author detail endpoint:**
```php
// ✅ AuthorController.php
public function show(string $identifier): JsonResponse
{
    $author = Author::withCount(['books' => fn ($q) => $q->published()])
        ->where(function ($q) use ($identifier) {
            $q->where('slug', $identifier);
            if (is_numeric($identifier)) {
                $q->orWhere('id', (int) $identifier);
            }
        })->first();

    if (!$author) {
        return response()->json(['success' => false, 'message' => 'المؤلف غير موجود.'], 404);
    }

    // Separately paginate books
    $books = $author->books()->published()->orderBy('title', 'asc')->paginate(24);

    return response()->json([
        'success' => true,
        'data'    => $author,
        'books'   => $books->items(),
        'meta'    => ['total' => $books->total(), 'last_page' => $books->lastPage()],
    ]);
}
```

---

### PERF-04 · **`CategoryController::show()` Loads ALL Books for a Category**
**File:** [`CategoryController.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Http/Controllers/API/CategoryController.php#L39)
**Line:** 39–41 *(same problem as PERF-03)*

```php
// ❌ All books for a category in one response — no limit
'books' => fn ($q) => $q->published()->orderBy('title', 'asc'),
```
Same fix pattern: paginate or limit book results to 24 and let the frontend paginate.

---

### PERF-05 · **`BookController::index()` Can Return Unlimited Rows**
**File:** [`BookController.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Http/Controllers/API/BookController.php#L114)
**Lines:** 114–149

**The Problem:**
```php
$allRequested = $request->boolean('all') || $request->input('per_page') === 'all';
// ...
if ($hasPagination) { /* paginate */ }

// ❌ Default path: dumps ALL books with all their relations in one response
$books = $query->get();  // Line 139
```
When called without `?page=` or `?per_page=`, the endpoint dumps the **entire book catalog** (potentially thousands of books × 7 eager-loaded relations). The frontend caches this in `sessionStorage`, which sounds reasonable, but the initial load is catastrophic for memory and bandwidth.

**The Fix — Make pagination the default, require explicit `?all=true` opt-in:**
```php
// ✅ BookController.php
public function index(Request $request): JsonResponse
{
    // ... (filters and sorting stay the same)

    // Safety cap: never serve more than 500 books at once without pagination
    $allRequested = $request->boolean('all');
    $hasPagination = $request->has('page') || ($request->has('per_page') && !$allRequested);

    if ($allRequested) {
        $books = $query->limit(500)->get();  // ← Hard cap
        $transformed = $books->map(fn (Book $b) => $this->transformBook($b));
        return response()->json([
            'success' => true,
            'count'   => $books->count(),
            'data'    => $transformed,
            'books'   => $transformed,
        ]);
    }

    // Default to pagination
    $perPage = max(1, min((int) $request->input('per_page', 24), 100));
    $paginator = $query->paginate($perPage);
    // ... rest unchanged
}
```

---

### PERF-06 · **`BookExcelImportService` Has N+1 Keyword Inserts Inside a Loop**
**File:** [`BookExcelImportService.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Services/BookExcelImportService.php#L256)
**Lines:** 256–263

**The Problem:**
```php
// ❌ For each book, for each keyword: separate INSERT query
$keywords = preg_split('/[,;\n]+/', $rawKw);
foreach ($keywords as $kw) {
    $kw = trim($kw);
    if ($kw !== '') {
        $book->keywords()->firstOrCreate(['keyword' => Str::limit($kw, 250)]); // ← N queries
    }
}
```
For 100 books with 5 keywords each = 500 individual `INSERT OR SELECT` queries just for keywords. This is in addition to similar patterns for `platformLinks`.

**The Fix — Batch with `upsert`:**
```php
// ✅ BookExcelImportService.php — Batch keyword inserts
if (!empty($rowData['keywords'])) {
    $rawKw = (string) $rowData['keywords'];
    if (!str_contains(strtolower($rawKw), 'text in arabic')) {
        $keywords = array_filter(
            array_map('trim', preg_split('/[,;\n]+/', $rawKw)),
            fn ($kw) => $kw !== ''
        );
        if (!empty($keywords)) {
            $keywordRows = array_map(
                fn ($kw) => ['book_id' => $book->id, 'keyword' => Str::limit($kw, 250)],
                $keywords
            );
            // Single upsert for all keywords of this book
            \App\Models\BookKeyword::upsert(
                $keywordRows,
                ['book_id', 'keyword'],  // unique keys
                []                       // no columns to update on conflict
            );
        }
    }
}
```

---

## 4. 🔵 LOW — Logic, Edge Cases & Best Practices

---

### LOW-01 · **Login Does Not Use `ValidationException` — Timing Attack Possible**
**File:** [`AuthController.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Http/Controllers/API/AuthController.php#L81)
**Lines:** 81–88

**The Problem:**
```php
// ❌ Manually checks and returns 401, leaking that the email exists
$user = User::where('email', strtolower(trim($validated['email'])))->first();
if (!$user || !Hash::check($validated['password'], $user->password)) {
    return response()->json(['success' => false, 'message' => '...'], 401);
}
```
The current implementation uses a raw `User::where(...)->first()` query, which returns `null` instantly if the email doesn't exist, but takes bcrypt's full compute time if the email exists (to run `Hash::check`). This timing difference can be used to enumerate valid email addresses.

**The Fix — Use Laravel's `Auth::attempt()` which handles timing internally:**
```php
// ✅ AuthController.php
use Illuminate\Support\Facades\Auth;

public function login(Request $request): JsonResponse
{
    $validated = $request->validate([
        'email'    => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ]);

    // Auth::attempt() handles timing-safe comparison
    if (!Auth::attempt(['email' => strtolower(trim($validated['email'])), 'password' => $validated['password']])) {
        throw ValidationException::withMessages([
            'email' => ['بيانات الاعتماد غير صحيحة.'],
        ]);
    }

    $user  = Auth::user();
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'success'      => true,
        'token'        => $token,
        'access_token' => $token,
        'token_type'   => 'Bearer',
        'user'         => [...],
    ]);
}
```

---

### LOW-02 · **Frontend Auth `verifyAuthSession()` Has Silent Failure — 403/500 Errors Ignored**
**File:** [`global.js`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/js/global.js#L471)
**Lines:** 471–496

**The Problem:**
```javascript
async function verifyAuthSession() {
    // ...
    if (res.status === 401) {
        clearAuthSession();  // ✅ Handles 401
    } else if (res.ok) {
        // ✅ Handles 200
    }
    // ❌ What about 403, 429, 500? Session is kept with stale data.
    // Also: no timeout. A hanging request blocks nothing but wastes resources.
}
```
If the `/api/v1/user` endpoint returns `403` (forbidden), `429` (rate limited), or `500` (server error), the function silently does nothing. The user appears logged in with potentially stale data. Also, there is no `AbortController` timeout, so a slow network could leave the `fetch` promise pending indefinitely.

**The Fix:**
```javascript
// ✅ global.js — More robust session verification
async function verifyAuthSession() {
    const token = getAuthToken();
    if (!token) return;

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 8000); // 8s timeout

    try {
        const res = await fetch(`${getApiBaseUrl()}/api/v1/user`, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        if (res.status === 401 || res.status === 403) {
            // Token is invalid or user lost permissions — force logout
            clearAuthSession();
        } else if (res.ok) {
            const data = await res.json();
            if (data?.user) {
                sessionStorage.setItem(AUTH_USER_KEY, JSON.stringify(data.user));
                updateNavbarAuthState();
            }
        }
        // 429/500: keep local session, try again next page load
    } catch (err) {
        clearTimeout(timeoutId);
        if (err.name !== 'AbortError') {
            // Network offline — keep local session silently
        }
    }
}
```

---

### LOW-03 · **`BookExcelImportService` — Invalid Date Silently Falls Back to `Carbon::parse()` With Risk**
**File:** [`BookExcelImportService.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Services/BookExcelImportService.php#L157)
**Lines:** 157–161

**The Problem:**
```php
try {
    // ❌ Carbon::parse() on arbitrary user input — can parse strings like 'next Friday'
    // or 'yesterday', producing unexpected future/past dates silently.
    $publicationDate = Carbon::parse($rawDate)->format('Y-m-d');
} catch (\Throwable) {
    $publicationDate = null;
}
```
`Carbon::parse()` is very permissive — it accepts strings like `"next year"`, `"tomorrow"`, `"July"` and produces valid dates. This means a spreadsheet row with `publication_date = "2025"` would produce `2025-10-21` (today's month/day).

**The Fix:**
```php
// ✅ Use a whitelist of known safe formats
$safeFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd-m-Y'];
$publicationDate = null;
foreach ($safeFormats as $fmt) {
    $parsed = \DateTime::createFromFormat($fmt, $rawDate);
    if ($parsed && $parsed->format($fmt) === $rawDate) {
        $publicationDate = $parsed->format('Y-m-d');
        break;
    }
}
```

---

### LOW-04 · **`BookResource` Filament Form — `cover_path` Can Accept Arbitrary URLs**
**File:** [`BookResource.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/app/Filament/Resources/BookResource.php#L297)
**Line:** 297

**The Problem:**
```php
FileUpload::make('cover_path')
    ->image()
    ->disk('public')
    ->directory('covers')
    // ...
```
The `BookController::transformBook()` method (line 201–207) checks if `cover_url` starts with `http` and if so, uses it as-is. An editor could set `cover_path` to `javascript:alert(1)` or an external URL. While Filament's `FileUpload` handles physical upload correctly, if the raw `cover_path` string is ever edited manually (via API or DB seeder), it could propagate an untrusted URL into the API.

**The Fix — Sanitize the cover URL in `getCoverUrlAttribute()`:**
```php
// ✅ Book.php — Validate cover URL scheme
public function getCoverUrlAttribute(): string
{
    $path = $this->cover_path ?: $this->cover;
    if (!empty($path)) {
        // Only allow safe URL schemes
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return $path;
        }
        return '/storage/' . ltrim($path, '/');
    }
    return "/storage/covers/{$this->isbn}.jpg";
}
```

---

### LOW-05 · **`setBtnLoading()` in `global.js` Uses `innerHTML` — Minor XSS Risk**
**File:** [`global.js`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/js/global.js#L717)
**Line:** 717

**The Problem:**
```javascript
// ❌ loadingText is set by developer, but good practice to avoid innerHTML with variables
btn.innerHTML = `<span class="auth-spinner"></span> <span>${loadingText || ''}</span>`;
```
While `loadingText` is developer-controlled here, the pattern of writing template literals into `innerHTML` is a bad practice that could become dangerous if `loadingText` ever comes from user data.

**The Fix:**
```javascript
// ✅ Use DOM methods or textContent to avoid innerHTML pitfalls
function setBtnLoading(btn, isLoading, loadingText) {
    if (!btn) return;
    btn.disabled = isLoading;
    if (isLoading) {
        btn.dataset.origHtml = btn.innerHTML;
        const spinner = document.createElement('span');
        spinner.className = 'auth-spinner';
        const text = document.createElement('span');
        text.textContent = loadingText || '';  // ✅ textContent is XSS-safe
        btn.innerHTML = '';
        btn.appendChild(spinner);
        btn.appendChild(document.createTextNode(' '));
        btn.appendChild(text);
    } else if (btn.dataset.origHtml) {
        btn.innerHTML = btn.dataset.origHtml;
        delete btn.dataset.origHtml;
    }
}
```

---

### LOW-06 · **Duplicate Route Registrations Waste Memory & Can Cause Route Name Conflicts**
**File:** [`api.php`](file:///c:/Users/Aio/Downloads/Final%20V6%20(2)/FinalV6/backend/routes/api.php#L51)
**Lines:** 51–76

**The Problem:**
```php
/* =========================================================================
 | Root API Aliases (/api/...)     ← Lines 54–76
 | ========================================================================= */
// ❌ Every route is registered TWICE — once under /api/v1/ and again under /api/
Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
Route::post('/login',    [AuthController::class, 'login'])->name('api.auth.login');
// ... (13 more duplicate routes)
```
Every route exists as `/api/v1/...` and `/api/...` simultaneously. This doubles the routing table size, means throttle middleware applied to v1 routes doesn't apply to root aliases, and creates surface area for inconsistencies.

**The Fix — Use route group prefixes with fallback aliases, not full duplicates:**
```php
// ✅ api.php — Clean, single registration with v1 group
Route::prefix('v1')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login',    [AuthController::class, 'login'])->name('api.v1.auth.login');
    });
    // ... all routes once, under v1
});

// One simple redirect for root aliases (no middleware skip risk)
Route::redirect('/register', '/api/v1/register');
Route::redirect('/login',    '/api/v1/login');
// OR just remove root aliases and update the frontend to use /api/v1/
```

---

## 5. ✅ What Is Done Right

These are genuinely well-implemented — worth noting:

| ✅ | Area | Detail |
|---|------|--------|
| ✅ | **SQL Injection** | Zero raw queries found. Eloquent ORM is used consistently with bound parameters everywhere. |
| ✅ | **Filament RBAC — UserResource** | `canViewAny`, `canCreate`, `canEdit`, `canDelete` are all gated to `isSuperAdmin()`. Self-delete prevention is in place for individual rows. |
| ✅ | **UserPolicy** | `UserPolicy` mirrors `UserResource` access controls at the model layer — correct defense in depth. |
| ✅ | **Password Hashing** | `'password' => 'hashed'` cast in User model means Eloquent auto-hashes on assignment. `BCRYPT_ROUNDS=12` is a good setting. |
| ✅ | **Role Assignment at Registration** | `role` is hardcoded to `'user'` in `AuthController::register()` — users cannot self-promote. |
| ✅ | **Token Revocation on Logout** | `currentAccessToken()->delete()` properly revokes the specific token, not all user tokens. |
| ✅ | **Filament Session Middleware Stack** | `AdminPanelProvider` has `EncryptCookies`, `VerifyCsrfToken`, `AuthenticateSession` — complete and correct. |
| ✅ | **XSS in API Response** | No `{!! raw !!}` Blade output. JSON responses are auto-escaped by Laravel. |
| ✅ | **Zip Cleanup** | Temp ZIP and extraction directories are cleaned up after processing. |
| ✅ | **`per_page` Cap** | `BookController` caps `per_page` at 100: `max(1, min((int) $request->input('per_page', 24), 100))`. |
| ✅ | **401 Handling in JS** | `verifyAuthSession()` correctly clears the session on `401` responses. |
| ✅ | **Sanctum Stateful Domains** | `.env` correctly sets `SANCTUM_STATEFUL_DOMAINS` to the production domain only. |

---

## 6. 📋 Fix Priority Checklist

| Priority | ID | File | Action |
|----------|-----|------|--------|
| 🔴 P0 | CRIT-01 | `ListBooks.php` | Add ZIP entry path validation before `extractTo()` |
| 🔴 P0 | CRIT-02 | `cors.php` | Remove `'*'` from `allowed_origins` |
| 🔴 P0 | CRIT-03 | `User.php` | Remove ghost roles `'admin'` and `'customer'` |
| 🔴 P0 | CRIT-04 | `sanctum.php` | Set `expiration` to a finite value (e.g., 10080) |
| 🔴 P0 | CRIT-05 | `.env` | Set `APP_DEBUG=false`, add DB password in production |
| 🟠 P1 | HIGH-01 | `global.js` | Move auth token to `sessionStorage` + add CSP header |
| 🟠 P1 | HIGH-02 | `api.php` | Add `throttle:10,1` middleware to auth routes |
| 🟠 P1 | HIGH-03 | `BlogPostResource.php` | Fix role check to include editors |
| 🟠 P1 | HIGH-04 | `UserResource.php` | Guard bulk delete from self-deletion |
| 🟠 P1 | HIGH-05 | `ListBooks.php` | Add server-side file extension validation |
| 🟠 P1 | HIGH-06 | `web.php` | Replace admin redirect with a neutral API response |
| 🟠 P1 | HIGH-07 | `AuthController.php` | Validate `preferred_lang` with `in:ar,en` rule |
| 🟡 P2 | PERF-01 | `StatsOverviewWidget.php` | Cache stats with `Cache::remember()` |
| 🟡 P2 | PERF-02 | `BookResource.php` | Add `withCount('platformLinks')` to `getEloquentQuery()` |
| 🟡 P2 | PERF-03 | `AuthorController.php` | Paginate books in author `show()` |
| 🟡 P2 | PERF-04 | `CategoryController.php` | Paginate books in category `show()` |
| 🟡 P2 | PERF-05 | `BookController.php` | Hard-cap `?all=true` responses at 500 records |
| 🟡 P2 | PERF-06 | `BookExcelImportService.php` | Batch keyword inserts with `upsert()` |
| 🔵 P3 | LOW-01 | `AuthController.php` | Use `Auth::attempt()` for timing-safe login |
| 🔵 P3 | LOW-02 | `global.js` | Add `AbortController` timeout + handle 403 in `verifyAuthSession` |
| 🔵 P3 | LOW-03 | `BookExcelImportService.php` | Replace `Carbon::parse()` with strict format whitelist |
| 🔵 P3 | LOW-04 | `Book.php` | Sanitize cover URL scheme in accessor |
| 🔵 P3 | LOW-05 | `global.js` | Replace `innerHTML` in `setBtnLoading()` with DOM methods |
| 🔵 P3 | LOW-06 | `api.php` | Remove duplicate root-level route aliases |
