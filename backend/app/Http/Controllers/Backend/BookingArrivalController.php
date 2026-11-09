<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\BookingArrival;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Modules\Booking\Models\Booking;

class BookingArrivalController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['booking_id' => 'nullable|integer|min:1']);
        $bookings = Booking::with(['user', 'branch'])->when($request->booking_id,
            fn ($query) => $query->where('id', $request->booking_id))->latest('id')->paginate(20)->withQueryString();
        return view('backend.booking-arrival.index', compact('bookings'));
    }

    public function show(Request $request, Booking $booking)
    {
        $booking->load(['user', 'branch', 'booking_service.service', 'arrivalConfirmedBy', 'payment', 'transactions']);

        $isPaid = (bool) (
            $booking->payment_status === 1 ||
            ($booking->payment && $booking->payment->payment_status === 'paid') ||
            $booking->transactions()->where('payment_status', 1)->exists()
        );

        $expiresAt = $booking->start_date_time
            ? \Carbon\Carbon::parse($booking->start_date_time)->endOfDay()
            : now()->endOfDay();

        $qrUrl = $isPaid ? URL::temporarySignedRoute('backend.booking-arrival.scan', $expiresAt, ['booking' => $booking->id]) : null;
        $fromScan = request()->routeIs('backend.booking-arrival.scan');

        $viewer = $request->user();
        $userBranches = $viewer ? $viewer->branches->pluck('id')->all() : [];
        $branchMismatch = !empty($userBranches) && !in_array((int) $booking->branch_id, $userBranches);

        return view('backend.booking-arrival.show', compact('booking', 'qrUrl', 'fromScan', 'isPaid', 'branchMismatch'));
    }

    public function update(Request $request, Booking $booking, BookingArrival $arrival)
    {
        $data = $request->validate(['arrived' => 'required|boolean', 'source' => 'required|in:manual,qr']);
        if ($data['source'] === 'qr') {
            // A QR submission must carry the same valid signed URL used to open the scan page.
            abort_unless($request->hasValidSignature(), 403);
        }
        $arrival->update($booking->id, (bool) $data['arrived'], $request->user()->id, $data['source']);
        return back()->with('success', $data['arrived'] ? 'تم تأكيد وصول العميل.' : 'تم إلغاء تأكيد الوصول.');
    }
}
