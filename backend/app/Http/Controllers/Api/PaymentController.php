<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentAttempt;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentOrchestratorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function init(Request $request)
    {
        $data = $request->validate([
            'gateway' => ['required', 'string', Rule::in([...app(PaymentGatewayManager::class)->availableCodes(), 'cod'])],
            'page_type' => 'nullable|in:cart,payment',
            'coupon_code' => 'nullable|string',
            'wallet' => 'sometimes|boolean',
            'wallet_amount' => 'nullable|numeric|min:0',
            'loyalty' => 'sometimes|boolean',
            'loyalty_points' => 'nullable|integer|min:0',
            'payment_source' => 'nullable|string',
        ]);

        $result = app(PaymentOrchestratorService::class)->initiate([
            'gateway' => $data['gateway'],
            'page_type' => $data['page_type'] ?? 'cart',
            'coupon_code' => $data['coupon_code'] ?? null,
            'wallet' => $data['wallet'] ?? false,
            'wallet_amount' => $data['wallet_amount'] ?? null,
            'loyalty' => $data['loyalty'] ?? false,
            'loyalty_points' => $data['loyalty_points'] ?? null,
            'payment_source' => $data['payment_source'] ?? 'src_card',
            'channel' => 'api',
            'is_mobile' => true,
        ]);

        $statusCode = ($result['status'] ?? '') === 'error' ? 422 : 200;

        return response()->json($result, $statusCode);
    }

    public function status(string $token)
    {
        $attempt = PaymentAttempt::where('token', $token)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'status' => $attempt->status,
            'amount' => (float) $attempt->amount,
            'gateway' => $attempt->gateway,
            'payment_url' => $attempt->payment_url,
            'invoice_id' => $attempt->invoice_id,
        ]);
    }
}
