<?php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\DB;
use App\Services\Wallet\InsufficientWalletBalance;
use App\Services\Wallet\WalletLedger;
use App\Services\Wallet\WalletTopupService;
use Modules\Booking\Models\BookingTransaction;

class PaymentOrchestratorService
{
    public function initiate(array $input): array
    {
        $input['coupon_code'] = trim((string) ($input['coupon_code'] ?? ''));
        if ($input['coupon_code'] === '') return $this->initiateCheckout($input);
        return DB::transaction(function () use ($input) {
            $coupon = \Modules\Promotion\Models\Coupon::where('coupon_code', $input['coupon_code'])->lockForUpdate()->first();
            if (!$coupon) return ['status' => 'error', 'message' => 'كود الخصم غير صالح.'];
            // Pending gateways hold capacity until a verified success, failure or cancellation.
            $pending = PaymentAttempt::where('coupon_code', $input['coupon_code'])
                ->whereIn('status', [PaymentAttempt::STATUS_INITIATED, PaymentAttempt::STATUS_REDIRECTED])->count();
            if ($coupon->use_limit <= $pending) return ['status' => 'error', 'message' => 'لا توجد استخدامات متاحة للكوبون حالياً؛ توجد عمليات دفع قيد التنفيذ.'];
            return $this->initiateCheckout($input);
        });
    }

    private function initiateCheckout(array $input): array
    {
        $userId = auth()->id();

        // Determine page type based on input ( buy-now , cart )
        $pageType = app(CheckoutTypeResolver::class)->resolve($input);
        $gateway = $input['gateway'] ?? '';
        $couponCode = $input['coupon_code'] ?? null;
        if (!$userId) {
            return ['status' => 'error', 'message' => __('auth.unauthenticated')];
        }

        // Cash on delivery is built in; every other gateway must be active in /app/payment-gateways.
        if ($gateway !== 'cod' && ! app(PaymentGatewayManager::class)->isAvailable($gateway)) {
            return ['status' => 'error', 'message' => __('messages.invalid_payment_method')];
        }

        $checkout = app(PaymentCalculatorService::class)->calculateTotal($pageType, $couponCode);

        if (isset($checkout['error'])) {
            return ['status' => 'error', 'message' => $checkout['error']];
        }

        $grossAmount = (float) ($checkout['total'] ?? 0);
        $taxAmount = (float) ($checkout['tax'] ?? 0);
        $discountAmount = (float) ($checkout['discountAmount'] ?? 0);

        if ($gateway === 'cod') {
            return $this->handleCod($userId, $pageType, $grossAmount, $taxAmount, $discountAmount, $checkout, $couponCode);
        }

        $submethods = [
            'wallet' => (bool) ($input['wallet'] ?? false),
            'wallet_amount' => isset($input['wallet_amount']) ? max(0, (float) $input['wallet_amount']) : null,
            'loyalty' => (bool) ($input['loyalty'] ?? false),
            'loyalty_points' => isset($input['loyalty_points']) ? max(0, (int) $input['loyalty_points']) : null,
        ];
        $subResult = app(PaymentSubMethodsService::class)->apply($userId, new Request($submethods), $grossAmount, false);
        if (isset($subResult['error'])) {
            return ['status' => 'error', 'message' => $subResult['error']];
        }

        $remainingAmount = (float) ($subResult['remaining_amount'] ?? 0);

        if ($remainingAmount <= 0) {
            $invoiceId = $this->finalize( $userId, $grossAmount, $taxAmount, $discountAmount, $pageType, $checkout['cart_ids'] ?? [], $checkout['gift_ids'] ?? [], $this->resolvePaymentMethod($gateway, true), $couponCode, $submethods );

            return [
                'status' => 'paid',
                'invoice_id' => $invoiceId,
                'amount' => 0,
            ];
        }

        $attempt = PaymentAttempt::create([
            'token' => (string) Str::uuid(),
            'user_id' => $userId,
            'gateway' => $gateway,
            'isBuyNow' => $pageType ? str_contains($pageType, 'buy_now') : false,
            'currency' => 'SAR',
            'gross_amount' => $grossAmount,
            'amount' => $remainingAmount,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'coupon_code' => $couponCode,
            'cart_ids' => $checkout['cart_ids'] ?? [],
            'gift_ids' => $checkout['gift_ids'] ?? [],
            'submethods' => $submethods,
            'status' => PaymentAttempt::STATUS_INITIATED,
            'meta' => [
                'channel' => $input['channel'] ?? 'web',
                'payment_source' => $input['payment_source'] ?? null,
            ],
        ]);

        $customer = $this->buildCustomer($userId);
        $urls = $this->buildGatewayUrls($attempt);
        $gatewayResult = $this->createGatewayPayment($attempt, $customer, $urls, $input);

        $attempt->update([
            'external_id' => $gatewayResult['external_id'] ?? null,
            'payment_url' => $gatewayResult['payment_url'] ?? null,
            'status' => PaymentAttempt::STATUS_REDIRECTED,
            'meta' => array_merge($attempt->meta ?? [], [
                'gateway_response' => $gatewayResult['raw'] ?? null,
            ]),
        ]);

        return [
            'status' => 'redirect',
            'payment_url' => $attempt->payment_url,
            'attempt_token' => $attempt->token,
            'attempt_id' => $attempt->id,
            'amount' => $attempt->amount,
        ];
    }

