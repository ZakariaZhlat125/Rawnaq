<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CafeCategory;
use App\Models\CafeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CafeItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_cafe_items');
    }

    public function index()
    {
        $items = CafeItem::with('category')->orderBy('sort_order')->latest('id')->paginate(20);

        return view('backend.cafe.items.index', compact('items'));
    }

    public function create()
    {
        $categories = CafeCategory::active()->orderBy('sort_order')->orderBy('name')->get();

        return view('backend.cafe.items.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['image'] = $this->storeImage($request);

        CafeItem::create($data);

        return redirect()->route('backend.cafe.items.index')->with('success', __('cafe.saved_successfully'));
    }

    public function edit(CafeItem $item)
    {
        $categories = CafeCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('backend.cafe.items.edit', compact('item', 'categories'));
    }

    public function update(Request $request, CafeItem $item)
    {
        $data = $this->validatedData($request);
        $image = $this->storeImage($request, $item);

        if ($image) {
            $data['image'] = $image;
        }

        $item->update($data);

        return redirect()->route('backend.cafe.items.index')->with('success', __('cafe.saved_successfully'));
    }

    public function destroy(CafeItem $item)
    {
        $item->delete();

        return redirect()->route('backend.cafe.items.index')->with('success', __('cafe.deleted_successfully'));
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'cafe_category_id' => ['required', 'exists:cafe_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,unavailable'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    protected function storeImage(Request $request, ?CafeItem $item = null): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        if (! File::exists(public_path('uploads/cafe/items'))) {
            File::makeDirectory(public_path('uploads/cafe/items'), 0777, true);
        }

        if ($item && $item->image && File::exists(public_path($item->image))) {
            File::delete(public_path($item->image));
        }

        $image = $request->file('image');
        $imageName = time().'_'.uniqid().'.'.$image->extension();
        $image->move(public_path('uploads/cafe/items'), $imageName);

        return 'uploads/cafe/items/'.$imageName;
    }
}
