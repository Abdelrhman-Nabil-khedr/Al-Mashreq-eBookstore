<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    /**
     * Display a listing of authors with book counts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Author::query()->withCount([
            'books' => fn ($q) => $q->published(),
        ]);

        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where('name', 'like', "%{$term}%")
                  ->orWhere('biography', 'like', "%{$term}%");
        }

        $authors = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'count' => $authors->count(),
            'data' => $authors,
        ]);
    }

    /**
     * Display a single author and their published books.
     */
    public function show(string $identifier): JsonResponse
    {
        $author = Author::with([
            'books' => fn ($q) => $q->published()->orderBy('title', 'asc'),
        ])->where(function ($q) use ($identifier) {
            $q->where('slug', $identifier);
            if (is_numeric($identifier)) {
                $q->orWhere('id', (int) $identifier);
            }
        })->first();

        if (!$author) {
            return response()->json([
                'success' => false,
                'message' => 'المؤلف غير موجود.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $author,
        ]);
    }
}
