<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CafeCategory;
use Illuminate\Http\Request;

class CafeCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_cafe_categories');
    }

    public function index()
    {
        $categories = CafeCategory::withCount('items')->orderBy('sort_order')->latest('id')->paginate(20);

        return view('backend.cafe.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('backend.cafe.categories.create');
    }

    public function store(Request $request)
    {
        CafeCategory::create($this->validatedData($request));

        return redirect()->route('backend.cafe.categories.index')->with('success', __('cafe.saved_successfully'));
    }

    public function edit(CafeCategory $category)
    {
        return view('backend.cafe.categories.edit', compact('category'));
    }

    public function update(Request $request, CafeCategory $category)
    {
        $category->update($this->validatedData($request));

        return redirect()->route('backend.cafe.categories.index')->with('success', __('cafe.saved_successfully'));
    }

    public function destroy(CafeCategory $category)
    {
        $category->delete();

        return redirect()->route('backend.cafe.categories.index')->with('success', __('cafe.deleted_successfully'));
    }

    protected function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['status'] = $request->boolean('status');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
