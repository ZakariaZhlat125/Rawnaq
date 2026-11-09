<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Booking\Models\Booking;
use Modules\Package\Models\Package;
use Modules\Product\Models\Product;
use Modules\Service\Models\Service;

class CustomerExperienceController extends Controller
{
    private const TYPES = [
        'service' => Service::class,
        'package' => Package::class,
        'product' => Product::class,
    ];

    private function booking(Request $request, $id): Booking
    {
        return Booking::where('created_by', $request->user()->id)
            ->whereNull('deleted_by')
            ->findOrFail($id);
    }

    public function review(Request $request, $id)
    {
        $this->booking($request, $id);

        return response()->json([
            'status' => true,
            'data' => BookingReview::where('booking_id', $id)
                ->where('user_id', $request->user()->id)->first(),
        ]);
    }

    public function saveReview(Request $request, $id)
    {
        $booking = $this->booking($request, $id);
        abort_unless($booking->status === 'completed', 422, 'يمكن تقييم الحجوزات المكتملة فقط');

        $data = $request->validate([
            'branch_rating' => 'required|integer|between:1,5',
            'review_text' => 'nullable|string|max:1000',
            'service_ratings' => 'required|array|min:1',
            'service_ratings.*.booking_service_id' => 'required|integer|distinct',
            'service_ratings.*.service_rating' => 'required|integer|between:1,5',
            'service_ratings.*.employee_rating' => 'nullable|integer|between:1,5',
        ]);

        $services = $booking->services()->get()->keyBy('id');
        abort_unless(count($data['service_ratings']) === $services->count(), 422, 'يرجى تقييم جميع خدمات الحجز');

        // Resolve IDs from the booking; clients cannot nominate another service or employee.
        $data['service_ratings'] = array_map(function ($row) use ($services) {
            $service = $services->get($row['booking_service_id']);
            abort_unless($service, 422, 'الخدمة لا تخص الحجز');
            abort_unless(!$service->employee_id || isset($row['employee_rating']), 422, 'يرجى تقييم الموظف');

            return [
                'booking_service_id' => $service->id,
                'service_id' => $service->service_id,
                'employee_id' => $service->employee_id,
                'service_rating' => (int) $row['service_rating'],
                'employee_rating' => $service->employee_id ? (int) $row['employee_rating'] : null,
            ];
        }, $data['service_ratings']);

        // Keep existing approved-review displays compatible and re-submit edits for approval.
        $data['rating'] = $data['branch_rating'];
        $data['is_approved'] = 0;
        $data['status'] = 1;
        $review = BookingReview::updateOrCreate([
            'booking_id' => $booking->id,
            'user_id' => $request->user()->id,
        ], $data);

        return response()->json(['status' => true, 'data' => $review, 'message' => 'تم حفظ تقييمك بنجاح']);
    }

    public function favorites(Request $request)
    {
        $rows = DB::table('customer_favorites')->where('user_id', $request->user()->id)->latest('id')->get();
        $items = [];
        foreach (self::TYPES as $type => $class) {
            $items[$type] = $class::whereIn('id', $rows->where('type', $type)->pluck('item_id'))->get()->keyBy('id');
        }

        $data = $rows->map(function ($row) use ($items) {
            $item = $items[$row->type]->get($row->item_id);
            if (!$item) {
                return null;
            }
            $name = $item->name;
            if (is_array($name)) {
                $name = $name['ar'] ?? reset($name);
            }

            return [
                'type' => $row->type,
                'item_id' => $row->item_id,
                'name' => $name,
                'image' => $item->feature_image,
                'category_id' => $item->category_id,
            ];
        })->filter()->values();

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function saveFavorite(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'item_id' => 'required|integer|min:1',
        ]);
        $class = self::TYPES[$data['type']];
        $class::findOrFail($data['item_id']);

        // Unique key and insertOrIgnore make repeated clicks safe.
        DB::table('customer_favorites')->insertOrIgnore($data + [
            'user_id' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true]);
    }

    public function deleteFavorite(Request $request, $type, $id)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        DB::table('customer_favorites')->where('user_id', $request->user()->id)
            ->where('type', $type)->where('item_id', $id)->delete();

        return response()->json(['status' => true]);
    }
}
