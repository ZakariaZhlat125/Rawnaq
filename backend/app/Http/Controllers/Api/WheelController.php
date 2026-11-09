<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\Setting;
use App\Models\Wheel;
use App\Services\WheelSpinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Wallet\Models\Wallet;

class WheelController extends Controller
{
    public function __construct(private readonly WheelSpinService $wheelSpinService)
    {
    }

    public function prizes(Request $request): JsonResponse
    {
        $wheelEnabled = $this->isWheelEnabled();
        $prizes = Wheel::query()
            ->where('reward_value', '>', 0)
            ->orderBy('id')
            ->get()
            ->map(fn (Wheel $wheel) => [
                'id' => $wheel->id,
                'type' => $wheel->type,
                'reward_value' => (float) $wheel->reward_value,
                'label' => $this->formatRewardLabel($wheel->type, (float) $wheel->reward_value),
            ])
            ->values();

        $alreadyUsed = $this->wheelSpinService->hasSpun($request->ip());

        return response()->json([
            'status' => true,
            'data' => [
                'enabled' => $wheelEnabled,
                'can_spin' => $wheelEnabled && ! $alreadyUsed,
                'already_used' => $alreadyUsed,
                'prizes' => $prizes,
            ],
        ]);
    }

    public function spin(Request $request): JsonResponse
    {
        if (! $this->isWheelEnabled()) {
            return response()->json([
                'status' => false,
                'message' => __('messagess.wheel_not_available_now'),
            ], 403);
        }

        $ip = $request->ip();

        if ($this->wheelSpinService->hasSpun($ip)) {
            return response()->json([
                'status' => false,
                'already_used' => true,
                'message' => __('messagess.wheel_already_used'),
            ], 409);
        }

        $availableRewards = Wheel::query()
            ->where('reward_value', '>', 0)
            ->select('id', 'type', 'reward_value')
            ->get();

        if ($availableRewards->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => __('messagess.wheel_not_available_now'),
            ], 404);
        }

        $authUser = Auth::guard('sanctum')->user();

        // The wheel is guaranteed to win once per IP address.
        $winner = $availableRewards->random();

        $spin = $this->wheelSpinService->recordSpin(
            ip: $ip,
            user: $authUser,
            won: true,
            wheelId: $winner->id,
            rewardType: $winner->type ?: 'points',
            rewardValue: (float) $winner->reward_value,
        );

        return response()->json([
            'status' => true,
            'won' => true,
            'message' => $this->formatRewardLabel($spin->reward_type, (float) $spin->reward_value),
            'account_linked' => (bool) $authUser,
            'data' => [
                'prize' => [
                    'id' => $spin->wheel_id,
                    'type' => $spin->reward_type,
                    'value' => (float) $spin->reward_value,
                    'label' => $this->formatRewardLabel($spin->reward_type, (float) $spin->reward_value),
                ],
                'balances' => $authUser ? [
                    'loyalty_points' => (int) (optional(LoyaltyPoint::where('user_id', $authUser->id)->first())->points ?? 0),
                    'wallet_balance' => (float) (optional(Wallet::where('user_id', $authUser->id)->first())->amount ?? 0),
                ] : null,
            ],
        ]);
    }

    private function formatRewardLabel(?string $type, float|int $value): string
    {
        $formattedValue = $this->formatRewardNumber($value);

        return $type === 'wallet_balance'
            ? __('messagess.wheel_congrats_wallet', ['amount' => $formattedValue])
            : __('messagess.wheel_congrats', ['points' => $formattedValue]);
    }

    private function formatRewardNumber(float|int $value): float|int
    {
        return floor((float) $value) == (float) $value ? (int) $value : round((float) $value, 2);
    }

    private function isWheelEnabled(): bool
    {
        return (bool) Setting::get('wheel_enabled', true);
    }
}