    public function handleCallback(string $gateway, string $token, Request $request): array
    {
        return DB::transaction(function () use ($gateway, $token, $request) {
            PaymentAttempt::where('token', $token)->lockForUpdate()->first();
            return $this->processCallback($gateway, $token, $request);
        });
    }

    private function processCallback(string $gateway, string $token, Request $request): array
    {
        $attempt = PaymentAttempt::where('token', $token)->first();

        if (! $attempt || $attempt->gateway !== $gateway) {
            return ['status' => 'failed', 'message' => __('Payment attempt not found')];
        }

        if ($attempt->status === PaymentAttempt::STATUS_PAID) {
            return ['status' => 'paid', 'invoice_id' => $attempt->invoice_id];
        }

        if ($attempt->purpose === PaymentAttempt::PURPOSE_WALLET_TOPUP) {
            return $this->handleTopupCallback($attempt, $request);
        }

        $verification = $this->verifyGatewayPayment($attempt, $request);

        if (($verification['external_id'] ?? null) && ! $attempt->external_id) {
            $attempt->external_id = $verification['external_id'];
        }

        $status = $verification['status'] ?? 'failed';

        if ($status === 'paid') {
            $invoiceId = $this->finalize(
                $attempt->user_id,
                (float) $attempt->gross_amount,
                (float) $attempt->tax_amount,
                (float) $attempt->discount_amount,
                $attempt->page_type ?? CheckoutType::CART,
                $attempt->cart_ids ?? [],
                $attempt->gift_ids ?? [],
                $this->resolvePaymentMethod($gateway, false),
                $attempt->coupon_code ?? '',
                $attempt->submethods ?? [],
                (float) $attempt->amount
            );

            $attempt->update([
                'status' => PaymentAttempt::STATUS_PAID,
                'invoice_id' => $invoiceId,
                'meta' => array_merge($attempt->meta ?? [], [
                    'verify_response' => $verification['raw'] ?? null,
                ]),
            ]);

            return ['status' => 'paid', 'invoice_id' => $invoiceId];
        }

        $attempt->update([
            'status' => $status === 'cancelled'
                ? PaymentAttempt::STATUS_CANCELLED
                : PaymentAttempt::STATUS_FAILED,
            'meta' => array_merge($attempt->meta ?? [], [
                'verify_response' => $verification['raw'] ?? null,
            ]),
        ]);

        $this->cleanupFailedCartBookings($attempt);

        return ['status' => $status];
    }

