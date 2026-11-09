<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\GiftDesign;
use App\Services\Gifts\GiftCardService;
use App\Services\Gifts\GiftRedemptionService;
use App\Services\Gifts\GiftSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class GiftAccountController extends Controller
{
    /** GET /api/gifts/sent */
    public function sent(Request $request)
    {
        $gifts = GiftCard::where('user_id', $request->user()->id)
            ->where('payment_status', 1)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => collect($gifts->items())->map(fn (GiftCard $g) => $this->present($g, 'sender')),
            'pagination' => ['current_page' => $gifts->currentPage(), 'last_page' => $gifts->lastPage(), 'total' => $gifts->total()],
        ]);
    }

    /** GET /api/gifts/sent/{id} — with the timeline. */
    public function sentShow(Request $request, int $id)
    {
        $gift = GiftCard::where('user_id', $request->user()->id)->where('payment_status', 1)->findOrFail($id);

        return response()->json(['status' => true, 'data' => $this->present($gift, 'sender') + [
            'timeline' => $gift->events()->get()
                // Internal events stay internal.
                ->reject(fn ($e) => in_array($e->event, ['send_failed', 'reminder_sent'], true))
                ->map(fn ($e) => [
                    'event' => $e->event,
                    'label' => __('gift_system.event.'.$e->event),
                    'at' => $e->created_at?->toIso8601String(),
                ])->values(),
        ]]);
    }

    /** POST /api/gifts/sent/{id}/resend */
    public function resend(Request $request, int $id)
    {
        $gift = GiftCard::where('user_id', $request->user()->id)->where('payment_status', 1)->findOrFail($id);
        $sent = app(GiftCardService::class)->resend($gift, $request->user()->id);

        return response()->json([
            'status' => $sent,
            'message' => $sent ? __('gift_system.resent') : __('gift_system.resend_failed'),
        ], $sent ? 200 : 422);
    }

    /**
     * GET /api/gifts/received — gifts claimed to this account, plus paid gifts sent to this
     * account's mobile number that are waiting to be claimed.
     */
    public function received(Request $request)
    {
        $user = $request->user();
        $tail = substr(preg_replace('/\D/', '', (string) $user->mobile), -9);

        $gifts = GiftCard::where('payment_status', 1)
            ->where(function ($q) use ($user, $tail) {
                $q->where('recipient_user_id', $user->id);
                if (strlen($tail) === 9) {
                    $q->orWhere(fn ($p) => $p->whereNull('recipient_user_id')->where('recipient_phone', 'like', '%'.$tail));
                }
            })
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->filter(fn (GiftCard $g) => (int) $g->recipient_user_id === (int) $user->id
                || GiftRedemptionService::samePhone($g->recipient_phone, $user->mobile));

        return response()->json([
            'status' => true,
            'data' => $gifts->map(fn (GiftCard $g) => $this->present($g, 'recipient'))->values(),
        ]);
    }

    /** POST /api/gifts/received/{id}/claim — a gift sent to my own number, no code needed. */
    public function claim(Request $request, int $id)
    {
        $gift = app(GiftRedemptionService::class)->claimForRecipient($id, $request->user());

        return response()->json([
            'status' => true,
            'message' => $gift->isValue() ? __('gift_system.added_to_wallet', ['amount' => $gift->value_amount]) : __('gift_system.claimed'),
            'data' => $this->present($gift->fresh(), 'recipient'),
        ]);
    }

    /** POST /api/gifts/received/{id}/book — book the gifted services online. */
    public function book(Request $request, int $id)
    {
        $data = $request->validate([
            'appointments' => ['required', 'array', 'min:1', 'max:10'],
            'appointments.*.service_id' => ['required', 'integer'],
            'appointments.*.employee_id' => ['required', 'integer'],
            'appointments.*.date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'appointments.*.time' => ['required', 'date_format:H:i'],
        ]);
        $gift = GiftCard::where('recipient_user_id', $request->user()->id)->findOrFail($id);
        $bookingIds = app(GiftRedemptionService::class)->bookOnline($gift, $request->user(), $data['appointments']);

        return response()->json([
            'status' => true,
            'message' => __('gift_system.booked'),
            'booking_ids' => $bookingIds,
            'data' => $this->present($gift->fresh(), 'recipient'),
        ]);
    }

    /**
     * POST /api/gifts/redeem {code}. Route-throttled; repeated wrong codes from one
     * account are additionally locked out for an hour.
     */
    public function redeem(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:30']]);
        $key = 'gift-redeem-fail:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'status' => false,
                'message' => __('gift_system.too_many_attempts', ['minutes' => ceil(RateLimiter::availableIn($key) / 60)]),
            ], 429);
        }

        try {
            $gift = app(GiftRedemptionService::class)->redeemCode($data['code'], $request->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            RateLimiter::hit($key, 3600);
            throw $e;
        }
        RateLimiter::clear($key);

        return response()->json([
            'status' => true,
            'message' => $gift->isValue() ? __('gift_system.added_to_wallet', ['amount' => $gift->value_amount]) : __('gift_system.claimed'),
            'data' => $this->present($gift->fresh(), 'recipient'),
        ]);
    }

    /** GET /api/gift-designs (public) + value-card limits for the purchase page. */
    public function designs()
    {
        $settings = app(GiftSettings::class);

        return response()->json([
            'status' => true,
            'data' => GiftDesign::active()->get()->map->toPublicArray(),
            'value_card' => [
                'min' => $settings->float('gift_value_min'),
                'max' => $settings->float('gift_value_max'),
                'presets' => $settings->presets(),
                'validity_days' => $settings->int('gift_validity_days'),
            ],
        ]);
    }

    private function present(GiftCard $g, string $as): array
    {
        $status = in_array($g->gift_status, GiftCard::USABLE_STATUSES, true) && $g->isExpired()
            ? GiftCard::STATUS_EXPIRED
            : $g->gift_status;
        $names = fn ($items) => $items->map(fn ($m) => is_array($m->name) ? ($m->name[app()->getLocale()] ?? reset($m->name)) : $m->name)->values();

        return [
            'id' => $g->id,
            'type' => $g->type,
            'value_amount' => $g->isValue() ? (float) $g->value_amount : null,
            'services' => $names($g->services_list),
            'packages' => $names($g->packages_list),
            'design' => $g->design,
            'recipient_name' => $g->recipient_name,
            'sender_name' => $g->sender_name,
            'message' => $g->message,
            'status' => $status,
            'status_label' => __('gift_system.status.'.$status),
            'usable' => $g->isUsable(),
            'claimed' => (bool) $g->recipient_user_id,
            'opened' => (bool) $g->opened_at,
            'can_book_online' => $as === 'recipient' && $g->isUsable() && $g->recipient_user_id && $g->type === GiftCard::TYPE_SERVICE && $g->branch_id,
            'branch_id' => $g->branch_id,
            'service_ids' => array_map('intval', $g->requested_services ?? []),
            // id + name together, so the booking form never pairs a name with the wrong service.
            'service_items' => $g->services_list->map(fn ($m) => ['id' => $m->id, 'name' => is_array($m->name) ? ($m->name[app()->getLocale()] ?? reset($m->name)) : $m->name])->values(),
            'expires_at' => $g->expires_at?->toIso8601String(),
            'sent_at' => $g->sent_at?->toIso8601String(),
            'claimed_at' => $g->claimed_at?->toIso8601String(),
            'redeemed_at' => $g->redeemed_at?->toIso8601String(),
            'created_at' => $g->created_at?->toIso8601String(),
            // The buyer sees the code to pass it on; the recipient sees it once the gift is theirs.
            'code' => $g->isUsable() && ($as === 'sender' || $g->recipient_user_id) ? $g->redeemCode() : $g->maskedCode(),
            'share_url' => $as === 'sender' && $g->isUsable() ? $g->share_url : null,
        ];
    }
}
