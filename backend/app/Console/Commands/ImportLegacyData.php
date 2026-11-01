<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookBisacCode;
use App\Models\BookKeyword;
use App\Models\BookPlatformLink;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLegacyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-legacy-data
                            {--path= : Custom path to the legacy books.json file}
                            {--covers-path= : Custom path to legacy covers directory}
                            {--force : Force import without prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy books JSON records and copy cover images to public storage with dynamic relationship resolution.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  Al-Mashreq eBookstore: Legacy Data & Covers Migration');
        $this->info('====================================================');

        // 1. Locate the legacy JSON file
        $jsonPath = $this->locateJsonFile();
        if (!$jsonPath) {
            $this->error('Error: Could not locate the legacy books.json file.');
            $this->line('Searched locations:');
            foreach ($this->getCandidateJsonPaths() as $p) {
                $this->line('  - ' . $p);
            }
            return self::FAILURE;
        }

        $this->info("Located legacy JSON file at: [{$jsonPath}]");

        // 2. Locate the legacy covers directory
        $coversDir = $this->locateCoversDirectory();
        if ($coversDir) {
            $this->info("Located legacy covers directory at: [{$coversDir}]");
        } else {
            $this->warn('Warning: Could not locate legacy covers directory. Will proceed without cover image copying.');
        }

        // 3. Prepare target public covers storage directory
        $destStorageDir = storage_path('app/public/covers');
        if (!is_dir($destStorageDir)) {
            mkdir($destStorageDir, 0755, true);
        }
        $this->info("Target covers storage directory: [{$destStorageDir}]");

        // 4. Read and decode JSON file
        $this->info('Reading and decoding JSON file...');
        $startTime = microtime(true);

        $jsonContent = file_get_contents($jsonPath);
        if ($jsonContent === false) {
            $this->error("Failed to read file contents from: {$jsonPath}");
            return self::FAILURE;
        }

        $decoded = json_decode($jsonContent, true);
        if (!is_array($decoded)) {
            $this->error('Failed to parse JSON file: ' . json_last_error_msg());
            return self::FAILURE;
        }

        $books = $decoded['books'] ?? (isset($decoded[0]) ? $decoded : []);
        $totalFound = count($books);

        if ($totalFound === 0) {
            $this->warn('No book records found in the JSON file.');
            return self::SUCCESS;
        }

        $this->info("Found {$totalFound} book records to process.");

        // In-memory caches to eliminate redundant database queries
        $authorCache = [];
        $publisherCache = [];
        $categoryCache = [];

        // Preload existing entities into memory
        Author::all(['id', 'name'])->each(function ($a) use (&$authorCache) {
            $authorCache[trim($a->name)] = $a->id;
        });

        Publisher::all(['id', 'name'])->each(function ($p) use (&$publisherCache) {
            $publisherCache[trim($p->name)] = $p->id;
        });

        Category::whereNull('parent_id')->get(['id', 'name'])->each(function ($c) use (&$categoryCache) {
            $categoryCache['root:' . trim($c->name)] = $c->id;
        });

        Category::whereNotNull('parent_id')->get(['id', 'name', 'parent_id'])->each(function ($sc) use (&$categoryCache) {
            $categoryCache[$sc->parent_id . ':' . trim($sc->name)] = $sc->id;
        });

        $this->info('Initialized relationship caches. Starting migration...');

        $bar = $this->output->createProgressBar($totalFound);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% (%elapsed:6s% / %estimated:-6s%) - %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $coversCopiedCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($books as $item) {
            $rawIsbn = trim((string) ($item['ISBN'] ?? $item['isbn'] ?? ''));
            if (empty($rawIsbn)) {
                $skippedCount++;
                $bar->advance();
                continue;
            }

            // Truncate ISBN to fit database limit
            $isbn = Str::limit($rawIsbn, 20, '');

            $rawTitle = trim((string) ($item['Title'] ?? $item['title'] ?? ''));
            $title = filled($rawTitle) ? Str::limit($rawTitle, 1000, '') : ('كتاب ' . $isbn);

            try {
                // 1. Process Cover Image
                $coverRelativePath = null;
                if ($coversDir) {
                    $coverSource = $this->findLegacyCoverFile($coversDir, $rawIsbn, $isbn);
                    if ($coverSource) {
                        $ext = pathinfo($coverSource, PATHINFO_EXTENSION) ?: 'jpg';
                        $targetFilename = $isbn . '.' . $ext;
                        $targetFull = $destStorageDir . DIRECTORY_SEPARATOR . $targetFilename;

                        if (!file_exists($targetFull) || filesize($targetFull) !== filesize($coverSource)) {
                            copy($coverSource, $targetFull);
                            $coversCopiedCount++;
                        }

                        $coverRelativePath = 'covers/' . $targetFilename;
                    }
                }

                // 2. Upsert Book and Relationships within Transaction
                DB::transaction(function () use (
                    $item,
                    $isbn,
                    $title,
                    $coverRelativePath,
                    &$authorCache,
                    &$publisherCache,
                    &$categoryCache,
                    &$createdCount,
                    &$updatedCount,
                    &$bar
                ) {
                    // Resolve / Create Author dynamically
                    $authorId = null;
                    $authorName = trim((string) ($item['Author'] ?? $item['author'] ?? ''));
                    if (filled($authorName)) {
                        $authorId = $this->resolveAuthor($authorName, $authorCache);
                    }

                    // Resolve / Create Publisher dynamically
                    $publisherId = null;
                    $pubName = trim((string) ($item['Publisher'] ?? $item['publisher'] ?? ''));
                    if (filled($pubName)) {
                        $publisherId = $this->resolvePublisher($pubName, $publisherCache);
                    }

                    // Resolve / Create Main Category dynamically
                    $categoryId = null;
                    $catName = trim((string) ($item['CategoryMain'] ?? $item['category'] ?? ''));
                    if (empty($catName) && !empty($item['Category'])) {
                        $parts = explode('/', $item['Category']);
                        $catName = trim($parts[0]);
                    }

                    if (filled($catName)) {
                        $categoryId = $this->resolveCategory($catName, null, $categoryCache);
                    }

                    // Resolve / Create SubCategory dynamically
                    $subCategoryId = null;
                    $subCatName = trim((string) ($item['Subject_2'] ?? $item['subcategory'] ?? ''));
                    if (empty($subCatName) && !empty($item['Category'])) {
                        $parts = explode('/', $item['Category']);
                        if (isset($parts[1])) {
                            $subCatName = trim($parts[1]);
                        }
                    }

                    if (filled($subCatName)) {
                        $subCategoryId = $this->resolveCategory($subCatName, $categoryId, $categoryCache);
                    }

                    // Clean and sanitize book fields
                    $rawPages = $item['Pages'] ?? $item['pages'] ?? null;
                    $pages = (is_numeric($rawPages) && (int) $rawPages > 0 && (int) $rawPages <= 65535) ? (int) $rawPages : null;

                    $rawLang = trim((string) ($item['Language'] ?? $item['language'] ?? ''));
                    $language = filled($rawLang) ? Str::limit($rawLang, 50, '') : 'Arabic';

                    $rawAge = trim((string) ($item['Age'] ?? $item['age_range'] ?? ''));
                    $ageRange = filled($rawAge) ? Str::limit($rawAge, 50, '') : null;

                    $rawSeries = trim((string) ($item['Series'] ?? $item['series'] ?? ''));
                    $series = filled($rawSeries) ? Str::limit($rawSeries, 50, '') : null;

                    $rawSubTitle = trim((string) ($item['SubTitle'] ?? $item['subtitle'] ?? ''));
                    $subTitle = filled($rawSubTitle) ? Str::limit($rawSubTitle, 1000, '') : null;

                    $rawContributor = trim((string) ($item['Contributor'] ?? $item['contributor_name'] ?? ''));
                    $contributorName = filled($rawContributor) ? Str::limit($rawContributor, 500, '') : null;

                    $rawRole = trim((string) ($item['Contributor Role'] ?? $item['contributor_role'] ?? ''));
                    $contributorRole = filled($rawRole) ? Str::limit($rawRole, 100, '') : null;

                    $synopsis = filled($item['Synopsis'] ?? $item['synopsis'] ?? null) ? trim($item['Synopsis'] ?? $item['synopsis']) : null;
                    $searchVector = filled($item['_search'] ?? null) ? trim((string) $item['_search']) : null;

                    // Find or create Book instance
                    $book = Book::where('isbn', $isbn)->first();
                    $isNew = false;

                    if (!$book) {
                        $isNew = true;
                        $book = new Book();
                        $book->isbn = $isbn;
                    }

                    $book->title = $title;
                    $book->subtitle = $subTitle;
                    $book->series = $series;
                    $book->synopsis = $synopsis;
                    $book->pages = $pages;
                    $book->language = $language;
                    $book->age_range = $ageRange;
                    $book->search_vector = $searchVector;
                    $book->contributor_name = $contributorName;
                    $book->contributor_role = $contributorRole;

                    if ($authorId) $book->author_id = $authorId;
                    if ($publisherId) $book->publisher_id = $publisherId;
                    if ($categoryId) $book->category_id = $categoryId;
                    if ($subCategoryId) $book->sub_category_id = $subCategoryId;

                    // Update cover path if resolved
                    if ($coverRelativePath) {
                        $book->cover_path = $coverRelativePath;
                        $book->cover = $coverRelativePath;
                    }

                    // Preserve or set default publishing status
                    if ($book->is_published === null) $book->is_published = true;
                    if ($book->is_featured === null) $book->is_featured = false;

                    $book->save();

                    // Sync Platform Links
                    $links = $item['Links'] ?? [];
                    if (is_array($links) && count($links) > 0) {
                        foreach ($links as $sortIdx => $l) {
                            $lUrl = trim((string) ($l['url'] ?? ''));
                            if (filled($lUrl) && filter_var($lUrl, FILTER_VALIDATE_URL) && strlen($lUrl) <= 2000) {
                                BookPlatformLink::updateOrCreate(
                                    ['book_id' => $book->id, 'url' => $lUrl],
                                    [
                                        'platform_name' => Str::limit(trim((string) ($l['name'] ?? 'متجر إلكتروني')), 255, ''),
                                        'sort_order' => $sortIdx,
                                    ]
                                );
                            }
                        }
                    } elseif (!empty($item['Link']) && filter_var($item['Link'], FILTER_VALIDATE_URL) && strlen($item['Link']) <= 2000) {
                        BookPlatformLink::updateOrCreate(
                            ['book_id' => $book->id, 'url' => trim($item['Link'])],
                            [
                                'platform_name' => 'متجر إلكتروني',
                                'sort_order' => 0,
                            ]
                        );
                    }

                    // Sync BISAC codes
                    foreach (['BISAC1', 'BISAC2'] as $bisacKey) {
                        if (!empty($item[$bisacKey])) {
                            $code = Str::limit(trim((string) $item[$bisacKey]), 50, '');
                            if (filled($code)) {
                                BookBisacCode::firstOrCreate([
                                    'book_id' => $book->id,
                                    'code' => $code,
                                ]);
                            }
                        }
                    }

                    // Sync Keywords
                    if (!empty($item['Keywords'])) {
                        $rawKeywords = explode(',', (string) $item['Keywords']);
                        foreach ($rawKeywords as $kw) {
                            $kw = Str::limit(trim($kw), 255, '');
                            if (filled($kw)) {
                                BookKeyword::firstOrCreate([
                                    'book_id' => $book->id,
                                    'keyword' => $kw,
                                ]);
                            }
                        }
                    }

                    if ($isNew) {
                        $createdCount++;
                    } else {
                        $updatedCount++;
                    }

                    $bar->setMessage(Str::limit($book->title, 25));
                });
            } catch (\Throwable $e) {
                $errorCount++;
                if (count($errors) < 10) {
                    $errors[] = "ISBN {$isbn} ({$title}): " . $e->getMessage();
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->info('----------------------------------------------------');
        $this->info("  Migration completed in {$elapsed} seconds!");
        $this->info('----------------------------------------------------');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records in JSON', $totalFound],
                ['New Books Created', $createdCount],
                ['Existing Books Updated', $updatedCount],
                ['Cover Images Copied / Verified', $coversCopiedCount],
                ['Books with Cover Path in DB', Book::whereNotNull('cover_path')->count()],
                ['Skipped (Missing ISBN)', $skippedCount],
                ['Failed Records', $errorCount],
                ['Total Books in Database', Book::count()],
                ['Total Authors in Database', Author::count()],
                ['Total Categories in Database', Category::count()],
                ['Total Publishers in Database', Publisher::count()],
            ]
        );

        if (!empty($errors)) {
            $this->warn('Sample errors encountered:');
            foreach ($errors as $err) {
                $this->line('  - ' . $err);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Search and match legacy cover file for an ISBN.
     */
    protected function findLegacyCoverFile(string $dir, string $rawIsbn, string $cleanIsbn): ?string
    {
        $candidates = [
            $dir . DIRECTORY_SEPARATOR . $cleanIsbn . '.jpg',
            $dir . DIRECTORY_SEPARATOR . $cleanIsbn . '.jpeg',
            $dir . DIRECTORY_SEPARATOR . $cleanIsbn . '.png',
            $dir . DIRECTORY_SEPARATOR . $cleanIsbn . '.webp',
            $dir . DIRECTORY_SEPARATOR . $rawIsbn . '.jpg',
            $dir . DIRECTORY_SEPARATOR . $rawIsbn . '.jpeg',
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                return $cand;
            }
        }

        // Fuzzy match: e.g. 9781005956011_X.jpg or 9781005956011_1.jpg
        $matches = glob($dir . DIRECTORY_SEPARATOR . $cleanIsbn . '*.*');
        if ($matches) {
            foreach ($matches as $match) {
                $ext = strtolower(pathinfo($match, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    return $match;
                }
            }
        }

        return null;
    }

    /**
     * Locate the legacy JSON file across multiple possible locations.
     */
    protected function locateJsonFile(): ?string
    {
        $customPath = $this->option('path');
        if (filled($customPath)) {
            $resolved = realpath($customPath) ?: $customPath;
            if (file_exists($resolved)) {
                return $resolved;
            }
        }

        foreach ($this->getCandidateJsonPaths() as $candidate) {
            if (file_exists($candidate)) {
                return realpath($candidate);
            }
        }

        return null;
    }

    /**
     * Get candidate file paths for books.json.
     *
     * @return array<string>
     */
    protected function getCandidateJsonPaths(): array
    {
        return [
            base_path('../data/books.json'),
            base_path('data/books.json'),
            public_path('data/books.json'),
            public_path('js/data/books.json'),
            base_path('../public/data/books.json'),
            base_path('../../data/books.json'),
        ];
    }

    /**
     * Locate the legacy covers directory across multiple candidate locations.
     */
    protected function locateCoversDirectory(): ?string
    {
        $customPath = $this->option('covers-path');
        if (filled($customPath)) {
            $resolved = realpath($customPath) ?: $customPath;
            if (is_dir($resolved)) {
                return $resolved;
            }
        }

        $candidates = [
            base_path('../covers'),
            base_path('covers'),
            public_path('covers'),
            base_path('../../covers'),
            base_path('../frontend/covers'),
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                return realpath($candidate);
            }
        }

        return null;
    }

    /**
     * Dynamically resolve or create author with unique slug.
     */
    protected function resolveAuthor(string $name, array &$cache): int
    {
        $name = trim($name);
        if (isset($cache[$name])) {
            return $cache[$name];
        }

        $existing = Author::where('name', $name)->first();
        if ($existing) {
            $cache[$name] = $existing->id;
            return $existing->id;
        }

        $slug = $this->generateUniqueSlug(Author::class, $name, 'author');
        $author = Author::firstOrCreate(
            ['name' => Str::limit($name, 500, '')],
            ['slug' => $slug]
        );

        $cache[$name] = $author->id;
        return $author->id;
    }

    /**
     * Dynamically resolve or create publisher with unique slug.
     */
    protected function resolvePublisher(string $name, array &$cache): int
    {
        $name = trim($name);
        if (isset($cache[$name])) {
            return $cache[$name];
        }

        $existing = Publisher::where('name', $name)->first();
        if ($existing) {
            $cache[$name] = $existing->id;
            return $existing->id;
        }

        $slug = $this->generateUniqueSlug(Publisher::class, $name, 'pub');
        $publisher = Publisher::firstOrCreate(
            ['name' => Str::limit($name, 255, '')],
            ['slug' => $slug]
        );

        $cache[$name] = $publisher->id;
        return $publisher->id;
    }

    /**
     * Dynamically resolve or create category with unique slug and parent linking.
     */
    protected function resolveCategory(string $name, ?int $parentId, array &$cache): int
    {
        $name = trim($name);
        $cacheKey = ($parentId ?? 'root') . ':' . $name;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $query = Category::where('name', $name);
        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        $existing = $query->first();
        if ($existing) {
            $cache[$cacheKey] = $existing->id;
            return $existing->id;
        }

        $slug = $this->generateUniqueSlug(Category::class, $name, 'cat');
        $category = Category::firstOrCreate(
            [
                'name' => Str::limit($name, 255, ''),
                'parent_id' => $parentId,
            ],
            ['slug' => $slug]
        );

        $cache[$cacheKey] = $category->id;
        return $category->id;
    }

    /**
     * Safely generate a unique slug to satisfy UNIQUE database constraints.
     */
    protected function generateUniqueSlug(string $modelClass, string $name, string $prefix): string
    {
        $baseSlug = Str::slug($name);
        if (blank($baseSlug)) {
            $baseSlug = $prefix . '-' . Str::random(6);
        }
        $baseSlug = Str::limit($baseSlug, 180, '');

        $slug = $baseSlug;
        $counter = 1;
        while ($modelClass::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
