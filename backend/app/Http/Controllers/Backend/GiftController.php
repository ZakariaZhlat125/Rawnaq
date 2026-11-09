<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\Setting;
use App\Services\Gifts\GiftCardService;
use App\Services\Gifts\GiftSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    public function __construct(private GiftCardService $gifts)
    {
        $this->middleware('permission:view_gift')->only('index', 'show');
        $this->middleware('permission:manage_gifts')->only('cancel', 'extend', 'resend', 'revealCode', 'settings', 'saveSettings');
    }

    public function index(Request $request)
    {
        $query = GiftCard::with('user:id,first_name,last_name,mobile')
            ->where(fn ($q) => $q->where('payment_status', 1)->orWhere('gift_status', '!=', GiftCard::STATUS_PENDING_PAYMENT))
            ->when($request->status, fn ($q, $s) => $s === GiftCard::STATUS_EXPIRED
                ? $q->where(fn ($e) => $e->where('gift_status', $s)->orWhere(fn ($p) => $p->whereIn('gift_status', GiftCard::USABLE_STATUSES)->where('expires_at', '<=', now())))
                : $q->where('gift_status', $s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->source, fn ($q, $src) => $q->where('source', $src))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->batch, fn ($q, $b) => $q->where('batch_id', $b))
            ->when(trim((string) $request->q), function ($q, $term) {
                $digits = preg_replace('/\D/', '', $term);
                $q->where(fn ($w) => $w
                    ->where('recipient_name', 'like', "%{$term}%")
                    ->orWhere('sender_name', 'like', "%{$term}%")
                    ->orWhere('redeem_code_last4', strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $term), -4)))
                    ->when($digits !== '', fn ($d) => $d->orWhere('recipient_phone', 'like', '%'.substr($digits, -9).'%')->orWhere('id', (int) $digits)));
            })
            ->orderByDesc('id');

        $gifts = $query->paginate(25)->withQueryString();

        return view('backend.gift.index', compact('gifts'));
    }

    public function show(int $id)
    {
        $gift = GiftCard::with(['user', 'recipient', 'branch', 'batch', 'events.actor', 'redemptions.user', 'redemptions.staff'])->findOrFail($id);

        return view('backend.gift.show', compact('gift'));
    }

    public function cancel(Request $request, int $id)
    {
        $data = $request->validate([
            'reason' => 'required|string|min:3|max:255',
            'refund' => 'nullable|boolean',
        ]);
        $this->gifts->cancel(GiftCard::findOrFail($id), auth()->id(), $data['reason'], (bool) ($data['refund'] ?? false));

        return back()->with('success', __('gift_system.admin.cancelled'));
    }

    public function extend(Request $request, int $id)
    {
        $data = $request->validate([
            'expires_at' => 'required|date|after:today',
            'reason' => 'nullable|string|max:255',
        ]);
        $this->gifts->extend(GiftCard::findOrFail($id), Carbon::parse($data['expires_at']), auth()->id(), $data['reason'] ?? null);

        return back()->with('success', __('gift_system.admin.extended'));
    }

    public function resend(int $id)
    {
        $sent = $this->gifts->resend(GiftCard::findOrFail($id), auth()->id());

        return back()->with($sent ? 'success' : 'error', $sent ? __('gift_system.resent') : __('gift_system.resend_failed'));
    }

    /** The code is shown on demand only, and every view is logged on the timeline. */
    public function revealCode(int $id)
    {
        $gift = GiftCard::findOrFail($id);
        $this->gifts->record($gift, 'code_viewed', auth()->id());

        return response()->json(['code' => $gift->redeemCode()]);
    }

    public function settings()
    {
        return view('backend.gift.settings', ['values' => app(GiftSettings::class)->all()]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'gift_validity_days' => 'required|integer|min:1|max:3650',
            'gift_refund_on_cancel' => 'required|boolean',
            'gift_refund_on_expiry' => 'required|boolean',
            'gift_value_min' => 'required|numeric|min:1',
            'gift_value_max' => 'required|numeric|gt:gift_value_min',
            'gift_value_presets' => ['required', 'string', 'max:200', 'regex:/^\s*\d+(\.\d+)?(\s*,\s*\d+(\.\d+)?)*\s*$/'],
            'gift_expiry_reminder_days' => 'required|integer|min:0|max:60',
            'gift_expiry_reminder_sms' => 'required|string|max:500',
        ]);
        foreach ($data as $key => $value) {
            Setting::set($key, (string) $value);
        }

        return back()->with('success', __('gift_system.admin.saved'));
    }
}
