<?php

namespace Modules\Booking\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingTransaction;
use Modules\Booking\Trait\PaymentTrait;
use Modules\Commission\Models\CommissionEarning;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\UserPackage;
use App\Services\Wallet\InsufficientWalletBalance;
use App\Services\Wallet\WalletLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PaymentController extends Controller
{
    use PaymentTrait;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Payment';
    }

    public function savePayment(Request $request)
{
    try {
        $data = $request->all();
        if (filled($request->coupon_code)) return response()->json(['status' => false, 'message' => 'أرسل الكوبون مع طلب الدفع الموحد للتحقق منه وحسابه.'], 422);
        $data['tip_amount'] = 0;

        $booking = Booking::where('id', $data['booking_id'])->first();

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => __('Booking not found.')
            ], 404); // Not Found
        }

        if (($data['transaction_type'] ?? null) == 'wallet') {
            if (!$booking->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => __('User not found.')
                ], 404); // Not Found
            }

            // Only the booking owner (or staff) may pay it from the owner's wallet.
            if ((int) $booking->user_id !== (int) auth()->id() && !(auth()->user()?->can('edit_booking') || auth()->user()?->hasAnyRole(['admin', 'manager', 'employee']))) {
                return response()->json(['status' => false, 'message' => __('wallet_ledger.forbidden')], 403);
            }

            $total_amount = (float) $this->getTotalAmount($data['booking_id'], $data['tax_percentage'] ?? []);

            try {
                $payment = DB::transaction(function () use ($booking, $data, $total_amount) {
                    app(WalletLedger::class)->debit((int) $booking->user_id, $total_amount, 'payment', [
                        'activity_type' => 'debit',
                        'message' => __('messages.wallet_paid') . $booking->id,
                        'source_type' => 'booking',
                        'source_id' => $booking->id,
                        'idempotency_key' => 'payment:booking:' . $booking->id,
                        'meta' => ['transaction_id' => $data['external_transaction_id'] ?? null],
                    ]);

                    return BookingTransaction::create($data);
                });
            } catch (InsufficientWalletBalance $e) {
                return response()->json([
                    'status' => false,
                    'message' => __('Insufficient wallet balance.')
                ], 400); // Bad Request
            }
        } else {
            $payment = BookingTransaction::create($data);
        }

        $earning_data = $this->commissionData($payment);
        $this->storeApiUserPackage($data['booking_id']);

        if (isset($earning_data['commission_data']) && $earning_data['commission_data'] != null) {
            $booking->commission()->save(new CommissionEarning($earning_data['commission_data']));
        }

        return response()->json([
            'message' => __('booking.payment_done'),
            'status' => true
         ], 200); // OK
    } catch (\Exception $e) {
        // Log the exception for debugging purposes
        Log::error('Payment Save Error: ' . $e->getMessage());

        return response()->json([
            'status' => false,
            'message' => __('An error occurred while processing the payment.')
        ], 500); // Internal Server Error
    }
}
}
