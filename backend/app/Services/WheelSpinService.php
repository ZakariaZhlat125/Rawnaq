<?php

namespace App\Services;

use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointTransaction;
use App\Models\User;
use App\Models\WheelSpin;
use Illuminate\Support\Facades\DB;
use App\Services\Wallet\WalletLedger;

/**
 * Tracks lucky-wheel spins per IP address so each IP only ever gets one
 * spin, and reconciles a guest's spin with their account once they sign
 * in or register from the same IP.
 */
class WheelSpinService
{
    /**
     * The wheel can only be spun once per IP address, ever.
     */
    public function hasSpun(string $ip): bool
    {
        return WheelSpin::query()->where('ip_address', $ip)->exists();
    }

    /**
     * Record a spin outcome for an IP address, crediting it immediately
     * when the spin already belongs to an authenticated user.
     */
    public function recordSpin(string $ip, ?User $user, bool $won, ?int $wheelId, ?string $rewardType, ?float $rewardValue): WheelSpin
    {
        $spin = WheelSpin::create([
            'ip_address' => $ip,
            'user_id' => $user?->id,
            'wheel_id' => $wheelId,
            'won' => $won,
            'reward_type' => $rewardType,
            'reward_value' => $rewardValue,
            'credited' => false,
        ]);

        if ($user) {
            $this->creditSpin($spin, $user);
        }

        return $spin->fresh();
    }

    /**
     * Called right after a user authenticates: attaches the most recent
     * un-credited guest spin from their current IP to their account and
     * applies the reward, if any.
     */
    public function linkIpToUser(User $user, string $ip): ?WheelSpin
    {
        return DB::transaction(function () use ($user, $ip) {
            $spin = WheelSpin::query()
                ->where('ip_address', $ip)
                ->whereNull('user_id')
                ->where('credited', false)
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if (! $spin) {
                return null;
            }

            $spin->user_id = $user->id;
            $spin->save();

            $this->creditSpin($spin, $user);

            return $spin->fresh();
        });
    }

    private function creditSpin(WheelSpin $spin, User $user): void
    {
        if ($spin->credited) {
            return;
        }

        if (! $spin->won || ! $spin->reward_type) {
            $spin->update(['credited' => true, 'credited_at' => now()]);

            return;
        }

        if ($spin->reward_type === 'wallet_balance') {
            // Wheel winnings are promotional: bonus bucket, not withdrawable.
            $entry = app(WalletLedger::class)->credit($user->id, (float) $spin->reward_value, 'reward', [
                'bucket' => 'bonus',
                'activity_type' => 'wheel_win',
                'message' => "Won {$spin->reward_value} from lucky wheel",
                'source_type' => 'wheel_spin',
                'source_id' => $spin->id,
                'idempotency_key' => 'wheel:spin:'.$spin->id,
                'meta' => [
                    'wheel_spin_id' => $spin->id,
                    'wheel_id' => $spin->wheel_id,
                    'reward_type' => $spin->reward_type,
                    'reward_value' => $spin->reward_value,
                ],
            ]);

            LoyaltyPointTransaction::create([
                'user_id' => $user->id,
                'action' => 'add',
                'points' => 0,
                'balance_after' => null,
                'source' => 'wheel',
                'source_id' => $spin->wheel_id,
                'meta' => [
                    'ip_address' => $spin->ip_address,
                    'wheel_spin_id' => $spin->id,
                    'reward_type' => $spin->reward_type,
                    'reward_value' => $spin->reward_value,
                    'wallet_balance_after' => (float) $entry->balance_after,
                ],
            ]);
        } elseif ($spin->reward_type === 'points') {
            $pointsToAdd = (int) round($spin->reward_value);

            $loyalty = LoyaltyPoint::firstOrNew(['user_id' => $user->id]);
            $loyalty->points = (int) ($loyalty->points ?? 0) + $pointsToAdd;
            $loyalty->save();

            LoyaltyPointTransaction::create([
                'user_id' => $user->id,
                'action' => 'add',
                'points' => $pointsToAdd,
                'balance_after' => (int) $loyalty->points,
                'source' => 'wheel',
                'source_id' => $spin->wheel_id,
                'meta' => [
                    'ip_address' => $spin->ip_address,
                    'wheel_spin_id' => $spin->id,
                    'reward_type' => $spin->reward_type,
                    'reward_value' => $spin->reward_value,
                ],
            ]);
        }

        $spin->update(['credited' => true, 'credited_at' => now()]);
    }
}
