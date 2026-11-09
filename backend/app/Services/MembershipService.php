<?php

namespace App\Services;

use App\Models\MembershipTier;
use App\Models\User;
use App\Models\UserMembership;
use App\Services\Wallet\WalletLedger;
use Illuminate\Support\Str;

class MembershipService
{
    protected TaqnyatSmsService $smsService;

    public function __construct(TaqnyatSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Get or create a user's membership record.
     * Assigns the lowest tier (level=1) if creating for the first time.
     */
    public function getOrCreateMembership(User $user): UserMembership
    {
        $membership = $user->userMembership;

        if (!$membership) {
            $lowestTier = MembershipTier::orderBy('level')->first();

            if (!$lowestTier) {
                // Auto-seed default tiers if none exist
                $this->seedDefaultTiers();
                $lowestTier = MembershipTier::orderBy('level')->first();
            }

            $membership = UserMembership::create([
                'user_id'            => $user->id,
                'membership_tier_id' => $lowestTier->id,
                'points_balance'     => 0,
                'total_points_earned'=> 0,
                'qr_code_token'      => $this->generateQrToken(),
                'is_active'          => true,
            ]);
        }

        return $membership->load('tier');
    }

    /**
     * Add points to a user's membership and handle automatic tier upgrade.
     *
     * @return array{membership: UserMembership, upgraded: bool, new_tier: MembershipTier|null}
     */
    public function addPoints(User $user, int $points): array
    {
        $membership = $this->getOrCreateMembership($user);

        $membership->points_balance      += $points;
        $membership->total_points_earned += $points;
        $membership->save();

        $upgraded = $this->checkAndUpgradeTier($membership);

        $membership->load('tier');

        return [
            'membership' => $membership,
            'upgraded'   => $upgraded,
            'new_tier'   => $upgraded ? $membership->tier : null,
        ];
    }

    /**
     * Deduct (redeem) points from a user's membership.
     *
     * @return array{success: bool, message: string, membership: UserMembership}
     */
    public function redeemPoints(User $user, int $points): array
    {
        $membership = $this->getOrCreateMembership($user);

        if ($membership->points_balance < $points) {
            return [
                'success'    => false,
                'message'    => 'رصيد النقاط غير كافٍ',
                'membership' => $membership,
            ];
        }

        $membership->points_balance -= $points;
        $membership->save();

        return [
            'success'    => true,
            'message'    => 'تم استرداد النقاط بنجاح',
            'membership' => $membership->load('tier'),
        ];
    }

    /**
     * Check if user qualifies for a higher tier and upgrade if so.
     */
    public function checkAndUpgradeTier(UserMembership $membership): bool
    {
        $currentTier = $membership->tier;

        // Find the highest tier the user qualifies for based on total points earned
        $newTier = MembershipTier::where('min_points', '<=', $membership->total_points_earned)
            ->where('level', '>', $currentTier->level)
            ->orderBy('level', 'desc')
            ->first();

        if ($newTier) {
            $membership->membership_tier_id = $newTier->id;
            $membership->save();

            $this->grantTierWalletBonuses($membership->user_id, $currentTier->level, $newTier->level);

            // Send upgrade notification via SMS
            $this->sendTierUpgradeNotification($membership->user, $newTier);

            return true;
        }

        return false;
    }

    /**
     * Wallet bonus configured on each tier the member just passed (benefits.wallet_bonus),
     * credited once per member and tier as promotional balance.
     */
    protected function grantTierWalletBonuses(int $userId, int $fromLevel, int $toLevel): void
    {
        $tiers = MembershipTier::where('level', '>', $fromLevel)->where('level', '<=', $toLevel)->orderBy('level')->get();

        foreach ($tiers as $tier) {
            $bonus = (float) ($tier->benefits['wallet_bonus'] ?? 0);
            if ($bonus <= 0) {
                continue;
            }
            $days = (int) ($tier->benefits['wallet_bonus_expiry_days'] ?? 0);

            app(WalletLedger::class)->credit($userId, $bonus, 'reward', [
                'bucket' => 'bonus',
                'expires_at' => $days > 0 ? now()->addDays($days) : null,
                'activity_type' => 'tier_bonus',
                'message' => __('wallet_ledger.admin.tier_bonus', ['tier' => $tier->name_ar]),
                'source_type' => 'membership_tier',
                'source_id' => $tier->id,
                'idempotency_key' => "tier_bonus:{$userId}:{$tier->id}",
            ]);
        }
    }

    /**
     * Scan a QR token at a branch and return membership info.
     */
    public function scanQrCode(string $token): array
    {
        $membership = UserMembership::with(['user', 'tier'])
            ->where('qr_code_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$membership) {
            return ['valid' => false, 'message' => 'رمز QR غير صالح أو منتهي الصلاحية'];
        }

        return [
            'valid'       => true,
            'member_name' => $membership->user->full_name,
            'tier'        => $membership->tier->name_ar,
            'tier_level'  => $membership->tier->level,
            'points'      => $membership->points_balance,
            'benefits'    => $membership->tier->benefits,
            'discount'    => $membership->tier->discount_percentage,
            'qr_token'    => $membership->qr_code_token,
        ];
    }

    /**
     * Generate a unique, secure QR token.
     */
    public function generateQrToken(): string
    {
        do {
            $token = Str::upper(Str::random(12));
        } while (UserMembership::where('qr_code_token', $token)->exists());

        return $token;
    }

    /**
     * Regenerate a user's QR code token.
     */
    public function regenerateQrCode(User $user): UserMembership
    {
        $membership = $this->getOrCreateMembership($user);
        $membership->qr_code_token = $this->generateQrToken();
        $membership->save();

        return $membership;
    }

    /**
     * Send SMS notification when a user is upgraded to a new tier.
     */
    protected function sendTierUpgradeNotification(User $user, MembershipTier $tier): void
    {
        if (!$user->mobile) return;

        $tierName = app()->getLocale() === 'ar' ? $tier->name_ar : $tier->name_en;

        $message = "مبروك! 🎉 تمت ترقية عضويتك في رونق إلى مستوى {$tierName}. "
            . "استمتع بمزايا حصرية جديدة وخصومات مميزة. شكراً لولائك!";

        $this->smsService->sendSms($user->mobile, $message);
    }

    /**
     * Seed default membership tiers if none exist.
     */
    public function seedDefaultTiers(): void
    {
        $tiers = [
            [
                'name_en'             => 'Member',
                'name_ar'             => 'عضو',
                'level'               => 1,
                'min_points'          => 0,
                'discount_percentage' => 0,
                'benefits'            => [
                    'ar' => ['بطاقة عضوية رقمية', 'رمز QR خاص', 'عرض النقاط والرصيد'],
                    'en' => ['Digital membership card', 'Personal QR Code', 'Points & balance display'],
                ],
            ],
            [
                'name_en'             => 'Silver',
                'name_ar'             => 'فضي',
                'level'               => 2,
                'min_points'          => 500,
                'discount_percentage' => 5,
                'benefits'            => [
                    'ar' => ['خصم 5% على جميع الخدمات', 'أولوية في الحجز', 'بطاقة عضوية فضية', 'رمز QR خاص'],
                    'en' => ['5% discount on all services', 'Booking priority', 'Silver membership card', 'Personal QR Code'],
                ],
            ],
            [
                'name_en'             => 'Gold',
                'name_ar'             => 'ذهبي',
                'level'               => 3,
                'min_points'          => 1500,
                'discount_percentage' => 10,
                'benefits'            => [
                    'ar' => ['خصم 10% على جميع الخدمات', 'خدمة عملاء مخصصة', 'عروض حصرية', 'دعوة لفعاليات مميزة', 'رمز QR خاص'],
                    'en' => ['10% discount on all services', 'Dedicated customer service', 'Exclusive offers', 'VIP event invitations', 'Personal QR Code'],
                ],
            ],
            [
                'name_en'             => 'Platinum',
                'name_ar'             => 'بلاتيني',
                'level'               => 4,
                'min_points'          => 5000,
                'discount_percentage' => 20,
                'benefits'            => [
                    'ar' => ['خصم 20% على جميع الخدمات', 'خدمة بريميوم مخصصة', 'هدايا حصرية', 'دخول مجاني للفعاليات', 'أولوية قصوى في الحجز', 'رمز QR خاص'],
                    'en' => ['20% discount on all services', 'Dedicated premium service', 'Exclusive gifts', 'Free event access', 'Top booking priority', 'Personal QR Code'],
                ],
            ],
        ];

        foreach ($tiers as $tier) {
            MembershipTier::firstOrCreate(['level' => $tier['level']], $tier);
        }
    }

    /**
     * Get all membership tiers.
     */
    public function getAllTiers()
    {
        return MembershipTier::orderBy('level')->get();
    }

    /**
     * Get a user's full membership details.
     */
    public function getMembershipDetails(User $user): array
    {
        $membership = $this->getOrCreateMembership($user);
        $allTiers   = MembershipTier::orderBy('level')->get();
        $currentTier = $membership->tier;

        // Find next tier
        $nextTier = $allTiers->where('level', $currentTier->level + 1)->first();
        $pointsToNextTier = $nextTier
            ? max(0, $nextTier->min_points - $membership->total_points_earned)
            : 0;

        return [
            'membership'          => $membership,
            'current_tier'        => $currentTier,
            'next_tier'           => $nextTier,
            'points_balance'      => $membership->points_balance,
            'total_points_earned' => $membership->total_points_earned,
            'points_to_next_tier' => $pointsToNextTier,
            'progress_percentage' => $nextTier
                ? min(100, round(($membership->total_points_earned - $currentTier->min_points) / ($nextTier->min_points - $currentTier->min_points) * 100))
                : 100,
            'qr_token'            => $membership->qr_code_token,
            'all_tiers'           => $allTiers,
        ];
    }
}
