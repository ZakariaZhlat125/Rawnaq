<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Services\Payment\PaymentOrchestratorService;
use App\Services\Wallet\PointsToWalletConverter;
use App\Services\Wallet\WalletLedger;
use App\Services\Wallet\WalletSettings;
use App\Services\Wallet\WalletTopupService;
use App\Services\Wallet\WalletWithdrawalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Wallet\Models\WalletHistory;
use Modules\Wallet\Models\WithdrawMoney;

class WalletV2Controller extends Controller
{
    public function __construct(private WalletLedger $ledger, private WalletSettings $settings) {}

    /** GET /api/wallet — both balances, nearest expiry, and the limits the UI needs. */
    public function show(Request $request)
    {
        $userId = $request->user()->id;
        $pointValue = $this->settings->pointValue();

        return response()->json([
            'status' => true,
            'data' => $this->ledger->summary($userId) + [
                'points_balance' => (int) (LoyaltyPoint::where('user_id', $userId)->value('points') ?? 0),
                'point_value' => $pointValue,
                'topup' => [
                    'min' => $this->settings->float('wallet_topup_min'),
                    'max' => $this->settings->float('wallet_topup_max'),
                    'presets' => $this->settings->presets(),
                ],
                'withdrawal' => [
                    'enabled' => $this->settings->bool('wallet_withdrawal_enabled'),
                    'min' => $this->settings->float('wallet_withdrawal_min'),
                ],
                'points_conversion' => [
                    'enabled' => $this->settings->bool('wallet_points_conversion_enabled'),
                    'min' => (int) $this->settings->get('wallet_points_conversion_min'),
                ],
                'refund_policy' => [
                    'refund_on_cancel' => $this->settings->bool('wallet_refund_on_cancel'),
                    'free_cancel_hours' => $this->settings->float('wallet_free_cancel_hours'),
                    'late_cancel_fee_percent' => $this->settings->float('wallet_late_cancel_fee_percent'),
                ],
            ],
        ]);
    }

    /** GET /api/wallet/history?type=topup|refund|...|credit|debit */
    public function history(Request $request)
    {
        $data = $request->validate([
            'type' => 'nullable|string|in:'.implode(',', array_merge(WalletLedger::SOURCES, ['credit', 'debit'])),
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $timezone = setting('default_time_zone') ?? 'UTC';

        $page = WalletHistory::where('user_id', $request->user()->id)
            ->when($data['type'] ?? null, function ($q, $type) {
                in_array($type, ['credit', 'debit'], true) ? $q->where('direction', $type) : $q->where('source', $type);
            })
            ->orderByDesc('id')
            ->paginate($data['per_page'] ?? 20);

        return response()->json([
            'status' => true,
            'data' => collect($page->items())->map(function (WalletHistory $row) use ($timezone) {
                $activity = json_decode($row->activity_data ?? '{}', true) ?: [];
                $amount = $row->amount ?? (float) ($activity['credit_debit_amount'] ?? $activity['reward_value'] ?? 0);
                $direction = $row->direction
                    ?? (in_array($row->activity_type, ['withdraw', 'withdrawal', 'debit'], true) ? 'debit' : 'credit');

                return [
                    'id' => $row->id,
                    'direction' => $direction,
                    'source' => $row->source ?? $row->activity_type,
                    'label' => $row->source ? __('wallet_ledger.source.'.$row->source) : $row->activity_message,
                    'message' => $row->activity_message,
                    'amount' => round(abs($amount), 2),
                    'cash_amount' => $row->cash_amount,
                    'bonus_amount' => $row->bonus_amount,
                    'balance_after' => $row->balance_after,
                    'expires_at' => $row->expires_at?->toIso8601String(),
                    'datetime' => Carbon::parse($row->datetime ?? $row->created_at)->setTimezone($timezone)->toDateTimeString(),
                ];
            }),
            'pagination' => [
                'total' => $page->total(),
                'per_page' => $page->perPage(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    /** GET /api/wallet/topup-offers */
    public function topupOffers(Request $request)
    {
        return response()->json([
            'status' => true,
            'data' => app(WalletTopupService::class)->runningOffers($request->user()->id),
        ]);
    }

    /** POST /api/wallet/topup — creates a gateway payment; the balance is added in the callback. */
    public function topup(Request $request)
    {
        $min = $this->settings->float('wallet_topup_min');
        $max = $this->settings->float('wallet_topup_max');
        $data = $request->validate([
            'amount' => "required|numeric|min:$min|max:$max",
            'payment_source' => 'nullable|string|in:src_card,src_all,src_apple_pay,src_mada',
        ], [
            'amount.min' => __('wallet_ledger.topup_amount_range', ['min' => $min, 'max' => $max]),
            'amount.max' => __('wallet_ledger.topup_amount_range', ['min' => $min, 'max' => $max]),
        ]);

        $result = app(PaymentOrchestratorService::class)->initiateTopup($request->user()->id, round((float) $data['amount'], 2), [
            'channel' => 'api',
            'payment_source' => $data['payment_source'] ?? 'src_card',
        ]);

        return response()->json($result, ($result['status'] ?? '') === 'error' ? 422 : 200);
    }

    /** POST /api/wallet/convert-points */
    public function convertPoints(Request $request)
    {
        $data = $request->validate(['points' => 'required|integer|min:1']);
        $result = app(PointsToWalletConverter::class)->convert($request->user()->id, (int) $data['points']);

        return response()->json([
            'status' => true,
            'message' => __('wallet_ledger.conversion_done', ['points' => $result['points'], 'amount' => number_format($result['amount'], 2)]),
            'data' => $result + ['wallet' => $this->ledger->summary($request->user()->id)],
        ]);
    }

    /** GET /api/wallet/withdrawals */
    public function withdrawals(Request $request)
    {
        $rows = WithdrawMoney::where('user_id', $request->user()->id)->orderByDesc('id')->paginate(20);

        return response()->json([
            'status' => true,
            'data' => collect($rows->items())->map(fn (WithdrawMoney $w) => [
                'id' => $w->id,
                'amount' => $w->amount,
                'status' => $w->status,
                'status_label' => __('wallet_ledger.withdraw_status.'.$w->status),
                'iban_last4' => $w->iban ? substr($w->iban, -4) : null,
                'reference' => $w->status === WithdrawMoney::STATUS_PAID ? $w->reference : null,
                'note' => $w->review_note,
                'created_at' => $w->created_at?->toIso8601String(),
            ]),
            'pagination' => ['total' => $rows->total(), 'current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage()],
        ]);
    }

    /** POST /api/wallet/withdrawals */
    public function requestWithdrawal(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'bank_id' => 'nullable|integer',
            'iban' => ['nullable', 'string', 'regex:/^\s*SA\s*(\d\s*){22}$/i'],
            'account_name' => 'nullable|string|max:120',
        ]);

        $withdrawal = app(WalletWithdrawalService::class)->request($request->user()->id, (float) $data['amount'], $data);

        return response()->json([
            'status' => true,
            'message' => __('wallet_ledger.withdrawal_created'),
            'data' => ['id' => $withdrawal->id, 'status' => $withdrawal->status, 'wallet' => $this->ledger->summary($request->user()->id)],
        ], 201);
    }
}
