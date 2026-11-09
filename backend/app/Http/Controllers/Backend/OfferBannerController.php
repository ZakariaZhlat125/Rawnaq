<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Ouroffersection;
use Illuminate\Http\Request;

class OfferBannerController extends Controller
{
    public function index()
    {
        $offers = Ouroffersection::orderBy('created_at', 'desc')->get();
        return view('backend.offer-banners.index', compact('offers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title_ar'       => 'required|string|max:255',
            'title_en'       => 'nullable|string|max:255',
            'desc_ar'        => 'required|string|max:500',
            'desc_en'        => 'nullable|string|max:500',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'color'          => 'nullable|string|max:20',
            'link'           => 'nullable|string|max:500',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'overlay'        => 'nullable',
        ]);

        $data = [
            'title' => [
                'ar' => $request->title_ar,
                'en' => $request->title_en ?? $request->title_ar,
            ],
            'description' => [
                'ar' => $request->desc_ar,
                'en' => $request->desc_en ?? $request->desc_ar,
            ],
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'color'          => $request->color ?? '#1a1a2e',
            'link'           => $request->link,
            'overlay'        => $request->has('overlay') ? 1 : 0,
            'status'         => 1,
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            $img = $request->file('image');
            if (!file_exists(public_path('uploads/offer-banners'))) {
                mkdir(public_path('uploads/offer-banners'), 0777, true);
            }
            $imageName = time() . '_' . uniqid() . '.' . $img->extension();
            $img->move(public_path('uploads/offer-banners'), $imageName);
            $data['image'] = 'uploads/offer-banners/' . $imageName;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $offer = Ouroffersection::create($data);
            \App\Models\User::role('user')->where('status', 1)->where('is_banned', 0)->chunkById(200, function ($users) use ($offer) {
                foreach ($users as $user) {
                    $user->notifications()->create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'type' => 'offer.created',
                        'data' => ['subject' => 'عرض جديد من رونق', 'data' => [
                            'type' => 'عرض جديد', 'message' => 'عرض جديد: '.($offer->title['ar'] ?? '').' — '.($offer->description['ar'] ?? ''),
                            'notification_group' => 'offer', 'id' => $offer->id, 'event' => 'offer.created',
                        ]],
                    ]);
                }
            });
        });

        return redirect()->back()->with('success', 'تم إضافة العرض بنجاح ✅');
    }

    public function update(Request $request, $id)
    {
        $offer = Ouroffersection::findOrFail($id);

        $request->validate([
            'title_ar'       => 'required|string|max:255',
            'title_en'       => 'nullable|string|max:255',
            'desc_ar'        => 'required|string|max:500',
            'desc_en'        => 'nullable|string|max:500',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'color'          => 'nullable|string|max:20',
            'link'           => 'nullable|string|max:500',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'overlay'        => 'nullable',
        ]);

        $data = [
            'title' => [
                'ar' => $request->title_ar,
                'en' => $request->title_en ?? $request->title_ar,
            ],
            'description' => [
                'ar' => $request->desc_ar,
                'en' => $request->desc_en ?? $request->desc_ar,
            ],
            'discount_type'  => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'color'          => $request->color ?? '#1a1a2e',
            'link'           => $request->link,
            'overlay'        => $request->has('overlay') ? 1 : 0,
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($offer->image && file_exists(public_path($offer->image))) {
                unlink(public_path($offer->image));
            }

            $img = $request->file('image');
            if (!file_exists(public_path('uploads/offer-banners'))) {
                mkdir(public_path('uploads/offer-banners'), 0777, true);
            }
            $imageName = time() . '_' . uniqid() . '.' . $img->extension();
            $img->move(public_path('uploads/offer-banners'), $imageName);
            $data['image'] = 'uploads/offer-banners/' . $imageName;
        }

        $offer->update($data);

        return redirect()->back()->with('success', 'تم تحديث العرض بنجاح ✅');
    }

    public function updateStatus(Request $request, $id)
    {
        $offer = Ouroffersection::findOrFail($id);
        $offer->status = $request->status;
        $offer->save();

        return response()->json(['success' => true, 'message' => 'تم تحديث الحالة']);
    }

    public function destroy($id)
    {
        $offer = Ouroffersection::findOrFail($id);

        // Delete image file
        if ($offer->image && file_exists(public_path($offer->image))) {
            unlink(public_path($offer->image));
        }

        $offer->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف العرض بنجاح']);
    }
}
