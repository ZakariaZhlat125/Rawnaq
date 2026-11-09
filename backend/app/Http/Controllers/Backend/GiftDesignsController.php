<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\GiftDesign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GiftDesignsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_gift_designs');
    }

    public function index()
    {
        $designs = GiftDesign::orderBy('sort_order')->orderBy('id')->get();
        $usage = GiftCard::selectRaw('design, COUNT(*) c')->groupBy('design')->pluck('c', 'design');

        return view('backend.gift.designs', compact('designs', 'usage'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($request->input('slug') ?: ($data['name_en'] ?? $data['name_ar']));
        GiftDesign::create($data);

        return back()->with('success', __('gift_system.admin.saved'));
    }

    public function update(Request $request, GiftDesign $design)
    {
        $data = $this->validated($request, $design);
        if ($request->boolean('remove_image') && $design->image_path) {
            Storage::disk('public')->delete($design->image_path);
            $data['image_path'] = null;
        }
        $design->update($data);

        return back()->with('success', __('gift_system.admin.saved'));
    }

    /** Designs used by existing gifts are only switched off, so those gifts keep rendering. */
    public function destroy(GiftDesign $design)
    {
        if (GiftCard::where('design', $design->slug)->exists()) {
            $design->update(['is_active' => false]);
        } else {
            if ($design->image_path) {
                Storage::disk('public')->delete($design->image_path);
            }
            $design->delete();
        }

        return back()->with('success', __('gift_system.admin.saved'));
    }

    private function validated(Request $request, ?GiftDesign $design = null): array
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'occasion' => ['nullable', Rule::in(['general', 'birthday', 'eid', 'ramadan', 'national_day', 'wedding', 'christmas', 'thanks'])],
            // Only gradients/colours: the value is injected into a style attribute on the site.
            'background' => ['required', 'string', 'max:600', 'regex:/^[#(),.%\s\w-]+$/'],
            'foreground' => ['required', 'regex:/^#[0-9A-Fa-f]{3,8}$/'],
            'icon' => 'nullable|string|max:4',
            'sort_order' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            if ($design?->image_path) {
                Storage::disk('public')->delete($design->image_path);
            }
            $data['image_path'] = $request->file('image')->store('gift-designs', 'public');
        }
        unset($data['image']);

        return $data;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'design';
        $slug = Str::limit($slug, 40, '');
        $candidate = $slug;
        for ($i = 2; GiftDesign::where('slug', $candidate)->exists(); $i++) {
            $candidate = $slug.'-'.$i;
        }

        return $candidate;
    }
}
