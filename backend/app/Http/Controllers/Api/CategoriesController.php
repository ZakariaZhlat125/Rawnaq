<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Category\Models\Category;

class CategoriesController extends Controller
{
    public function index()
    {
        $categories = Category::where('status', 1)
            ->whereNull('parent_id')
            ->with(['services' => function ($query) {
                $query->where('status', 1)->with('branches');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(6)
            ->get();
    
        $categories->each(function ($category) {
            $category->setAttribute('page_content', app(\App\Services\CategoryPageContent::class)->get($category));
        });

        return response()->json([
            'status' => true,
            'data' => $categories
        ]);
    }
}
