<?php

namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Yajra\DataTables\DataTables;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletHistory;
use App\Models\PaymentAttempt;
use App\Services\Payment\PaymentOrchestratorService;
use App\Services\TapPaymentService;
use App\Services\Wallet\WalletLedger;
use App\Services\Wallet\WalletSettings;
use Currency;
use Carbon\Carbon;

class WalletController extends Controller
{
    public function __construct()
    {
        // Page Title
        $this->module_title = 'Wallet';

        // module name
        $this->module_name = 'wallet';

        // directory path of the module
        $this->module_path = 'Wallet::wallet';

        // Customer wallet histories are staff-only.
        $this->middleware('permission:view_wallet_admin|view_customer')->only('walletHistory', 'walletHistoryData');

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => 'fa-regular fa-sun',
            'module_name' => $this->module_name,
            'module_path' => $this->module_path,
        ]);
    }

    public function walletHistory($id)
    {
        $module_title = __('messages.wallet_history');
        $module_action = 'List';
        $user_id = $id;

        return view('wallet::wallet_history.index_datatable', compact('module_title', 'module_action', 'user_id'));
    }

    public function walletHistoryData(Datatables $datatable, Request $request)
    {
        $query = WalletHistory::where('user_id', $request->id);

        return $datatable->eloquent($query)
            
            ->editColumn('datetime', function ($data) {
                $timezone = setting('default_time_zone') ?? 'UTC';
                return Carbon::parse($data->datetime)->setTimezone($timezone)->format('Y-m-d H:i:s');
            })
            ->editColumn('activity_type', function ($data) {
                return str_replace("_"," ",ucfirst($data->activity_type));
            })
            
            ->editColumn('amount', function ($data) {
                $wallet = json_decode($data->activity_data); 
                return Currency::format($wallet->credit_debit_amount);
            })
            ->filterColumn('amount', function($query, $keyword) {
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(activity_data, '$.credit_debit_amount')) LIKE ?", ["%{$keyword}%"]);
            })
            ->orderColumn('amount', function ($query, $order) {
                $query->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(activity_data, '$.credit_debit_amount')) AS DECIMAL(15,2)) {$order}");
            })
            
            
            ->rawColumns(['activity_type','amount'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }

    /** Web top-up: same verified flow as the API; the balance is credited in the gateway callback. */
    public function addbalance(Request $request)
    {
        $settings = app(WalletSettings::class);
        $amount = (float) $request->amount;

        if ($amount < $settings->float('wallet_topup_min') || $amount > $settings->float('wallet_topup_max')) {
            return redirect()->back()->with('error', __('wallet_ledger.topup_amount_range', [
                'min' => $settings->float('wallet_topup_min'),
                'max' => $settings->float('wallet_topup_max'),
            ]));
        }

        $result = app(PaymentOrchestratorService::class)->initiateTopup(auth()->id(), round($amount, 2), ['channel' => 'web']);
        if (($result['status'] ?? '') !== 'redirect' || empty($result['payment_url'])) {
            return redirect()->back()->with('error', $result['message'] ?? __('wallet_ledger.topup_failed'));
        }

        $paymentUrl = $result['payment_url'];
        $lang = app()->getLocale() ?? 'en';
        $paymentUrl = preg_replace('/([&?])language=[^&]+/', '$1language=' . $lang, $paymentUrl);
        if (!str_contains($paymentUrl, 'language=')) {
            $paymentUrl .= (str_contains($paymentUrl, '?') ? '&' : '?') . 'language=' . $lang;
        }

        return redirect()->away($paymentUrl);
    }

    /**
     * Callback of the old session-based top-up, kept only for charges started before
     * the switch to PaymentAttempt. It credits the amount Tap actually captured, and
     * only when it equals the amount this session asked for.
     */
    public function callback(Request $request)
    {
        $failed = fn ($message) => redirect('/app')->with('error', $message);

        $expected = round((float) session()->pull('walletToAdd'), 2);
        $chargeId = (string) $request->tap_id;
        if ($expected <= 0 || $chargeId === '') {
            return $failed(__('messages.invalid_amount'));
        }

        // A charge created through PaymentAttempt is handled by the main payment callback.
        if (PaymentAttempt::where('external_id', $chargeId)->exists()) {
            return $failed(__('messages.duplicate_payment'));
        }

        $charge = TapPaymentService::forGateway('card')->getCharge($chargeId);
        if (strtoupper((string) ($charge['status'] ?? '')) !== 'CAPTURED') {
            return $failed(__('messages.payment_failed'));
        }

        $captured = round((float) ($charge['amount'] ?? 0), 2);
        if (abs($captured - $expected) > 0.009 || strtoupper((string) ($charge['currency'] ?? '')) !== 'SAR') {
            return $failed(__('wallet_ledger.payment_mismatch'));
        }

        // Legacy rows recorded the charge in activity_data; the ledger key covers new ones.
        if (WalletHistory::where('activity_type', 'deposit')->where('activity_data->tap_id', $chargeId)->exists()) {
            return $failed(__('messages.duplicate_payment'));
        }

        app(WalletLedger::class)->credit(auth()->id(), $captured, 'topup', [
            'activity_type' => 'deposit',
            'source_type' => 'tap_charge',
            'idempotency_key' => 'topup:tap:' . $chargeId,
            'meta' => ['tap_id' => $chargeId, 'status' => $charge['status']],
        ]);

        return redirect('/app')->with('success', __('messages.payment_success'));
    }
}
