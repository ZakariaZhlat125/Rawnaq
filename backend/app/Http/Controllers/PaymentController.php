<?php

namespace App\Http\Controllers;

use App\Services\CartExpirationService;
use App\Services\Payment\PaymentOrchestratorService;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Package\Models\BookingPackages;
use Modules\Product\Models\Cart;
use App\Models\GiftCard;
use Modules\Wallet\Models\Wallet;
use App\Models\LoyaltyPoint;
use App\Models\Setting;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        return redirect('/app');

        $userId = auth()->id();
        $typePage = $request->has('is_buy_now') ? 'payment' : 'cart';

        app(CartExpirationService::class)->clearExpired($userId);

        $cartProduct = collect();
        $productPrice = $productCount = $giftPrice = $giftCount  = $packagePrice = $packageCount  = 0;

        $cartServiceQuery = Booking::with('service.service', 'branch:id,name,description')
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('payment_type', $typePage === 'payment' ? 'payment' : 'cart')
            ->unpaid()
            ->where('created_by', $userId)
            ->whereNull('deleted_by');

        $cartService = $cartServiceQuery->get();

        if ($typePage === 'cart') {
            $cartProduct = Cart::with('product')->where('user_id', $userId)->get();

            $gifts = GiftCard::where('user_id', $userId)
                ->where('payment_status', 0)
                ->get();

            $bookingPackages = BookingPackages::whereHas('booking', function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->where('payment_type', 'cart')
                    ->unpaid()
                    ->whereNull('deleted_by');
            })->with('package')->get();

            $productPrice = $cartProduct->sum(fn($item) => ($item->product->max_price ?? $item->product->min_price ?? 0) * ($item->qty ?? 1));
            $packagePrice = $bookingPackages->sum(fn($item) => $item->package ? ($item->package->package_price ?? 0) : 0);
            $giftPrice = $gifts->sum(fn($g) => $g->subtotal ?? 0);

            $productCount = $cartProduct->count();
            $giftCount = $gifts->count();
            $packageCount = $bookingPackages->count();
        }

        $servicePrice = $cartService->sum(fn($item) => $item->service ? ($item->service->service_price ?? 0) : 0);
        $serviceCount = $cartService->sum(fn($item) => $item->service ? 1 : 0);

        $cartTotal = $productPrice + $packagePrice + $giftPrice + $servicePrice;

        $discountTotal = $cartService->sum(fn($item) => $item->services->sum(fn($s) => $s->discount_amount ?? 0));

        $ratePerPoint = Setting::get('point_value') ?? 0.5;
        $loyalty = LoyaltyPoint::where('user_id', $userId)->first();
        $loyaltyBalance = ($loyalty->points ?? 0) * $ratePerPoint;

        $branches = $cartService->map(fn($item) => [
            'branch_id' => $item->branch_id,
            'branch_name' => $item->branch?->name ?? 'غير محدد',
            'branch_description' => $item->branch?->description ?? '',
        ])->unique('branch_id')->values();

        $itemsCount = $serviceCount + $productCount + $giftCount + $packageCount;
        $totalPrice = $cartTotal - $discountTotal;
        $productsAmount = $productPrice;
        $wallet = app(\App\Services\Wallet\WalletLedger::class)->spendable($userId);
        $loyaltyBalance = $loyaltyBalance ?? 0;
        $codDepositPercent = (float) \App\Models\Setting::get('cod_deposit_percent', 30);
        $codDepositPercent = max(0, min(100, $codDepositPercent));
        $codDepositPercentText = rtrim(rtrim(number_format($codDepositPercent, 2, '.', ''), '0'), '.');

        return view('frontend::payment', compact(
            'cartService',
            'cartProduct',
            'packageCount',
            'discountTotal',
            'serviceCount',
            'productCount',
            'productPrice',
            'giftCount',
            'wallet',
            'loyaltyBalance',
            'branches', 
            'itemsCount',
            'totalPrice',
            'productsAmount',
            'codDepositPercent',
            'codDepositPercentText'
        ));
    }
    public function payment(Request $request)
    {
        $result = app(PaymentOrchestratorService::class)->initiate([
            'isBuyNow' => $request->is_buy_now ?? false,
            'gateway' => $request->paymentMethod,
            'coupon_code' => $request->invoiceCopon ?? null,
            'wallet' => (bool) $request->wallet,
            'wallet_amount' => $request->wallet_amount,
            'loyalty' => (bool) $request->loyalty,
            'loyalty_points' => $request->loyalty_points,
            'payment_source' => $request->payment_source ?? 'src_card',
            'channel' => 'web',
        ]);

        if (($result['status'] ?? '') === 'redirect' && !empty($result['payment_url'])) {
            return redirect()->away($result['payment_url']);
        }

        if (($result['status'] ?? '') === 'paid') {
            return redirect('/app/invoice')->with('success', __('messages.payment_success'));
        }

        return redirect()->back()->with('error', $result['message'] ?? __('messages.payment_failed'));
    }
}