    private function handleCod(int $userId, string $pageType, float $grossAmount, float $taxAmount, float $discountAmount, array $checkout, ?string $couponCode ): array {
        $codDepositPercent = (float) Setting::get('cod_deposit_percent', 30);
        $codDepositPercent = max(0, min(100, $codDepositPercent));
        $requiredDeposit = round($grossAmount * ($codDepositPercent / 100), 2);

        try {
            $invoiceId = null;

            DB::transaction(function () use ( $userId, $pageType, $grossAmount, $taxAmount, $discountAmount, $checkout, $couponCode, $requiredDeposit, $codDepositPercent, &$invoiceId ) {
                $insufficient = fn () => new \RuntimeException(__('messagess.wallet_balance_requirement', [
                    'percent' => rtrim(rtrim(number_format($codDepositPercent, 2, '.', ''), '0'), '.'),
                ]));
                $ledger = app(WalletLedger::class);

                if ($requiredDeposit > 0 && $ledger->spendable($userId) + 0.0001 < $requiredDeposit) {
                    throw $insufficient();
                }

                // Take the deposit before finalizing (finalizing sends the success SMS),
                // then link the ledger row to the invoice it paid.
                $deposit = null;
                if ($requiredDeposit > 0) {
                    try {
                        $deposit = $ledger->debit($userId, $requiredDeposit, 'payment', [
                            'activity_type' => 'withdraw',
                            'message' => __('messagess.cash_on_delivery'),
                            'source_type' => 'invoice',
                            'meta' => ['transaction_type' => 'cash_on_delivery'],
                        ]);
                    } catch (InsufficientWalletBalance) {
                        throw $insufficient();
                    }
                }

                $invoiceId = app(PaymentFinalizerService::class)->finalizePayment($userId,$grossAmount,$taxAmount,$discountAmount,$pageType,$checkout['cart_ids'] ?? [],$checkout['gift_ids'] ?? [],'cash on delivery',$couponCode ?? '',false);

                $deposit?->update(['source_id' => $invoiceId, 'idempotency_key' => 'payment:invoice:' . $invoiceId]);
                \App\Models\Invoice::findOrFail($invoiceId)->update(['collection_details' => ['gateway' => 0, 'wallet' => $requiredDeposit, 'loyalty' => 0]]);

                BookingTransaction::where('external_transaction_id', 'INV-' . $invoiceId)->update(['transaction_type' => 'cash_on_delivery_deposit_percent']);
            });

            PaymentAttempt::create([
                'token' => (string) Str::uuid(),
                'user_id' => $userId,
                'gateway' => 'cod',
                'isBuyNow' => $pageType ? str_contains($pageType, 'buy_now') : false,
                'currency' => 'SAR',
                'gross_amount' => $grossAmount,
                'amount' => 0,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'coupon_code' => $couponCode,
                'cart_ids' => $checkout['cart_ids'] ?? [],
                'gift_ids' => $checkout['gift_ids'] ?? [],
                'submethods' => [],
                'status' => PaymentAttempt::STATUS_PAID,
                'invoice_id' => $invoiceId,
                'meta' => ['deposit_amount' => $requiredDeposit],
            ]);

            return ['status' => 'paid', 'invoice_id' => $invoiceId];
        } catch (\Throwable $exception) {
            if (!empty($checkout['cart_ids'])) {
                \Modules\Booking\Models\Booking::whereIn('id', $checkout['cart_ids'])->where('status', 'pending')->unpaid()->delete();
            }
            return ['status' => 'error', 'message' => $exception->getMessage()];
        }
    }

    /**
     * Start a wallet top-up. The balance is credited only in the gateway callback,
     * after the gateway itself confirms the charge, its amount and its currency.
     * Only the card gateway (Tap) is offered for top-ups because it is the one whose
     * verification checks the captured amount.
     */
    public function initiateTopup(int $userId, float $amount, array $input = []): array
    {
        $offer = app(WalletTopupService::class)->bestOfferFor($userId, $amount);

        $attempt = PaymentAttempt::create([
            'token' => (string) Str::uuid(),
            'user_id' => $userId,
            'gateway' => 'card',
            'purpose' => PaymentAttempt::PURPOSE_WALLET_TOPUP,
            'isBuyNow' => false,
            'currency' => 'SAR',
            'gross_amount' => $amount,
            'amount' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'cart_ids' => [],
            'gift_ids' => [],
            'submethods' => [],
            'status' => PaymentAttempt::STATUS_INITIATED,
            'meta' => [
                'channel' => $input['channel'] ?? 'web',
                'payment_source' => $input['payment_source'] ?? 'src_card',
                'offer_id' => $offer?->id,
            ],
        ]);

        try {
            $gatewayResult = $this->createGatewayPayment($attempt, $this->buildCustomer($userId), $this->buildGatewayUrls($attempt), $input);
        } catch (\Throwable $e) {
            $attempt->update(['status' => PaymentAttempt::STATUS_FAILED, 'meta' => array_merge($attempt->meta ?? [], ['error' => $e->getMessage()])]);

            return ['status' => 'error', 'message' => __('wallet_ledger.topup_failed')];
        }

        $attempt->update([
            'external_id' => $gatewayResult['external_id'] ?? null,
            'payment_url' => $gatewayResult['payment_url'] ?? null,
            'status' => PaymentAttempt::STATUS_REDIRECTED,
            'meta' => array_merge($attempt->meta ?? [], ['gateway_response' => $gatewayResult['raw'] ?? null]),
        ]);

        return [
            'status' => 'redirect',
            'payment_url' => $attempt->payment_url,
            'attempt_token' => $attempt->token,
            'attempt_id' => $attempt->id,
            'amount' => (float) $attempt->amount,
            'bonus' => $offer ? (float) $offer->bonus_amount : 0,
        ];
    }

