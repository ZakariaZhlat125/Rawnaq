<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 12), 1), 50);

        $blogs = Blog::published()
            ->latest('published_at')
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => collect($blogs->items())
                ->map(fn (Blog $blog) => $this->transformBlog($blog))
                ->values(),
            'pagination' => [
                'current_page' => $blogs->currentPage(),
                'last_page' => $blogs->lastPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $blog = Blog::published()
            ->where('slug', $slug)
            ->first();

        if (! $blog) {
            return response()->json([
                'status' => false,
                'message' => __('messages.no_record'),
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->transformBlog($blog, true),
        ]);
    }

    private function transformBlog(Blog $blog, bool $includeContent = false): array
    {
        $publishedAt = $blog->published_at ?: $blog->created_at;
        $excerpt = $blog->excerpt ?: Str::limit(strip_tags($blog->content), 160);

        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $excerpt,
            'content' => $includeContent ? $blog->content : null,
            'image' => $blog->image,
            'image_url' => $blog->image_url,
            'published_at' => $publishedAt?->toIso8601String(),
            'published_at_formatted' => $publishedAt?->format('Y-m-d'),
        ];
    }
}
