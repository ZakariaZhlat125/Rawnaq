<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\User;
use App\Services\Gifts\GiftCodeGenerator;
use App\Services\Gifts\GiftRedemptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Modules\Booking\Models\Booking;

/** Reception: look up a code, pick the customer (and booking for service gifts), apply. */
class GiftBranchRedeemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:redeem_gift_branch');
    }

    public function index(Request $request)
    {
        $gift = null;
        $customer = null;
        $bookings = collect();
        $error = null;

        if ($request->filled('code')) {
            $key = 'gift-branch-lookup:'.auth()->id();
            if (RateLimiter::tooManyAttempts($key, 30)) {
                $error = __('gift_system.too_many_attempts', ['minutes' => ceil(RateLimiter::availableIn($key) / 60)]);
            } else {
                RateLimiter::hit($key, 600);
                $codes = app(GiftCodeGenerator::class);
                $gift = $codes->looksValid($request->code)
                    ? GiftCard::with(['user', 'recipient', 'branch'])->where('redeem_code_hash', $codes->hash($request->code))->first()
                    : null;
                $error = $gift ? null : __('gift_system.invalid_code');
            }
        }

        if ($gift && $request->filled('customer')) {
            $term = trim($request->customer);
            $digits = substr(preg_replace('/\D/', '', $term), -9);
            $customer = User::query()
                ->where(fn ($q) => $q->where('email', $term)
                    ->when(strlen($digits) === 9, fn ($d) => $d->orWhere('mobile', 'like', '%'.$digits))
                    ->when(ctype_digit($term), fn ($i) => $i->orWhere('id', (int) $term)))
                ->first();
            if ($customer && ! $gift->isValue()) {
                $bookings = Booking::where('user_id', $customer->id)
                    ->whereNotIn('status', ['cancelled', 'canceled', 'completed'])
                    ->whereDoesntHave('transactions', fn ($t) => $t->where('payment_status', 1))
                    ->with('services')
                    ->orderBy('start_date_time')
                    ->limit(20)
                    ->get();
            }
        } elseif ($gift && $gift->recipient_user_id) {
            $customer = $gift->recipient;
        }

        return view('backend.gift.branch', compact('gift', 'customer', 'bookings', 'error'));
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'customer_id' => 'required|integer|exists:users,id',
            'booking_id' => 'nullable|integer|exists:bookings,id',
        ]);
        $customer = User::findOrFail($data['customer_id']);
        $booking = isset($data['booking_id']) ? Booking::find($data['booking_id']) : null;

        try {
            $gift = app(GiftRedemptionService::class)->redeemAtBranch($data['code'], $customer, auth()->id(), $booking);
        } catch (ValidationException $e) {
            return back()->withInput()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('gifts.show', $gift->id)->with('success', $gift->isValue()
            ? __('gift_system.added_to_wallet', ['amount' => $gift->value_amount])
            : __('gift_system.admin.applied_to_booking', ['booking' => $booking?->id]));
    }
}
