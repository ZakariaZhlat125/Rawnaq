<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Wallet\Models\WalletHistory;
use Modules\Wallet\Models\WalletTopupOffer;

class WalletTopupOffersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_wallet_offers');
    }

    public function index()
    {
        $offers = WalletTopupOffer::orderByDesc('id')->get();
        $uses = WalletHistory::where('source', 'campaign')->where('source_type', 'wallet_topup_offer')
            ->selectRaw('source_id, COUNT(*) uses, COALESCE(SUM(amount),0) total')
            ->groupBy('source_id')->get()->keyBy('source_id');

        return view('backend.wallet.offers', compact('offers', 'uses'));
    }

    public function store(Request $request)
    {
        WalletTopupOffer::create($this->validated($request));

        return back()->with('success', __('wallet_ledger.admin.saved'));
    }

    public function update(Request $request, WalletTopupOffer $offer)
    {
        $offer->update($this->validated($request));

        return back()->with('success', __('wallet_ledger.admin.saved'));
    }

    public function destroy(WalletTopupOffer $offer)
    {
        // Keep the row so past bonuses still point at it; just switch it off.
        $offer->update(['is_active' => false]);

        return back()->with('success', __('wallet_ledger.admin.saved'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title_ar' => 'required|string|max:150',
            'title_en' => 'nullable|string|max:150',
            'min_amount' => 'required|numeric|min:1',
            'bonus_amount' => 'required|numeric|min:0.01',
            'bonus_expiry_days' => 'nullable|integer|min:1|max:3650',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
