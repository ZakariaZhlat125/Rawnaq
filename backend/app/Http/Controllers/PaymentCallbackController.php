<?php

namespace App\Http\Controllers;

use App\Models\PaymentAttempt;
use App\Services\Payment\Gateways\ArbGateway;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentOrchestratorService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function handle(string $gateway, Request $request)
    {
        $token = $request->query('attempt') ?: $request->input('attempt');

        if (! $token && $request->filled('trandata')) {
            try {
                $driver = app(PaymentGatewayManager::class)->forCallback($gateway);
                if ($driver instanceof ArbGateway) {
                    $token = $driver->attemptToken((string) $request->input('trandata'));
                }
            } catch (\Throwable) {
                // Fallback attempt extraction failed
            }
        }

        if (!$token) {
            return redirect('/app')->with('error', __('messages.payment_failed'));
        }

        $attempt = PaymentAttempt::where('token', $token)->first();
        $isApi = ($attempt?->meta['channel'] ?? '') === 'api' || (bool) ($attempt?->is_mobile ?? false);
        $frontendUrl = rtrim(config('app.frontend_url', 'http://127.0.0.1:5173'), '/');

        $result = app(PaymentOrchestratorService::class)->handleCallback($gateway, $token, $request);

        if ($attempt?->purpose === PaymentAttempt::PURPOSE_WALLET_TOPUP) {
            $status = $result['status'] ?? 'failed';
            if ($isApi) {
                return redirect($frontendUrl . '/account?tab=wallet&topup=' . $status);
            }

            return $status === 'paid'
                ? redirect('/app')->with('success', __('wallet_ledger.topup_success'))
                : redirect('/app')->with('error', $status === 'cancelled' ? __('messages.payment_cancelled') : __('messages.payment_failed'));
        }

        if (($result['status'] ?? '') === 'paid') {
            if ($isApi) {
                return redirect($frontendUrl . '/account?payment=success&invoice=' . ($result['invoice_id'] ?? '') . '&attempt_id=' . ($attempt?->id ?? ''));
            }
            return redirect('/app/invoice')->with('success', __('messages.payment_success'));
        }

        $message = ($result['status'] ?? '') === 'cancelled'
            ? __('messages.payment_cancelled')
            : __('messages.payment_failed');

        if ($isApi) {
            return redirect($frontendUrl . '/booking?payment=' . ($result['status'] ?? 'failed'));
        }

        return redirect('/app')->with('error', $result['message'] ?? $message);
    }
}
