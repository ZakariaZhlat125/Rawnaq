<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\Wallet\InsufficientWalletBalance;
use App\Services\Wallet\WalletLedger;
use App\Services\Wallet\WalletSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\WalletHistory;
use Modules\Wallet\Models\WithdrawMoney;

class WalletAdminController extends Controller
{
    public function __construct(private WalletLedger $ledger, private WalletSettings $settings)
    {
        $this->middleware('permission:view_wallet_admin')->only('index');
        $this->middleware('permission:adjust_wallet_balance')->only('adjust');
        $this->middleware('permission:manage_wallet_policy')->only('policy', 'savePolicy');
    }

    /** Report (wallet balances are a liability) + customer lookup for manual adjustments. */
    public function index(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();

        $liability = DB::table('wallet')->selectRaw('COALESCE(SUM(amount),0) total, COALESCE(SUM(cash_balance),0) cash, COALESCE(SUM(bonus_balance),0) bonus')->first();
        $period = WalletHistory::whereBetween('created_at', [$from, $to])->whereNotNull('source')
            ->selectRaw('source, direction, COALESCE(SUM(amount),0) total, COUNT(*) movements')
            ->groupBy('source', 'direction')
            ->get()
            ->keyBy(fn ($row) => $row->source.':'.$row->direction);
        $sum = fn (string $key) => (float) ($period[$key]->total ?? 0);

        $stats = [
            'topups' => $sum('topup:credit'),
            'spent' => $sum('payment:debit'),
            'refunds' => $sum('refund:credit'),
            'bonus_granted' => $sum('campaign:credit') + $sum('reward:credit') + $sum('points_conversion:credit') + $sum('gift:credit'),
            'expired' => $sum('expiry:debit'),
            'withdrawn' => (float) WithdrawMoney::where('status', WithdrawMoney::STATUS_PAID)->whereBetween('reviewed_at', [$from, $to])->sum('amount'),
            'pending_withdrawals' => WithdrawMoney::whereIn('status', [WithdrawMoney::STATUS_PENDING, WithdrawMoney::STATUS_APPROVED])->count(),
            // Should always be 0: wallets whose total differs from cash + bonus.
            'drift' => DB::table('wallet')->whereRaw('ABS(COALESCE(amount,0) - (cash_balance + bonus_balance)) > 0.009')->count(),
        ];

        $customer = null;
        $customerWallet = null;
        $customerHistory = collect();
        if ($request->filled('customer')) {
            $term = trim($request->customer);
            $customer = User::query()
                ->where(fn ($q) => $q->where('mobile', $term)->orWhere('email', $term)->orWhere('id', ctype_digit($term) ? (int) $term : 0))
                ->first();
            if ($customer) {
                $customerWallet = $this->ledger->summary($customer->id);
                $customerHistory = WalletHistory::where('user_id', $customer->id)->orderByDesc('id')->limit(15)->get();
            }
        }

        return view('backend.wallet.index', compact('liability', 'stats', 'from', 'to', 'customer', 'customerWallet', 'customerHistory'));
    }

    /** Manual credit/debit with a mandatory reason (adjust_wallet_balance permission). */
    public function adjust(Request $request, int $user)
    {
        $data = $request->validate([
            'direction' => 'required|in:credit,debit',
            'bucket' => 'required|in:cash,bonus',
            'amount' => 'required|numeric|min:0.01|max:100000',
            'reason' => 'required|string|min:3|max:500',
            'expiry_days' => 'nullable|integer|min:1|max:3650',
        ]);
        $customer = User::findOrFail($user);

        $options = [
            'activity_type' => 'adjustment',
            'message' => $data['reason'],
            'created_by' => auth()->id(),
            'meta' => ['reason' => $data['reason'], 'staff_id' => auth()->id()],
        ];

        try {
            if ($data['direction'] === 'credit') {
                $this->ledger->credit($customer->id, (float) $data['amount'], 'adjustment', $options + [
                    'bucket' => $data['bucket'],
                    'expires_at' => $data['bucket'] === 'bonus' && ! empty($data['expiry_days']) ? now()->addDays((int) $data['expiry_days']) : null,
                ]);
            } else {
                $this->ledger->debit($customer->id, (float) $data['amount'], 'adjustment', $options + [
                    'buckets' => $data['bucket'],
                    'allow_inactive' => true,
                ]);
            }
        } catch (InsufficientWalletBalance $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('wallet.index', ['customer' => $customer->id])->with('success', __('wallet_ledger.admin.adjusted'));
    }

    public function policy()
    {
        return view('backend.wallet.policy', ['values' => $this->settings->all()]);
    }

    public function savePolicy(Request $request)
    {
        $data = $request->validate([
            'wallet_refund_on_cancel' => 'required|boolean',
            'wallet_free_cancel_hours' => 'required|numeric|min:0|max:720',
            'wallet_late_cancel_fee_percent' => 'required|numeric|min:0|max:100',
            'wallet_withdrawal_enabled' => 'required|boolean',
            'wallet_withdrawal_min' => 'required|numeric|min:0',
            'wallet_withdrawal_requires_approval' => 'required|boolean',
            'wallet_spend_bonus_first' => 'required|boolean',
            'wallet_points_conversion_enabled' => 'required|boolean',
            'wallet_points_conversion_min' => 'required|integer|min:1',
            'wallet_topup_min' => 'required|numeric|min:1',
            'wallet_topup_max' => 'required|numeric|gt:wallet_topup_min',
            'wallet_topup_presets' => ['required', 'string', 'max:200', 'regex:/^\s*\d+(\.\d+)?(\s*,\s*\d+(\.\d+)?)*\s*$/'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, (string) $value);
        }

        return back()->with('success', __('wallet_ledger.admin.saved'));
    }
}