    private function handleTopupCallback(PaymentAttempt $attempt, Request $request): array
    {
        $verification = $this->verifyGatewayPayment($attempt, $request);
        $status = $verification['status'] ?? 'failed';

        if ($status === 'paid') {
            app(WalletTopupService::class)->creditPaidAttempt($attempt, $verification);

            return ['status' => 'paid', 'purpose' => PaymentAttempt::PURPOSE_WALLET_TOPUP];
        }

        $attempt->update([
            'status' => $status === 'cancelled' ? PaymentAttempt::STATUS_CANCELLED : PaymentAttempt::STATUS_FAILED,
            'meta' => array_merge($attempt->meta ?? [], ['verify_response' => $verification['raw'] ?? null]),
        ]);

        return ['status' => $status === 'cancelled' ? 'cancelled' : 'failed', 'purpose' => PaymentAttempt::PURPOSE_WALLET_TOPUP];
    }

    private function finalize(int $userId,float $grossAmount,float $taxAmount,float $discountAmount,string $pageType,array $cartIds,array $giftIds,string $paymentMethod,?string $couponCode,array $submethods,?float $gatewayCollected = null): int {
        
        $invoiceId = app(PaymentFinalizerService::class)->finalizePayment($userId,$grossAmount, $taxAmount, $discountAmount, $pageType, $cartIds, $giftIds, $paymentMethod, $couponCode ?? '', true );
        $settlement = app(PaymentSubMethodsService::class)->apply($userId,new Request($submethods),$grossAmount,true,$invoiceId);
        \App\Models\Invoice::findOrFail($invoiceId)->update(['collection_details' => [
            'gateway' => max(0, $gatewayCollected ?? 0),
            'wallet' => (float) ($settlement['used_wallet'] ?? 0),
            'loyalty' => (float) ($settlement['used_loyalty_amount'] ?? 0),
        ]]);
        return $invoiceId;
    }

    private function createGatewayPayment(PaymentAttempt $attempt, array $customer, array $urls, array $input): array
    {
        return app(PaymentGatewayManager::class)->forCheckout($attempt->gateway)->create($attempt, $customer, $urls, $input);
    }

    private function verifyGatewayPayment(PaymentAttempt $attempt, Request $request): array
    {
        $driver = app(PaymentGatewayManager::class)->forCallback($attempt->gateway);

        return $driver ? $driver->verify($attempt, $request) : ['status' => 'failed'];
    }

    private function buildCustomer(int $userId): array
    {
        $user = User::find($userId);
        $name = trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? ''));

        return [
            'name' => $name,
            'first_name' => $user?->first_name,
            'last_name' => $user?->last_name,
            'email' => $user?->email,
            'phone' => $user?->mobile,
            'country_code' => '966',
            'country' => 'SA',
            'reference' => $user?->id,
        ];
    }

    private function buildGatewayUrls(PaymentAttempt $attempt): array
    {
        $base = route('payments.callback', ['gateway' => $attempt->gateway]);
        $token = $attempt->token;

        if (app(PaymentGatewayManager::class)->driverOf($attempt->gateway) === 'arb') {
            return [
                'success' => $base . '?attempt=' . $token,
                'fail' => $base . '?attempt=' . $token . '&result=fail',
                'cancel' => $base . '?attempt=' . $token . '&result=cancel',
            ];
        }

        return [
            'success' => $base . '?attempt=' . $token . '&result=success',
            'fail' => $base . '?attempt=' . $token . '&result=fail',
            'cancel' => $base . '?attempt=' . $token . '&result=cancel',
        ];
    }

    private function resolvePaymentMethod(string $gateway, bool $subMethodsOnly): string
    {
        if ($subMethodsOnly) {
            return 'sub_methods';
        }

        return match ($gateway) {
            'card' => 'tap',
            'tabby' => 'tabby',
            'tamara' => 'tamara',
            'telr' => 'telr',
            'arb' => 'arb',
            'urpay' => 'urpay',
            default => $gateway,
        };
    }

    private function cleanupFailedCartBookings(PaymentAttempt $attempt): void
    {
        if (!empty($attempt->cart_ids)) {
            $failedBookings = \Modules\Booking\Models\Booking::whereIn('id', $attempt->cart_ids)->unpaid()->get();
            foreach ($failedBookings as $bk) {
                $bk->bookingService()->delete();
                $bk->packages()->delete();
                $bk->delete();
            }
        }
    }

}
