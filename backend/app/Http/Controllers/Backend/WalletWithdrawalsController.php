<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletWithdrawalService;
use Illuminate\Http\Request;
use Modules\Wallet\Models\WithdrawMoney;

class WalletWithdrawalsController extends Controller
{
    public function __construct(private WalletWithdrawalService $withdrawals)
    {
        $this->middleware('permission:manage_wallet_withdrawals');
    }

    public function index(Request $request)
    {
        $status = $request->get('status', WithdrawMoney::STATUS_PENDING);
        $rows = WithdrawMoney::with(['user:id,first_name,last_name,mobile,email', 'bank'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('backend.wallet.withdrawals', compact('rows', 'status'));
    }

    public function approve(Request $request, WithdrawMoney $withdrawal)
    {
        $data = $request->validate(['note' => 'nullable|string|max:500']);
        $this->withdrawals->approve($withdrawal, auth()->id(), $data['note'] ?? null);

        return back()->with('success', __('wallet_ledger.admin.saved'));
    }

    public function reject(Request $request, WithdrawMoney $withdrawal)
    {
        $data = $request->validate(['note' => 'required|string|max:500']);
        $this->withdrawals->reject($withdrawal, auth()->id(), $data['note']);

        return back()->with('success', __('wallet_ledger.admin.saved'));
    }

    public function markPaid(Request $request, WithdrawMoney $withdrawal)
    {
        $data = $request->validate([
            'reference' => 'required|string|max:120',
            'note' => 'nullable|string|max:500',
        ]);
        $this->withdrawals->markPaid($withdrawal, auth()->id(), $data['reference'], $data['note'] ?? null);

        return back()->with('success', __('wallet_ledger.admin.saved'));
    }
}
