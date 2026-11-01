<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Get all published static pages.
     */
    public function index(): JsonResponse
    {
        $pages = Page::published()
            ->select(['id', 'slug', 'title', 'title_en', 'subtitle', 'subtitle_en', 'meta_title', 'meta_description', 'updated_at'])
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Get a single static page by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'الصفحة المطلوبة غير موجودة أو معطلة حالياً.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }
}
