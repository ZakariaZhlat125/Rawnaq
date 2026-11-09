<?php

namespace Modules\Wallet\Http\Controllers\API;

use Modules\Wallet\Models\Wallet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Wallet\Http\Requests\WalletRequest;
use Modules\Wallet\Models\WalletHistory;
use Carbon\Carbon;
use App\Services\Wallet\WalletLedger;
use App\Services\Wallet\WalletWithdrawalService;

/**
 * Legacy wallet endpoints kept for the existing mobile apps.
 * New clients should use /api/wallet/* (App\Http\Controllers\Api\WalletV2Controller).
 */
class WalletController extends Controller
{
    /**
     * Create an empty wallet for the current user. The balance can only change
     * through the ledger, so any amount sent by the client is ignored.
     */
    public function store(WalletRequest $request)
    {
        $user = auth()->user();

        $existingWallet = Wallet::where('user_id', $user->id)->first();

        if ($existingWallet) {
            return response()->json([
                'status' => false,
                'message' => __('User already has a wallet'),
                'data' => $existingWallet,
            ], 400);
        }

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'title' => $user->first_name . ' ' . $user->last_name,
            'amount' => 0,
            'cash_balance' => 0,
            'bonus_balance' => 0,
            'status' => 1,
        ]);

        return response()->json([
            'status' => true,
            'message' => __('Wallet created successfully.'),
            'data' => $wallet,
        ], 201);
    }

    public function getBalance(WalletLedger $ledger)
    {
        $summary = $ledger->summary(auth()->id());

        return response()->json([
            'balance' => $summary['balance'],
            'cash_balance' => $summary['cash_balance'],
            'bonus_balance' => $summary['bonus_balance'],
        ], 200);
    }

    /**
     * Retired: it added the client-supplied amount to the balance without any payment.
     * Top-ups now go through POST /api/wallet/topup and are credited in the gateway callback.
     */
    public function walletTopup()
    {
        return response()->json([
            'status' => false,
            'message' => __('wallet_ledger.endpoint_retired'),
        ], 410);
    }


    public function walletHistory(Request $request)
    {

        $perPage = min(max((int) $request->get('per_page', 25), 1), 100);
        $orderBy = $request->get('orderby') === 'asc' ? 'asc' : 'desc';


        $userId = auth()->user()->id;

        $walletHistories = WalletHistory::where('user_id', $userId)
            ->orderBy('created_at', $orderBy)
            ->paginate($perPage);

        $availableBalance = app(WalletLedger::class)->spendable($userId);

        $timezone = setting('default_time_zone') ?? 'UTC';

        $walletHistories->getCollection()->transform(function ($item) use ($timezone) {

            $item->datetime = Carbon::parse($item->datetime)
                ->setTimezone($timezone)
                ->toDateTimeString();
            return $item;
        });

        $response = [
            'pagination' => [
                'total' => $walletHistories->total(),
                'per_page' => $walletHistories->perPage(),
                'current_page' => $walletHistories->currentPage(),
                'last_page' => $walletHistories->lastPage(),
                'from' => $walletHistories->firstItem(),
                'to' => $walletHistories->lastItem(),
                'next_page' => $walletHistories->nextPageUrl(),
                'previous_page' => $walletHistories->previousPageUrl(),
            ],
            'data' => $walletHistories->items(),
            'available_balance' => $availableBalance,

        ];

        return response()->json($response);
    }

    /**
     * Withdrawal request for the authenticated user (any user_id in the body is ignored).
     * The amount is held from the cash balance and paid out after staff review.
     */
    public function withdrawMoney(Request $request, WalletWithdrawalService $withdrawals)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'bank_id' => 'nullable|integer',
            'iban' => ['nullable', 'string', 'regex:/^\s*SA\s*(\d\s*){22}$/i'],
            'account_name' => 'nullable|string|max:120',
        ]);

        $withdrawal = $withdrawals->request(auth()->id(), (float) $data['amount'], $data);

        return response()->json([
            'status' => true,
            'message' => __('wallet_ledger.withdrawal_created'),
            'data' => ['id' => $withdrawal->id, 'status' => $withdrawal->status],
        ], 200);
    }
}
