<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Shuchkin\SimpleXLS;

class BookExcelImportService
{
    /**
     * Import books from an Excel (.xls, .xlsx) or CSV file.
     *
     * @param string $filePath Absolute path to the uploaded file
     * @return array{total: int, created: int, updated: int, errors: array<string>}
     */
    public function import(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $headers = [];
        $headerRowNumber = null;
        $dataRowIndex = 0;
        $total = 0;
        $created = 0;
        $updated = 0;
        $errors = [];

        try {
            foreach ($this->iterateRows($filePath, $extension) as $rowNumber => $cells) {
                // 1. Detect headers if not yet found
                if (empty($headers)) {
                    $detected = [];
                    foreach ($cells as $index => $cellValue) {
                        $normalized = $this->normalizeHeaderName($cellValue);
                        if ($normalized) {
                            $detected[$index] = $normalized;
                        }
                    }

                    // Consider this row the header row if it contains isbn or title
                    if (in_array('isbn', $detected, true) || in_array('title', $detected, true)) {
                        $headers = $detected;
                        $headerRowNumber = $rowNumber;
                        continue;
                    }

                    // Otherwise skip leading empty or descriptive title rows
                    continue;
                }

                // 2. Track data row index after headers
                $dataRowIndex++;

                // Map row data using header indices safely
                $rowData = [];
                foreach ($headers as $index => $key) {
                    $val = $cells[$index] ?? null;
                    $rowData[$key] = is_string($val) ? trim($val) : $val;
                }

                // Skip template instruction/sample rows if present
                if ($this->isInstructionRow($cells, $rowData)) {
                    continue;
                }

                // Clean and normalize ISBN (handles scientific notation, floats, and DateTime instances safely)
                $rawIsbn = $rowData['isbn'] ?? null;
                if ($rawIsbn instanceof DateTimeInterface) {
                    $rawIsbn = '';
                } elseif (is_float($rawIsbn) || (is_string($rawIsbn) && preg_match('/^[0-9]+(\.[0-9]+)?e\+[0-9]+$/i', trim($rawIsbn)))) {
                    $rawIsbn = sprintf('%.0f', (float) $rawIsbn);
                } elseif (is_numeric($rawIsbn) && is_string($rawIsbn) && str_ends_with($rawIsbn, '.0')) {
                    $rawIsbn = substr($rawIsbn, 0, -2);
                } elseif (is_scalar($rawIsbn)) {
                    $rawIsbn = (string) $rawIsbn;
                } else {
                    $rawIsbn = '';
                }

                // Convert Arabic-Indic numerals in ISBN if present
                $rawIsbn = strtr($rawIsbn, [
                    '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                    '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
                ]);

                $isbn = strtoupper(preg_replace('/[^0-9X]/i', '', $rawIsbn));
                $isbn = Str::limit($isbn, 20, '');

                // Skip if ISBN is empty or contains instruction text
                if (
                    empty($isbn) ||
                    str_contains(strtolower($isbn), 'digit') ||
                    str_contains(strtolower($isbn), 'dash') ||
                    (str_contains($isbn, '13') && str_contains(strtolower($isbn), 'no'))
                ) {
                    continue;
                }

                $title = $this->safeString($rowData['title'] ?? null, 250);
                if (empty($title)) {
                    $title = 'كتاب ' . $isbn;
                }

                try {
                    $total++;

                    // 1. Author (Resolve dynamically with safe unique slug)
                    $authorId = null;
                    $authorName = $this->safeString($rowData['author'] ?? null, 250);
                    if (filled($authorName) && !str_contains(strtolower($authorName), 'registered author') && !str_contains(strtolower($authorName), 'text in')) {
                        $author = $this->resolveAuthor($authorName);
                        $authorId = $author->id;
                    }

                    // 2. Publisher (Resolve dynamically with safe unique slug)
                    $publisherId = null;
                    $pubName = $this->safeString($rowData['publisher'] ?? null, 250);
                    if (filled($pubName) && !str_contains(strtolower($pubName), 'text in arabic') && !str_contains(strtolower($pubName), 'text in')) {
                        $publisher = $this->resolvePublisher($pubName);
                        $publisherId = $publisher->id;
                    }

                    // 3. Category (فئة 1 or category or subject_1)
                    $categoryId = null;
                    $catName = $this->safeString($rowData['category'] ?? null, 250);
                    if (empty($catName) && !empty($rowData['subject_1'])) {
                        $catName = $this->safeString($rowData['subject_1'] ?? null, 250);
                    }
                    if (filled($catName) && !str_contains(strtolower($catName), 'text in arabic') && !str_contains(strtolower($catName), 'text in english') && !str_contains(strtolower($catName), 'text in')) {
                        $category = $this->resolveCategory($catName, null);
                        $categoryId = $category->id;
                    }

                    // 4. SubCategory (فئة 2 or subcategory or subject_2)
                    $subCategoryId = null;
                    $subCatName = $this->safeString($rowData['subcategory'] ?? null, 250);
                    if (empty($subCatName) && !empty($rowData['subject_2'])) {
                        $subCatName = $this->safeString($rowData['subject_2'] ?? null, 250);
                    }
                    if (filled($subCatName) && !str_contains(strtolower($subCatName), 'text in arabic') && !str_contains(strtolower($subCatName), 'text in english') && !str_contains(strtolower($subCatName), 'text in')) {
                        $subCategory = $this->resolveCategory($subCatName, $categoryId);
                        $subCategoryId = $subCategory->id;
                    }

                    // 5. Pages (Strict integer parsing, clamped to unsignedSmallInteger max of 65535)
                    $pages = $this->parseInteger($rowData['pages'] ?? null, 1, 65535);

                    // 6. Publication Date (DateTimeInterface, dd/mm/yyyy, or standard)
                    $publicationDate = $this->parseDate($rowData['publication_date'] ?? null);

                    // Check existing book by ISBN
                    $book = Book::where('isbn', $isbn)->first();
                    $isNew = false;

                    if (!$book) {
                        $isNew = true;
                        $book = new Book();
                        $book->isbn = $isbn;
                    }

                    // Core Attributes (VARCHAR clamped to 250 chars or DB limits, TEXT without limits)
                    $book->title = $title;
                    $book->subtitle = $this->safeString($rowData['subtitle'] ?? null, 250);
                    $book->series = $this->safeString($rowData['series'] ?? null, 250);
                    $book->series_position = $this->safeString($rowData['series_position'] ?? null, 50);
                    $book->synopsis = $this->cleanValue($rowData['synopsis'] ?? null); // TEXT column - no limit
                    $book->author_bio = $this->cleanValue($rowData['author_bio'] ?? null); // TEXT column - no limit
                    if ($pages !== null) {
                        $book->pages = $pages;
                    }

                    // Language
                    $langVal = $this->safeString($rowData['language'] ?? null, 50);
                    if (!empty($langVal) && !str_contains(strtolower($langVal), 'languages list')) {
                        $book->language = $langVal;
                    } elseif (empty($book->language)) {
                        $book->language = 'Arabic';
                    }

                    // Original Language
                    $origLangVal = $this->safeString($rowData['original_language'] ?? null, 100);
                    if ($origLangVal && !str_contains(strtolower($origLangVal), 'languages list')) {
                        $book->original_language = $origLangVal;
                    }

                    // Publication Date
                    if ($publicationDate !== null) {
                        $book->publication_date = $publicationDate;
                    }

                    // Format (eBook, Audio, etc.)
                    $formatVal = $this->safeString($rowData['format'] ?? null, 100);
                    if ($formatVal && !str_contains(strtolower($formatVal), 'list (')) {
                        $book->format = $formatVal;
                    }

                    // Age group / range
                    $ageVal = $this->safeString($rowData['age_range'] ?? null, 50);
                    if ($ageVal && !str_contains(strtolower($ageVal), 'numbers and dashes')) {
                        $book->age_range = $ageVal;
                    }

                    // Contributors: Illustrator, Translator, Editor
                    $book->illustrator = $this->safeString($rowData['illustrator'] ?? null, 250);
                    $book->translator = $this->safeString($rowData['translator'] ?? null, 250);
                    $book->editor = $this->safeString($rowData['editor'] ?? null, 250);

                    // Classification Codes
                    $book->bisac_code = $this->safeString($rowData['bisac_code'] ?? null, 100);
                    $book->wgs_code = $this->safeString($rowData['wgs_code'] ?? null, 100);
                    $book->thema_code = $this->safeString($rowData['thema_code'] ?? null, 100);
                    $book->catalog_code = $this->safeString($rowData['catalog_code'] ?? null, 100);

                    // Cover path if specified
                    $coverVal = $this->safeString($rowData['cover_path'] ?? null, 250);
                    if (!empty($coverVal)) {
                        $book->cover_path = $coverVal;
                        $book->cover = $coverVal;
                    }

                    // Generic contributor
                    $book->contributor_name = $this->safeString($rowData['contributor_name'] ?? null, 250);
                    $book->contributor_role = $this->safeString($rowData['contributor_role'] ?? null, 100);

                    // Foreign Keys
                    if ($authorId) $book->author_id = $authorId;
                    if ($publisherId) $book->publisher_id = $publisherId;
                    if ($categoryId) $book->category_id = $categoryId;
                    if ($subCategoryId) $book->sub_category_id = $subCategoryId;

                    if ($book->is_published === null) $book->is_published = true;
                    if ($book->is_featured === null) $book->is_featured = false;

                    // Populate full-text search vector
                    $book->search_vector = trim("{$book->title} {$book->subtitle} {$authorName} {$pubName} {$catName}");

                    $book->save();

                    // Save indexing keywords if present
                    if (!empty($rowData['keywords'])) {
                        $rawKw = is_scalar($rowData['keywords']) ? (string) $rowData['keywords'] : '';
                        if (!str_contains(strtolower($rawKw), 'text in arabic') && !str_contains(strtolower($rawKw), 'keywords list')) {
                            $keywords = preg_split('/[,;\n]+/', $rawKw);
                            foreach ($keywords as $kw) {
                                $kw = trim($kw);
                                if ($kw !== '') {
                                    $book->keywords()->firstOrCreate(['keyword' => Str::limit($kw, 250)]);
                                }
                            }
                        }
                    }

                    // Platform purchase links (supports pairs 1..8, e.g. وصلة بيع1 / منصة1, and legacy single link)
                    $this->syncPlatformLinks($book, $rowData);

                    if ($isNew) {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    \Log::error("Import Error: " . $e->getMessage());
                    Log::error("Import Error on row {$rowNumber} (ISBN: {$isbn}): " . $e->getMessage(), [
                        'row' => $rowNumber,
                        'isbn' => $isbn,
                        'file' => $filePath,
                        'exception' => $e,
                    ]);
                    $errors[] = "السطر {$rowNumber} (ISBN: {$isbn}): " . $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Import Error: " . $e->getMessage());
            Log::error("Import Error processing file {$filePath}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            $errors[] = "خطأ في قراءة ملف الإكسيل: " . $e->getMessage();
        }

        if (empty($headers)) {
            $msg = 'لم يتم العثور على ترويسة صالحة (Headers) في الملف. يرجى التأكد من وجود أعمدة مثل ISBN أو العنوان.';
            \Log::error("Import Error: " . $msg);
            $errors[] = $msg;
        }

        return [
            'total' => $total,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Resolve Author with conflict-free unique slug generation.
     */
    protected function resolveAuthor(string $name): Author
    {
        $name = Str::limit(trim($name), 250);
        $author = Author::where('name', $name)->first();
        if ($author) {
            return $author;
        }

        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'author-' . Str::random(6);
        } else {
            $baseSlug = Str::limit($baseSlug, 200, '');
        }

        $slug = $baseSlug;
        $counter = 1;
        while (Author::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return Author::create([
            'name' => $name,
            'slug' => $slug,
        ]);
    }

    /**
     * Resolve Publisher with conflict-free unique slug generation.
     */
    protected function resolvePublisher(string $name): Publisher
    {
        $name = Str::limit(trim($name), 250);
        $publisher = Publisher::where('name', $name)->first();
        if ($publisher) {
            return $publisher;
        }

        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'pub-' . Str::random(6);
        } else {
            $baseSlug = Str::limit($baseSlug, 200, '');
        }

        $slug = $baseSlug;
        $counter = 1;
        while (Publisher::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return Publisher::create([
            'name' => $name,
            'slug' => $slug,
        ]);
    }

    /**
     * Resolve Category with conflict-free unique slug generation.
     */
    protected function resolveCategory(string $name, ?int $parentId = null): Category
    {
        $name = Str::limit(trim($name), 250);
        $category = Category::where('name', $name)->first();
        if ($category) {
            if ($parentId !== null && $category->parent_id === null) {
                $category->parent_id = $parentId;
                $category->save();
            }
            return $category;
        }

        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'cat-' . Str::random(6);
        } else {
            $baseSlug = Str::limit($baseSlug, 200, '');
        }

        $slug = $baseSlug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return Category::create([
            'name' => $name,
            'parent_id' => $parentId,
            'slug' => $slug,
        ]);
    }

    /**
     * Safely parse a date value from Excel/CSV (handles DateTimeInterface, Carbon, and various string formats).
     */
    protected function parseDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (!is_scalar($value)) {
            return null;
        }

        $str = trim((string) $value);
        if ($str === '' || str_contains(strtolower($str), 'dd/mm/yyyy') || str_contains(strtolower($str), 'date') || str_contains(strtolower($str), 'تاريخ')) {
            return null;
        }

        // Format: DD/MM/YYYY, DD-MM-YYYY, DD.MM.YYYY
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $str, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        // Format: YYYY-MM-DD
        if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $str, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        // 4-digit year (e.g. 2024)
        if (preg_match('/^\d{4}$/', $str)) {
            return "{$str}-01-01";
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Safely parse an integer from mixed cell input.
     */
    protected function parseInteger(mixed $value, int $min = 1, int $max = 65535): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return min($max, max($min, $value));
        }

        if (is_float($value)) {
            return min($max, max($min, (int) round($value)));
        }

        if (is_string($value)) {
            // Convert Arabic-Indic digits to standard ASCII digits
            $str = strtr(trim($value), [
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ]);

            // Extract first sequence of digits
            if (preg_match('/\d+/', $str, $matches)) {
                $num = (int) $matches[0];
                return min($max, max($min, $num));
            }
        }

        return null;
    }

    /**
     * Safely process string values: handles DateTimeInterface, nulls, trims, filters template hints, and clamps length.
     */
    protected function safeString(mixed $val, ?int $maxLength = null): ?string
    {
        if ($val === null) {
            return null;
        }

        if ($val instanceof DateTimeInterface) {
            $str = $val->format('Y-m-d');
        } elseif (is_scalar($val)) {
            $str = trim((string) $val);
        } else {
            return null;
        }

        if ($str === '') {
            return null;
        }

        // Filter out template hint instructions
        $lower = strtolower($str);
        if (
            str_contains($lower, 'text in arabic') ||
            str_contains($lower, 'text in english') ||
            str_contains($lower, 'text and numbers in english') ||
            str_contains($lower, 'number in english') ||
            str_contains($lower, 'number 2 digits') ||
            str_contains($lower, '13 digits')
        ) {
            return null;
        }

        return $maxLength !== null ? Str::limit($str, $maxLength, '') : $str;
    }

    /**
     * Clean cell string value, trimming and filtering placeholder client text without length limits (for TEXT columns).
     */
    protected function cleanValue(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }

        if ($val instanceof DateTimeInterface) {
            return $val->format('Y-m-d');
        }

        if (!is_scalar($val)) {
            return null;
        }

        $str = trim((string) $val);
        if ($str === '') {
            return null;
        }

        // Filter out template hint instructions if client left them unchanged
        $lower = strtolower($str);
        if (
            str_contains($lower, 'text in arabic') ||
            str_contains($lower, 'text in english') ||
            str_contains($lower, 'text and numbers in english') ||
            str_contains($lower, 'number in english') ||
            str_contains($lower, 'number 2 digits') ||
            str_contains($lower, '13 digits')
        ) {
            return null;
        }

        return $str;
    }

    /**
     * Check if a row represents sample/instruction notes from the client template.
     */
    protected function isInstructionRow(array $cells, array $rowData): bool
    {
        $rawIsbn = strtolower(trim((string) ($rowData['isbn'] ?? '')));
        $rawTitle = strtolower(trim((string) ($rowData['title'] ?? '')));

        if (
            str_contains($rawIsbn, 'digit') ||
            str_contains($rawIsbn, 'dash') ||
            str_contains($rawIsbn, 'example') ||
            str_contains($rawIsbn, 'مثال') ||
            str_contains($rawIsbn, 'إرشاد') ||
            str_contains($rawIsbn, 'تعليمات') ||
            str_contains($rawTitle, 'text in') ||
            str_contains($rawTitle, 'example') ||
            str_contains($rawTitle, 'مثال')
        ) {
            return true;
        }

        $cellStrings = [];
        foreach ($cells as $cell) {
            if ($cell instanceof DateTimeInterface) {
                $cellStrings[] = $cell->format('Y-m-d');
            } elseif (is_scalar($cell)) {
                $cellStrings[] = (string) $cell;
            }
        }
        $combined = strtolower(implode(' ', $cellStrings));

        if (
            str_contains($combined, '13 digits without dashes') ||
            str_contains($combined, 'registered author') ||
            str_contains($combined, 'text in arabic') ||
            str_contains($combined, 'text in english')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Generator to iterate rows from .xls, .xlsx, or .csv files.
     *
     * @return \Generator<int, array<int, mixed>>
     */
    protected function iterateRows(string $filePath, string $extension): \Generator
    {
        if ($extension === 'xls') {
            $xls = SimpleXLS::parse($filePath);
            if (!$xls) {
                throw new \RuntimeException('تعذر قراءة ملف XLS: ' . SimpleXLS::parseError());
            }

            foreach ($xls->rows() as $rowNumber => $cells) {
                yield $rowNumber => $cells;
            }
            return;
        }

        if ($extension === 'csv' || $extension === 'txt') {
            $reader = new CsvReader();
        } else {
            $reader = new XlsxReader();
        }

        $reader->open($filePath);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                    yield $rowNumber => $row->toArray();
                }
                break; // Only process first sheet
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * Map common English and Arabic column names to internal canonical keys.
     */
    protected function normalizeHeaderName(mixed $header): ?string
    {
        if ($header === null || $header instanceof DateTimeInterface || !is_scalar($header)) {
            return null;
        }

        // 1. Strip UTF-8 BOM, zero-width spaces, non-breaking spaces
        $clean = preg_replace('/[\x{FEFF}\x{200B}-\x{200D}\x{00A0}]/u', '', (string) $header);

        // 2. Remove Arabic diacritics (tashkeel)
        $clean = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $clean);

        // 3. Convert to lowercase
        $h = mb_strtolower(trim($clean), 'UTF-8');

        // 4. Convert Arabic-Indic numerals (٠-٩) to ASCII numerals (0-9)
        $h = strtr($h, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        // 5. Remove all punctuation, brackets, dashes, underscores, and whitespace
        $h = preg_replace('/[_\-\s\t\r\n\(\)\[\]\{\}\.\,\:\;\'\"\/\\\\\#\*\?\!]/u', '', $h);

        if ($h === '') {
            return null;
        }

        // Check for indexed link columns (e.g. وصلة بيع1 .. وصلة بيع8, link1 .. link8)
        if (preg_match('/^(?:وصلةبيع|وصلة|رابطبيع|رابطشراء|رابطالشراء|رابطالبيع|رابطالمنصة|رابطالمتجر|رابطمتجر|رابط|لينكبيع|لينكشراء|لينك|buylink|salelink|purchaselink|storelink|platformlink|link|url)(\d+)$/u', $h, $m)) {
            return 'link_' . (int) $m[1];
        }

        // Check for indexed platform columns (e.g. منصة1 .. منصة8, platform1 .. platform8)
        if (preg_match('/^(?:منصةبيع|منصة|اسمالمنصة|اسمالمتجر|متجربيع|المتجر|متجر|platformname|platform|storename|store)(\d+)$/u', $h, $m)) {
            return 'platform_' . (int) $m[1];
        }

        // Check for ordinal Arabic numbers in links/platforms (e.g. وصلة بيع الأولى, منصة الأولى)
        $ordinalMap = [
            'الأولى' => 1, 'الاولى' => 1, 'الأول' => 1, 'الاول' => 1, 'واحد' => 1, 'first' => 1,
            'الثانية' => 2, 'الثانيه' => 2, 'الثاني' => 2, 'اثنين' => 2, 'اثنان' => 2, 'second' => 2,
            'الثالثة' => 3, 'الثالثه' => 3, 'الثالث' => 3, 'ثلاثة' => 3, 'ثلاثه' => 3, 'third' => 3,
            'الرابعة' => 4, 'الرابعه' => 4, 'الرابع' => 4, 'أربعة' => 4, 'اربعة' => 4, 'اربع' => 4, 'fourth' => 4,
            'الخامسة' => 5, 'الخامسه' => 5, 'الخامس' => 5, 'خمسة' => 5, 'خمسه' => 5, 'خمس' => 5, 'fifth' => 5,
            'السادسة' => 6, 'السادسه' => 6, 'السادس' => 6, 'ستة' => 6, 'سته' => 6, 'ست' => 6, 'sixth' => 6,
            'السابعة' => 7, 'السابعه' => 7, 'السابع' => 7, 'سبعة' => 7, 'سبعه' => 7, 'سبع' => 7, 'seventh' => 7,
            'الثامنة' => 8, 'الثامنه' => 8, 'الثامن' => 8, 'ثمانية' => 8, 'ثمانيه' => 8, 'ثماني' => 8, 'eighth' => 8,
        ];
        foreach ($ordinalMap as $ordWord => $num) {
            $cleanOrd = preg_replace('/[_\-\s\t\r\n\(\)\[\]\{\}\.\,\:\;\'\"\/\\\\\#\*\?\!]/u', '', $ordWord);
            if (preg_match('/^(?:وصلةبيع|وصلة|رابطبيع|رابطشراء|رابطالشراء|رابطالبيع|رابطالمنصة|رابطالمتجر|رابطمتجر|رابط|لينكبيع|لينكشراء|لينك|buylink|salelink|purchaselink|storelink|platformlink|link|url)' . preg_quote($cleanOrd, '/') . '$/u', $h)) {
                return 'link_' . $num;
            }
            if (preg_match('/^(?:منصةبيع|منصة|اسمالمنصة|اسمالمتجر|متجربيع|المتجر|متجر|platformname|platform|storename|store)' . preg_quote($cleanOrd, '/') . '$/u', $h)) {
                return 'platform_' . $num;
            }
        }

        // Canonical map for quick exact matches
        $canonicalMap = [
            // ISBN
            'isbn' => 'isbn',
            'bookisbn' => 'isbn',
            'ردمك' => 'isbn',
            'الردمك' => 'isbn',
            'رقمالردمك' => 'isbn',
            'الرقمالدولي' => 'isbn',
            'الرقمالمعياري' => 'isbn',
            'الرقمالدوليللكتاب' => 'isbn',
            'رقمالكتابالدولي' => 'isbn',
            'isbn13' => 'isbn',
            'isbn10' => 'isbn',
            'ean' => 'isbn',
            'barcode' => 'isbn',
            'الباركود' => 'isbn',
            'كودالكتاب' => 'isbn',

            // Title
            'title' => 'title',
            'booktitle' => 'title',
            'عنوان' => 'title',
            'العنوان' => 'title',
            'اسمالكتاب' => 'title',
            'عنوانالكتاب' => 'title',
            'اسمالكتابالرئيسي' => 'title',

            // Subtitle
            'subtitle' => 'subtitle',
            'booksubtitle' => 'subtitle',
            'العنوانالفرعي' => 'subtitle',
            'عنوانفرعي' => 'subtitle',
            'الفرعي' => 'subtitle',

            // Author
            'author' => 'author',
            'bookauthor' => 'author',
            'authorname' => 'author',
            'المؤلف' => 'author',
            'مؤلف' => 'author',
            'اسمالمؤلف' => 'author',
            'الكاتب' => 'author',
            'كاتب' => 'author',
            'اسمالكاتب' => 'author',
            'المؤلفالرئيسي' => 'author',

            // Illustrator
            'illustrator' => 'illustrator',
            'illustratorname' => 'illustrator',
            'الرسام' => 'illustrator',
            'رسام' => 'illustrator',
            'اسمالرسام' => 'illustrator',
            'رسومات' => 'illustrator',

            // Translator
            'translator' => 'translator',
            'translatorname' => 'translator',
            'المترجم' => 'translator',
            'مترجم' => 'translator',
            'اسمالمترجم' => 'translator',
            'ترجمة' => 'translator',

            // Editor
            'editor' => 'editor',
            'editorname' => 'editor',
            'المحرر' => 'editor',
            'محرر' => 'editor',
            'اسمالمحرر' => 'editor',
            'تحرير' => 'editor',

            // Publisher
            'publisher' => 'publisher',
            'publishername' => 'publisher',
            'الناشر' => 'publisher',
            'ناشر' => 'publisher',
            'دارالنشر' => 'publisher',
            'اسمالناشر' => 'publisher',
            'اسم دارالنشر' => 'publisher',
            'دارنشر' => 'publisher',
            'جهةالنشر' => 'publisher',
            'المطبعة' => 'publisher',

            // Series
            'series' => 'series',
            'seriesname' => 'series',
            'اسمالسلسلة' => 'series',
            'السلسلة' => 'series',
            'سلسلة' => 'series',

            // Series position
            'seriesposition' => 'series_position',
            'الموقعفيالسلسلة' => 'series_position',
            'موقعفيالسلسلة' => 'series_position',
            'رقمالجزء' => 'series_position',
            'الجزء' => 'series_position',
            'part' => 'series_position',
            'volume' => 'series_position',

            // Codes
            'bisac' => 'bisac_code',
            'bisaccode' => 'bisac_code',
            'bookbisaccode' => 'bisac_code',
            'كودبيساك' => 'bisac_code',
            'رمزبيساك' => 'bisac_code',
            'بيساك' => 'bisac_code',

            'wgs' => 'wgs_code',
            'wgscode' => 'wgs_code',
            'كودwgs' => 'wgs_code',
            'رمزwgs' => 'wgs_code',

            'thema' => 'thema_code',
            'themacode' => 'thema_code',
            'كودثيما' => 'thema_code',
            'رمزثيما' => 'thema_code',
            'ثيما' => 'thema_code',

            'catalog' => 'catalog_code',
            'catalogcode' => 'catalog_code',
            'كودالفهرس' => 'catalog_code',
            'رمزالفهرس' => 'catalog_code',
            'الفهرس' => 'catalog_code',

            // Languages
            'originallanguage' => 'original_language',
            'اللغةالأصلية' => 'original_language',
            'اللغةالاصلية' => 'original_language',
            'لغةأصلية' => 'original_language',
            'لغةاصلية' => 'original_language',

            'language' => 'language',
            'booklanguage' => 'language',
            'لغةالكتاب' => 'language',
            'اللغة' => 'language',
            'لغة' => 'language',
            'lang' => 'language',

            // Publication date
            'publicationdate' => 'publication_date',
            'pubdate' => 'publication_date',
            'releasedate' => 'publication_date',
            'date' => 'publication_date',
            'year' => 'publication_date',
            'تاريخالنشر' => 'publication_date',
            'تاريخالطباعة' => 'publication_date',
            'سنةالنشر' => 'publication_date',
            'تاريخالإصدار' => 'publication_date',
            'تاريخالاصدار' => 'publication_date',
            'سنةالإصدار' => 'publication_date',
            'سنةالاصدار' => 'publication_date',
            'تاريخ' => 'publication_date',

            // Keywords
            'keywords' => 'keywords',
            'tags' => 'keywords',
            'كلماتمفتاحية' => 'keywords',
            'الكلماتالمفتاحية' => 'keywords',
            'كلماتدلالية' => 'keywords',
            'الكلماتالدلالية' => 'keywords',
            'الوسوم' => 'keywords',
            'وسوم' => 'keywords',

            // Synopsis / Description
            'synopsis' => 'synopsis',
            'description' => 'synopsis',
            'blurb' => 'synopsis',
            'summary' => 'synopsis',
            'about' => 'synopsis',
            'النبذة' => 'synopsis',
            'نبذة' => 'synopsis',
            'الملخص' => 'synopsis',
            'ملخص' => 'synopsis',
            'الوصف' => 'synopsis',
            'وصف' => 'synopsis',
            'نبذةعنالكتاب' => 'synopsis',
            'ملخصالكتاب' => 'synopsis',
            'وصفالكتاب' => 'synopsis',

            // Age Range
            'age' => 'age_range',
            'agerange' => 'age_range',
            'agegroup' => 'age_range',
            'العمر' => 'age_range',
            'الفئةالعمرية' => 'age_range',
            'فئةعمرية' => 'age_range',
            'المرحلةالعمرية' => 'age_range',
            'السن' => 'age_range',

            // Pages
            'pages' => 'pages',
            'pagecount' => 'pages',
            'numberofpages' => 'pages',
            'الصفحات' => 'pages',
            'عددالصفحات' => 'pages',
            'عددالأوراق' => 'pages',
            'عددالاوراق' => 'pages',

            // Categories
            'category' => 'category',
            'category1' => 'category',
            'categorymain' => 'category',
            'maincategory' => 'category',
            'فئة1' => 'category',
            'فئه1' => 'category',
            'فئة' => 'category',
            'فئه' => 'category',
            'التصنيف' => 'category',
            'تصنيف' => 'category',
            'التصنيفالرئيسي' => 'category',
            'التصنيف1' => 'category',
            'تصنيف1' => 'category',
            'الفئة1' => 'category',
            'الفئه1' => 'category',
            'الفئةالأولى' => 'category',
            'الفئةالاولي' => 'category',
            'القسم' => 'category',
            'subject1' => 'category',
            'الموضوع1' => 'category',
            'موضوع1' => 'category',

            // Subcategories
            'subcategory' => 'subcategory',
            'category2' => 'subcategory',
            'فئة2' => 'subcategory',
            'فئه2' => 'subcategory',
            'الفئة2' => 'subcategory',
            'الفئه2' => 'subcategory',
            'الفئةالثانية' => 'subcategory',
            'الفئةالثانيه' => 'subcategory',
            'التصنيفالفرعي' => 'subcategory',
            'تصنيففرعي' => 'subcategory',
            'التصنيف2' => 'subcategory',
            'تصنيف2' => 'subcategory',
            'الموضوعالفرعي' => 'subcategory',
            'subcat' => 'subcategory',
            'subject2' => 'subcategory',
            'الموضوع2' => 'subcategory',
            'موضوع2' => 'subcategory',

            'subject3' => 'subject_3',
            'الموضوع3' => 'subject_3',
            'موضوع3' => 'subject_3',
            'subject4' => 'subject_4',
            'الموضوع4' => 'subject_4',
            'موضوع4' => 'subject_4',

            // Format
            'format' => 'format',
            'bookformat' => 'format',
            'صيغة' => 'format',
            'الصيغة' => 'format',
            'نوعالوعاء' => 'format',
            'نوعالكتاب' => 'format',
            'نوعالملف' => 'format',

            // Author bio
            'authorbio' => 'author_bio',
            'نبذةعنالمؤلف' => 'author_bio',
            'نبذةالمؤلف' => 'author_bio',
            'سيرةالمؤلف' => 'author_bio',
            'عنالمؤلف' => 'author_bio',

            // Contributor
            'contributor' => 'contributor_name',
            'contributorname' => 'contributor_name',
            'المشارك' => 'contributor_name',
            'اسمالمشارك' => 'contributor_name',
            'contributorrole' => 'contributor_role',
            'صفةالمشارك' => 'contributor_role',
            'دورالمشارك' => 'contributor_role',

            // Cover
            'cover' => 'cover_path',
            'coverpath' => 'cover_path',
            'coverurl' => 'cover_path',
            'coverimage' => 'cover_path',
            'الغلاف' => 'cover_path',
            'صورةالغلاف' => 'cover_path',
            'مسارالغلاف' => 'cover_path',
            'رابطالغلاف' => 'cover_path',

            // Links & Platforms (single / un-indexed)
            'link' => 'link',
            'links' => 'link',
            'url' => 'link',
            'الرابط' => 'link',
            'رابطالشراء' => 'link',
            'رابطالبيع' => 'link',
            'وصلةبيع' => 'link',
            'وصلة' => 'link',
            'رابطالمتجر' => 'link',
            'متجر' => 'platform_name',
            'المتجر' => 'platform_name',
            'منصة' => 'platform_name',
            'اسمالمنصة' => 'platform_name',
            'platform' => 'platform_name',
            'platformname' => 'platform_name',
        ];

        // Explicit canonical mappings for pairs 1 through 10
        for ($i = 1; $i <= 10; $i++) {
            $canonicalMap["وصلةبيع{$i}"] = "link_{$i}";
            $canonicalMap["وصلة{$i}"] = "link_{$i}";
            $canonicalMap["رابطبيع{$i}"] = "link_{$i}";
            $canonicalMap["رابطشراء{$i}"] = "link_{$i}";
            $canonicalMap["رابطالشراء{$i}"] = "link_{$i}";
            $canonicalMap["رابطالبيع{$i}"] = "link_{$i}";
            $canonicalMap["رابطالمتجر{$i}"] = "link_{$i}";
            $canonicalMap["رابط{$i}"] = "link_{$i}";
            $canonicalMap["لينك{$i}"] = "link_{$i}";
            $canonicalMap["buylink{$i}"] = "link_{$i}";
            $canonicalMap["salelink{$i}"] = "link_{$i}";
            $canonicalMap["purchaselink{$i}"] = "link_{$i}";
            $canonicalMap["storelink{$i}"] = "link_{$i}";
            $canonicalMap["platformlink{$i}"] = "link_{$i}";
            $canonicalMap["link{$i}"] = "link_{$i}";
            $canonicalMap["url{$i}"] = "link_{$i}";

            $canonicalMap["منصة{$i}"] = "platform_{$i}";
            $canonicalMap["منصةبيع{$i}"] = "platform_{$i}";
            $canonicalMap["اسمالمنصة{$i}"] = "platform_{$i}";
            $canonicalMap["المتجر{$i}"] = "platform_{$i}";
            $canonicalMap["متجر{$i}"] = "platform_{$i}";
            $canonicalMap["اسمالمتجر{$i}"] = "platform_{$i}";
            $canonicalMap["platform{$i}"] = "platform_{$i}";
            $canonicalMap["platformname{$i}"] = "platform_{$i}";
            $canonicalMap["store{$i}"] = "platform_{$i}";
            $canonicalMap["storename{$i}"] = "platform_{$i}";
        }

        if (isset($canonicalMap[$h])) {
            return $canonicalMap[$h];
        }

        // Normalized Arabic check (alef/teh/yeh variations)
        $hNorm = str_replace(['أ', 'إ', 'آ'], 'ا', $h);
        $hNorm = str_replace('ة', 'ه', $hNorm);
        $hNorm = str_replace('ى', 'ي', $hNorm);

        foreach ($canonicalMap as $key => $target) {
            $keyNorm = str_replace(['أ', 'إ', 'آ'], 'ا', $key);
            $keyNorm = str_replace('ة', 'ه', $keyNorm);
            $keyNorm = str_replace('ى', 'ي', $keyNorm);

            if ($hNorm === $keyNorm) {
                return $target;
            }
        }

        // Resilient substring pattern matching
        if (str_contains($h, 'isbn') || str_contains($h, 'ردمك') || str_contains($h, 'معياري')) {
            return 'isbn';
        }
        if (str_contains($h, 'subtitle') || str_contains($h, 'فرعي')) {
            return 'subtitle';
        }
        if (str_contains($h, 'title') || str_contains($h, 'عنوان') || str_contains($h, 'اسمالكتاب')) {
            return 'title';
        }
        if (str_contains($h, 'authorbio') || str_contains($h, 'سيرةالمؤلف') || str_contains($h, 'نبذةالمؤلف')) {
            return 'author_bio';
        }
        if (str_contains($h, 'author') || str_contains($h, 'مؤلف') || str_contains($h, 'كاتب')) {
            return 'author';
        }
        if (str_contains($h, 'publisher') || str_contains($h, 'ناشر') || str_contains($h, 'دارنشر')) {
            return 'publisher';
        }
        if (str_contains($h, 'subcat') || str_contains($h, 'فئة2') || str_contains($h, 'فئه2') || str_contains($h, 'تصنيففرعي')) {
            return 'subcategory';
        }
        if (str_contains($h, 'category') || str_contains($h, 'فئة') || str_contains($h, 'فئه') || str_contains($h, 'تصنيف')) {
            return 'category';
        }

        return null;
    }

    /**
     * Read, map, validate, and store platform links from the imported row.
     * Supports pairs from 1 up to 8 (and beyond) plus un-indexed link/platform fields.
     */
    protected function syncPlatformLinks(Book $book, array $rowData): void
    {
        $collectedLinks = [];
        $seenUrls = [];

        // 1. Process indexed pairs: platform_1/link_1 through platform_8/link_8 (up to 20)
        for ($i = 1; $i <= 20; $i++) {
            $rawLink = $rowData["link_{$i}"] ?? null;
            $rawPlatform = $rowData["platform_{$i}"] ?? null;

            $parsed = $this->parsePlatformLinkPair($rawLink, $rawPlatform, $i);
            if ($parsed !== null) {
                $canonicalUrl = strtolower($parsed['url']);
                if (!isset($seenUrls[$canonicalUrl])) {
                    $seenUrls[$canonicalUrl] = true;
                    $collectedLinks[] = $parsed;
                }
            }
        }

        // 2. Process legacy/un-indexed link & platform if not already collected
        if (!empty($rowData['link'])) {
            $parsed = $this->parsePlatformLinkPair($rowData['link'], $rowData['platform_name'] ?? null, count($collectedLinks) + 1);
            if ($parsed !== null) {
                $canonicalUrl = strtolower($parsed['url']);
                if (!isset($seenUrls[$canonicalUrl])) {
                    $seenUrls[$canonicalUrl] = true;
                    $collectedLinks[] = $parsed;
                }
            }
        }

        // 3. If any valid platform links were provided in this row, store them into the book data structure
        if (!empty($collectedLinks)) {
            // Delete existing links for this book to ensure exact order and prevent duplicates
            $book->platformLinks()->delete();

            foreach ($collectedLinks as $sortIdx => $linkItem) {
                $book->platformLinks()->create([
                    'platform_name' => Str::limit($linkItem['platform_name'], 100, ''),
                    'url' => $linkItem['url'],
                    'sort_order' => $sortIdx,
                ]);
            }
        }
    }

    /**
     * Validate, sanitize, and format a single platform & link pair.
     */
    protected function parsePlatformLinkPair(mixed $rawLink, mixed $rawPlatform, int $index): ?array
    {
        $link = is_scalar($rawLink) ? trim((string) $rawLink) : '';
        $platform = is_scalar($rawPlatform) ? trim((string) $rawPlatform) : '';

        // If both are empty, nothing to do
        if ($link === '' && $platform === '') {
            return null;
        }

        // Defensive check: if the user swapped platform and link columns in Excel
        if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL) && !str_starts_with($link, 'http://') && !str_starts_with($link, 'https://')) {
            if ($platform !== '' && (filter_var($platform, FILTER_VALIDATE_URL) || str_starts_with($platform, 'http://') || str_starts_with($platform, 'https://') || str_starts_with($platform, 'www.'))) {
                // Swap them
                $temp = $link;
                $link = $platform;
                $platform = $temp;
            }
        }

        // Filter out template hint instruction strings
        $lowerLink = strtolower($link);
        if (
            $link === '' ||
            str_contains($lowerLink, 'text in') ||
            str_contains($lowerLink, 'http://example') ||
            str_contains($lowerLink, 'https://example') ||
            str_contains($lowerLink, 'url here') ||
            str_contains($lowerLink, 'رابط المتجر') ||
            str_contains($lowerLink, 'وصلة بيع') ||
            str_contains($lowerLink, 'اسم المنصة')
        ) {
            return null;
        }

        // Clean platform name if it contains placeholder text
        $lowerPlatform = strtolower($platform);
        if (
            str_contains($lowerPlatform, 'text in') ||
            str_contains($lowerPlatform, 'اسم المنصة') ||
            str_contains($lowerPlatform, 'platform name')
        ) {
            $platform = '';
        }

        // Ensure scheme exists for valid domain URLs (e.g. www.amazon.com -> https://www.amazon.com)
        if (!preg_match('/^https?:\/\//i', $link)) {
            if (preg_match('/^[a-z0-9\-\.]+\.[a-z]{2,}/i', $link)) {
                $link = 'https://' . $link;
            }
        }

        // Validate URL
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Infer platform name if empty
        if ($platform === '') {
            $platform = $this->inferPlatformNameFromUrl($link, $index);
        }

        return [
            'platform_name' => $platform,
            'url' => $link,
        ];
    }

    /**
     * Infer human-readable platform name from URL hostname when platform name is omitted.
     */
    protected function inferPlatformNameFromUrl(string $url, int $index): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if (str_contains($host, 'play.google') || str_contains($host, 'books.google')) {
            return 'جوجل بلاي';
        }
        if (str_contains($host, 'amazon') || str_contains($host, 'amzn')) {
            return 'أمازون';
        }
        if (str_contains($host, 'abjjad')) {
            return 'أبجد';
        }
        if (str_contains($host, 'neelwafurat')) {
            return 'نيل وفرات';
        }
        if (str_contains($host, 'jamalon')) {
            return 'جملون';
        }
        if (str_contains($host, 'jarir')) {
            return 'مكتبة جرير';
        }
        if (str_contains($host, 'rufoof')) {
            return 'رفوف';
        }
        if (str_contains($host, 'kobo')) {
            return 'Kobo';
        }
        if (str_contains($host, 'apple') || str_contains($host, 'itunes')) {
            return 'Apple Books';
        }
        if (str_contains($host, 'storytel')) {
            return 'Storytel';
        }
        if (str_contains($host, 'obeikan')) {
            return 'مكتبة العبيكان';
        }

        return "منصة {$index}";
    }
}
