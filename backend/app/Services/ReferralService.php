<?php
namespace App\Services;
use App\Models\User;
use App\Models\Setting;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ReferralService
{
    public function normalize(?string $code): string { return strtoupper(trim($code ?? '')); }
    public function owner(?string $code): ?User {
        $code = $this->normalize($code);
        if ($code === '') return null;
        $owner = User::active()->where('referral_code', $code)->first();
        if (!$owner) throw ValidationException::withMessages(['referral_code'=>__('referral.invalid')]);
        return $owner;
    }
    public function award(User $member, ?string $code): void {
        if ($this->normalize($code) === '') return;
        DB::transaction(function () use ($member, $code) {
            $newUser = User::whereKey($member->id)->lockForUpdate()->firstOrFail();
            if ($newUser->referral_rewarded_at) return;
            $owner = $this->owner($code);
            if ($owner->id === $newUser->id) throw ValidationException::withMessages(['referral_code'=>__('referral.invalid')]);
            // Serialize awards to one inviter, including creation of their first balance row.
            $owner = User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
            if (!(int) $owner->status || (int) $owner->is_banned) throw ValidationException::withMessages(['referral_code'=>__('referral.invalid')]);
            $ownerPoints = max(0, min(1000000, (int) Setting::get('referral_points', 0)));
            $memberPoints = max(0, min(1000000, (int) Setting::get('referral_new_user_points', $ownerPoints)));
            $this->credit($owner->id, $ownerPoints, 'referral', $newUser->id, ['referred_user_id'=>$newUser->id]);
            $this->credit($newUser->id, $memberPoints, 'referral_registration', $newUser->id, ['referrer_user_id'=>$owner->id]);
            $newUser->forceFill([
                'referred_by_user_id'=>$owner->id, 'referral_rewarded_at'=>now(),
                'registration_referral_code'=>$this->normalize($code),
                'referral_owner_points'=>$ownerPoints, 'referral_member_points'=>$memberPoints,
            ])->save();
        });
    }

    private function credit(int $userId, int $points, string $source, int $memberId, array $meta): void
    {
        if ($points <= 0) return;
        $balance = LoyaltyPoint::where('user_id', $userId)->lockForUpdate()->first();
        if (!$balance) $balance = LoyaltyPoint::create(['user_id'=>$userId, 'points'=>0]);
        $balance->increment('points', $points);
        $balance->refresh();
        LoyaltyPointTransaction::create([
            'user_id'=>$userId, 'action'=>'add', 'points'=>$points,
            'balance_after'=>$balance->points, 'source'=>$source, 'source_id'=>$memberId, 'meta'=>$meta,
        ]);
    }
}
