<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\GiftDesign;
use App\Services\Gifts\GiftCardService;

class GiftClaimController extends Controller
{
    /**
     * Public gift page (the 64-char claim token in the link is the secret). Opening it is
     * recorded once as "opened" on the buyer's timeline. The redeem code is shown so the
     * recipient can add the gift to their account or use it at a branch.
     */
    public function show(string $token)
    {
        $giftCard = GiftCard::query()->where('claim_token', $token)->where('payment_status', 1)->firstOrFail();
        // The buyer previewing their own link is not the recipient opening it.
        $viewer = auth('sanctum')->user();
        if (! $viewer || (int) $viewer->id !== (int) $giftCard->user_id) {
            app(GiftCardService::class)->markOpened($giftCard);
        }
        $usable = $giftCard->isUsable();
        // Past its date but not yet swept by gifts:expire: show it as expired already.
        $status = in_array($giftCard->gift_status, GiftCard::USABLE_STATUSES, true) && $giftCard->isExpired()
            ? GiftCard::STATUS_EXPIRED
            : $giftCard->gift_status;

        return response()->json([
            'success' => true,
            'data' => [
                'recipient_name' => $giftCard->recipient_name,
                'sender_name' => $giftCard->sender_name,
                'design' => $giftCard->design ?: 'lux-dark',
                'design_data' => GiftDesign::where('slug', $giftCard->design ?: 'lux-dark')->first()?->toPublicArray(),
                'message' => $giftCard->message,
                'type' => $giftCard->type,
                'value_amount' => $giftCard->isValue() ? (float) $giftCard->value_amount : null,
                'gift_status' => $status,
                'status_label' => __('gift_system.status.'.$status),
                'usable' => $usable,
                'claimed' => (bool) $giftCard->recipient_user_id,
                'expires_at' => $giftCard->expires_at?->toIso8601String(),
                // The code is only useful while the gift can still be used.
                'redeem_code' => $usable ? $giftCard->redeemCode() : null,
                'send_channel' => $giftCard->send_channel,
                'created_at' => optional($giftCard->created_at)->toIso8601String(),
                'services' => $giftCard->services_list->map(fn ($service) => [
                    'id' => $service->id,
                    'name' => $service->getTranslations('name') ?: ['ar' => $service->name],
                ])->values(),
                'packages' => $giftCard->packages_list->map(fn ($package) => [
                    'id' => $package->id,
                    'name' => $package->getTranslations('name') ?: ['ar' => $package->name],
                ])->values(),
            ],
        ]);
    }
}
