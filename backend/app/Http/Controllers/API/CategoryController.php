<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of main categories with their subcategories and book counts.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::whereNull('parent_id')
            ->with([
                'children' => fn ($q) => $q->withCount(['books' => fn ($b) => $b->published()]),
            ])
            ->withCount([
                'books' => fn ($q) => $q->published(),
            ])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $categories->count(),
            'data' => $categories,
        ]);
    }

    /**
     * Display a single category with children and published books.
     */
    public function show(string $identifier): JsonResponse
    {
        $category = Category::with([
            'children',
            'books' => fn ($q) => $q->published()->orderBy('title', 'asc'),
        ])->where(function ($q) use ($identifier) {
            $q->where('slug', $identifier);
            if (is_numeric($identifier)) {
                $q->orWhere('id', (int) $identifier);
            }
        })->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'التصنيف غير موجود.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }
}
