<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\BookingReview;
use Illuminate\Http\Request;

class BookingReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = BookingReview::with(['user', 'booking' => fn ($q) => $q->withTrashed()->with(['branch', 'services.employee'])])
            ->orderBy('created_at', 'desc');

        // Filter by approval status
        if ($request->has('filter') && $request->filter !== '') {
            if ($request->filter === 'approved') {
                $query->where('is_approved', 1);
            } elseif ($request->filter === 'pending') {
                $query->where('is_approved', 0);
            }
        }

        $reviews = $query->get();

        // Stats
        $totalReviews = BookingReview::count();
        $approvedCount = BookingReview::where('is_approved', 1)->count();
        $pendingCount = BookingReview::where('is_approved', 0)->count();
        $avgRating = BookingReview::avg('rating');

        return view('backend.booking-reviews.index', compact(
            'reviews', 'totalReviews', 'approvedCount', 'pendingCount', 'avgRating'
        ));
    }

    public function approve($id)
    {
        $review = BookingReview::findOrFail($id);
        $review->is_approved = !$review->is_approved;
        $review->save();

        return response()->json([
            'success' => true,
            'is_approved' => $review->is_approved,
            'message' => $review->is_approved ? 'تم اعتماد التقييم' : 'تم إلغاء اعتماد التقييم',
        ]);
    }

    public function toggleStatus($id)
    {
        $review = BookingReview::findOrFail($id);
        $review->status = !$review->status;
        $review->save();

        return response()->json([
            'success' => true,
            'status' => $review->status,
            'message' => $review->status ? 'تم تفعيل التقييم' : 'تم إخفاء التقييم',
        ]);
    }

    public function destroy($id)
    {
        $review = BookingReview::findOrFail($id);
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التقييم بنجاح',
        ]);
    }
}
