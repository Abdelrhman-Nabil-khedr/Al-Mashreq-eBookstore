<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of books with search, filters, and optional pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Book::published()
            ->with([
                'author:id,name,slug,biography',
                'publisher:id,name,slug,url',
                'category:id,name,slug,parent_id',
                'subCategory:id,name,slug,parent_id',
                'platformLinks',
                'bisacCodes',
                'keywords',
            ]);

        // 1. Full-text / keyword search
        if ($request->filled('q')) {
            $query->search($request->input('q'));
        }

        // 2. Author filter (by ID, slug, or name)
        if ($request->filled('author')) {
            $authorParam = trim($request->input('author'));
            $query->whereHas('author', function ($q) use ($authorParam) {
                if (is_numeric($authorParam)) {
                    $q->where('id', (int) $authorParam);
                } else {
                    $q->where('slug', $authorParam)
                      ->orWhere('name', $authorParam)
                      ->orWhere('name', 'like', "%{$authorParam}%");
                }
            });
        }

        // 3. Main Category filter
        if ($request->filled('category')) {
            $catParam = trim($request->input('category'));
            $query->whereHas('category', function ($q) use ($catParam) {
                if (is_numeric($catParam)) {
                    $q->where('id', (int) $catParam);
                } else {
                    $q->where('slug', $catParam)
                      ->orWhere('name', $catParam)
                      ->orWhere('name', 'like', "%{$catParam}%");
                }
            });
        }

        // 4. SubCategory filter
        if ($request->filled('subcategory')) {
            $subParam = trim($request->input('subcategory'));
            $query->whereHas('subCategory', function ($q) use ($subParam) {
                if (is_numeric($subParam)) {
                    $q->where('id', (int) $subParam);
                } else {
                    $q->where('slug', $subParam)
                      ->orWhere('name', $subParam)
                      ->orWhere('name', 'like', "%{$subParam}%");
                }
            });
        }

        // 5. Language filter
        if ($request->filled('language')) {
            $query->language($request->input('language'));
        }

        // 6. Featured filter
        if ($request->has('featured')) {
            if ($request->boolean('featured')) {
                $query->featured();
            }
        }

        // 7. Sorting
        $sort = $request->input('sort', 'default');
        switch ($sort) {
            case 'az':
                $query->orderBy('title', 'asc');
                break;
            case 'za':
                $query->orderBy('title', 'desc');
                break;
            case 'author':
                $query->join('authors', 'books.author_id', '=', 'authors.id')
                    ->orderBy('authors.name', 'asc')
                    ->orderBy('books.title', 'asc')
                    ->select('books.*');
                break;
            case 'lang':
                $query->orderBy('language', 'asc')->orderBy('title', 'asc');
                break;
            case 'latest':
                $query->orderBy('id', 'desc');
                break;
            default:
                // Default: Priority ordering (featured first, then latest)
                $query->orderBy('is_featured', 'desc')->orderBy('id', 'desc');
                break;
        }

        $allRequested = $request->boolean('all') || $request->input('per_page') === 'all';
        $hasPagination = $request->has('page') || ($request->has('per_page') && !$allRequested);

        if ($hasPagination) {
            $perPage = max(1, min((int) $request->input('per_page', 24), 100));
            $paginator = $query->paginate($perPage);
            $transformed = collect($paginator->items())->map(fn (Book $b) => $this->transformBook($b));

            return response()->json([
                'success' => true,
                'count' => $paginator->total(),
                'total' => $paginator->total(),
                'data' => $transformed,
                'books' => $transformed,
                'generated_at' => now()->toIso8601String(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        }

        // Return all matching books (ideal for instant frontend client-side filtering)
        $books = $query->get();
        $transformed = $books->map(fn (Book $b) => $this->transformBook($b));

        return response()->json([
            'success' => true,
            'count' => $books->count(),
            'total' => $books->count(),
            'data' => $transformed,
            'books' => $transformed,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Display a single book by ISBN or ID.
     */
    public function show(string $identifier): JsonResponse
    {
        $book = Book::published()
            ->with([
                'author',
                'publisher',
                'category',
                'subCategory',
                'platformLinks',
                'bisacCodes',
                'keywords',
            ])
            ->where(function ($q) use ($identifier) {
                $q->where('isbn', $identifier);
                if (is_numeric($identifier)) {
                    $q->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'الكتاب المطلوب غير موجود أو غير منشور حالياً.',
            ], 404);
        }

        $transformed = $this->transformBook($book);

        return response()->json([
            'success' => true,
            'data' => $transformed,
            'book' => $transformed,
        ]);
    }

    /**
     * Transform a Book model instance into a rich API and legacy-compatible dictionary.
     */
    protected function transformBook(Book $book): array
    {
        $apiHost = request()->getSchemeAndHttpHost();

        // Resolve cover URL
        $cover = $book->cover_url;
        if (!empty($cover)) {
            if (!str_starts_with($cover, 'http')) {
                // If relative path like /storage/... or /covers/...
                $cover = $apiHost . '/' . ltrim($cover, '/');
            }
        } else {
            $cover = "/covers/{$book->isbn}.jpg";
        }

        $links = $book->platformLinks->map(fn ($link) => [
            'name' => $link->platform_name,
            'url' => $link->url,
        ])->values()->all();

        $primaryLink = count($links) ? $links[0]['url'] : '';

        $keywordsList = $book->keywords->pluck('keyword')->all();
        $keywordsStr = implode(', ', $keywordsList);

        $bisacList = $book->bisacCodes->pluck('code')->all();
        $bisacStr = implode(', ', $bisacList);

        // Build full search string for instant frontend filtering
        $searchVector = trim(implode(' ', array_filter([
            $book->isbn,
            $book->title,
            $book->subtitle,
            $book->series,
            $book->author?->name,
            $book->publisher?->name,
            $book->category?->name,
            $book->subCategory?->name,
            $book->language,
            $book->age_range,
            $book->contributor_name,
            $keywordsStr,
            $book->synopsis,
        ])));

        return [
            // Core IDs
            'id' => $book->id,
            '_id' => $book->id,
            'isbn' => $book->isbn,
            'ISBN' => $book->isbn,

            // Titles
            'title' => $book->title,
            'Title' => $book->title,
            'subtitle' => $book->subtitle,
            'SubTitle' => $book->subtitle,
            'series' => $book->series,
            'Series' => $book->series,

            // Content & Classification
            'synopsis' => $book->synopsis,
            'Synopsis' => $book->synopsis,
            'Description' => $book->synopsis,
            'pages' => $book->pages,
            'Pages' => $book->pages !== null ? (string) $book->pages : '',
            'language' => $book->language,
            'Language' => $book->language ?: 'Arabic',
            'age_range' => $book->age_range,
            'Age' => $book->age_range,

            // Media
            'cover_path' => $book->cover_path,
            'cover_url' => $cover,
            'Cover' => $cover,

            // Author Relationship
            'author_id' => $book->author_id,
            'author' => $book->author ? [
                'id' => $book->author->id,
                'name' => $book->author->name,
                'slug' => $book->author->slug,
                'biography' => $book->author->biography,
            ] : null,
            'Author' => $book->author?->name ?? '—',

            // Publisher Relationship
            'publisher_id' => $book->publisher_id,
            'publisher' => $book->publisher ? [
                'id' => $book->publisher->id,
                'name' => $book->publisher->name,
                'slug' => $book->publisher->slug,
                'url' => $book->publisher->url,
            ] : null,
            'Publisher' => $book->publisher?->name ?? '',

            // Category Relationships
            'category_id' => $book->category_id,
            'category' => $book->category ? [
                'id' => $book->category->id,
                'name' => $book->category->name,
                'slug' => $book->category->slug,
            ] : null,
            'Category' => $book->category?->name ?? '',
            'CategoryMain' => $book->category?->name ?? '',

            'sub_category_id' => $book->sub_category_id,
            'sub_category' => $book->subCategory ? [
                'id' => $book->subCategory->id,
                'name' => $book->subCategory->name,
                'slug' => $book->subCategory->slug,
            ] : null,
            'Subject_2' => $book->subCategory?->name ?? '',

            // Contributors
            'contributor_name' => $book->contributor_name,
            'Contributor' => $book->contributor_name,
            'contributor_role' => $book->contributor_role,
            'Contributor Role' => $book->contributor_role,
            'illustrator' => $book->illustrator,
            'Illustrator' => $book->illustrator,
            'translator' => $book->translator,
            'Translator' => $book->translator,
            'editor' => $book->editor,
            'Editor' => $book->editor,

            // Publication, Format & Classification Codes
            'series_position' => $book->series_position,
            'SeriesPosition' => $book->series_position,
            'format' => $book->format,
            'Format' => $book->format,
            'publication_date' => $book->publication_date ? $book->publication_date->format('Y-m-d') : null,
            'PublicationDate' => $book->publication_date ? $book->publication_date->format('d/m/Y') : null,
            'original_language' => $book->original_language,
            'OriginalLanguage' => $book->original_language,
            'author_bio' => $book->author_bio,
            'AuthorBio' => $book->author_bio,
            'age_group' => $book->age_range,
            'AgeGroup' => $book->age_range,
            'bisac_code' => $book->bisac_code,
            'wgs_code' => $book->wgs_code,
            'thema_code' => $book->thema_code,
            'catalog_code' => $book->catalog_code,

            // Purchase Links
            'links' => $links,
            'Links' => $links,
            'Link' => $primaryLink,

            // BISAC & Keywords
            'bisac_codes' => $bisacList,
            'Bisac' => $bisacStr,
            'keywords' => $keywordsList,
            'Keywords' => $keywordsStr,

            // Flags
            'is_featured' => (bool) $book->is_featured,
            'is_published' => (bool) $book->is_published,

            // SEO URLs & Search
            'book_url' => "/book/{$book->isbn}",
            '_search' => $searchVector,
        ];
    }
}
