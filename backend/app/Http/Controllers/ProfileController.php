<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Modules\Wallet\Models\Wallet;
use Modules\Booking\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\reject;
use Modules\Promotion\Models\Coupon;
use App\Models\GiftCard;

class ProfileController extends Controller
{
    public function profile()
    {
        $user = auth()->user();

        $baseQuery = Booking::with('service.service', 'service.employee', 'bookingTransaction')->whereHas('service')->where('created_by', $user->id)->whereNull('deleted_by');

        $completedGift = GiftCard::where('user_id', $user->id)->count();

        $completed = (clone $baseQuery)->paid()->where('status', 'completed')->count();

        $pending = (clone $baseQuery)->whereNotIn('status', ['completed', 'canceled'])->whereNull('deleted_by')->count();

        $coupons = Coupon::with('promotion')->where('is_expired', 0)->where('use_limit', '>=', 1)->count();


        $balance = app(\App\Services\Wallet\WalletLedger::class)->spendable($user->id);
        $referralBalance = optional($user->affiliate)->wallet_available ?? 0.00;
        $points = LoyaltyPoint::where('user_id', $user->id)->value('points') ?? 0;


        $bookings = Booking::with('service.service', 'bookingTransaction')->where('created_by', $user->id)->whereHas('services')->whereNull('deleted_by')->get();

        return view('frontend.profile.index', compact('user', 'balance', 'referralBalance', 'points', 'bookings', 'pending', 'completed', 'coupons', 'completedGift'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'mobile'         => 'required|string|max:20|unique:users,mobile,' . $id,
            'email'          => 'required|email|max:255|unique:users,email,' . $id,
            'address'        => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:255',
            'country'        => 'nullable|string|max:255',
            'date_of_birth'  => 'nullable|date|before:today',
            'profile_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data = [];

        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $imageName = 'user_' . $id . '_' . time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('profile_images'), $imageName);
            $data['avatar'] = 'profile_images/' . $imageName;
        } else {
            $data['avatar'] = auth()->user()->avatar;
        }


        $data['first_name']    = $request->first_name;
        $data['last_name']     = $request->last_name;
        $data['email']         = $request->email;
        $data['mobile']        = $request->mobile;
        $data['date_of_birth'] = $request->date_of_birth;
        $data['address']       = $request->address;
        $data['city']          = $request->city;
        $data['country']       = $request->country;

        User::where('id', $id)->update($data);

        return redirect()->back()->with('success', __('messages.profile_updated'));
    }

    public function coupon()
    {
        $coupons = Coupon::with('promotion')->where('is_expired', 0)->where('use_limit', '>=', 1)->get();

        return view('frontend.profile.coupons', compact('coupons'));
    }

    public function myBookings()
    {
        $reasons = reject::all();
        $bookings = Booking::with('service.service', 'service.employee')->where('created_by', auth()->user()->id)->whereNull('deleted_by')->whereNotIn('status', ['completed', 'canceled'])->get();
        $today = now()->toDateString();
        $gifts = GiftCard::where('user_id', auth()->id())->whereDate('created_at', '>=', $today)->where('payment_status', 1)->get();
        return view('frontend.profile.bookings.current', compact('bookings', 'reasons', 'gifts'));
    }

    public function destroy_myBooking(Request $request, $id)
    {
        $booking = Booking::find($id);
        $booking->delete();
        $reasons = $request->input('reasons', []);
        foreach ($reasons as $reasonId) {
            $reason = reject::find($reasonId);
            if ($reason) {
                $reason->increment('count');
            }
        }
        return response()->json(['success' => true, 'message' => __('messagess.item_removed_from_cart')]);
    }

    public function complateBookings()
    {
        $bookings = Booking::with('service.service', 'service.employee', 'bookingTransaction')
            ->where('created_by', auth()->user()->id)
            ->whereNull('deleted_by')
            ->paid()
            ->where('status', '=', 'completed')
            ->get();
        return view('frontend.profile.bookings.completed', compact('bookings'));
    }

    public function complateGift()
    {
        $gifts = GiftCard::where('user_id', auth()->id())->get();
        return view('frontend.profile.bookings.completed-gifts', compact('gifts'));
    }
}
