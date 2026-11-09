<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Booking\Models\Booking;

class ServiceRepeatRewards
{
    public function available(): bool { return Schema::hasTable('service_repeat_rules') && Schema::hasColumn('bookings', 'service_repeat_reward_id') && Schema::hasColumn('bookings','service_repeat_evaluated_at'); }
    public function enabled(): bool { return $this->available() && (bool)DB::table('service_repeat_settings')->where('id',1)->value('enabled'); }

    /** Called inside the cart transaction. Locking the owner serializes earning and redemption. */
    public function reserve(int $userId, int $serviceId, int $rewardId, Booking $booking): void {
        if (!$this->enabled() || (int)$booking->user_id !== $userId || !$booking->exists || $booking->service_repeat_reward_id) $this->reject();
        User::whereKey($userId)->lockForUpdate()->firstOrFail();
        $reward = DB::table('service_repeat_rewards')->where('id',$rewardId)->where('user_id',$userId)->where('service_id',$serviceId)->lockForUpdate()->first();
        if (!$reward || $reward->booking_id || $reward->used_at || !DB::table('service_repeat_rules')->where('id',$reward->rule_id)->where('enabled',true)->exists()) $this->reject();
        DB::table('service_repeat_rewards')->where('id',$rewardId)->update(['booking_id'=>$booking->id,'updated_at'=>now()]);
        DB::table('bookings')->where('id',$booking->id)->update(['service_repeat_reward_id'=>$rewardId]);
        $booking->service_repeat_reward_id = $rewardId;
        $booking->syncOriginalAttribute('service_repeat_reward_id');
    }

    private function reject(): void { throw ValidationException::withMessages(['repeat_reward_id'=>__('service_repeat.unavailable')]); }

    public function protect(Booking $booking): void {
        if (!$this->available()) return;
        $stored = $booking->exists ? DB::table('bookings')->where('id',$booking->id)->first(['service_repeat_reward_id','service_repeat_evaluated_at']) : null;
        $booking->service_repeat_reward_id = $stored?->service_repeat_reward_id;
        $booking->service_repeat_evaluated_at = $stored?->service_repeat_evaluated_at;
        if ($booking->service_repeat_reward_id && !in_array($booking->status,['cancelled','canceled'],true)) {
            $reward = DB::table('service_repeat_rewards')->where('id',$booking->service_repeat_reward_id)->where('booking_id',$booking->id)->where('user_id',$booking->user_id)->first();
            if (!$reward) $this->reject();
        }
    }

    public function release(array $bookingIds): void {
        if (!$this->available() || !$bookingIds) return;
        DB::table('service_repeat_rewards')->whereIn('booking_id',$bookingIds)->whereNull('used_at')->update(['booking_id'=>null,'updated_at'=>now()]);
    }

    public function onSaved(Booking $booking): void {
        if (!$this->available()) return;
        if (in_array($booking->status,['cancelled','canceled'],true)) { $this->release([$booking->id]); return; }
        if ($booking->status !== 'completed' || !$booking->user_id) return;
        DB::transaction(function() use($booking) {
            if (!User::whereKey($booking->user_id)->lockForUpdate()->first()) return;
            $row = Booking::whereKey($booking->id)->lockForUpdate()->first();
            if (!$row || $row->status !== 'completed' || $row->service_repeat_evaluated_at) return;
            // Commit the evaluation and ledger together. Paused/ineligible completions stay excluded,
            // while failures roll back this marker so a normal save can retry safely.
            DB::table('bookings')->where('id',$row->id)->update(['service_repeat_evaluated_at'=>now()]);
            if ($row->service_repeat_reward_id) {
                DB::table('service_repeat_rewards')->where('id',$row->service_repeat_reward_id)->where('booking_id',$row->id)->whereNull('used_at')->update(['used_at'=>now(),'updated_at'=>now()]);
                return;
            }
            // Paid, standalone services only; packages and complimentary bookings do not earn visits.
            if (!$this->enabled() || !$row->bookingTransaction()->exists() || DB::table('booking_packages')->where('booking_id',$row->id)->exists()) return;
            foreach ($row->bookingService()->get()->unique('service_id') as $line) {
                if ((float)$line->service_price - (float)$line->discount_amount - (float)$row->home_service_fee <= 0) continue;
                $rule = DB::table('service_repeat_rules')->where('service_id',$line->service_id)->where('enabled',true)->first();
                $globalStart = DB::table('service_repeat_settings')->where('id',1)->value('starts_at');
                if (!$rule || !$rule->starts_at || !$globalStart || !$row->created_at || $row->created_at->lt($rule->starts_at) || $row->created_at->lt($globalStart)) continue;
                if (DB::table('service_repeat_visits')->where('booking_id',$row->id)->where('service_id',$line->service_id)->exists()) continue;
                DB::table('service_repeat_visits')->insert(['booking_id'=>$row->id,'service_id'=>$line->service_id,'user_id'=>$row->user_id,'rule_id'=>$rule->id,'created_at'=>now(),'updated_at'=>now()]);
                $pending = DB::table('service_repeat_visits')->where('user_id',$row->user_id)->where('rule_id',$rule->id)->whereNull('reward_id');
                while ((clone $pending)->count() >= $rule->required_visits) {
                    $id = DB::table('service_repeat_rewards')->insertGetId(['user_id'=>$row->user_id,'service_id'=>$line->service_id,'rule_id'=>$rule->id,'required_visits'=>$rule->required_visits,'created_at'=>now(),'updated_at'=>now()]);
                    $ids = (clone $pending)->orderBy('id')->limit($rule->required_visits)->pluck('id');
                    DB::table('service_repeat_visits')->whereIn('id',$ids)->update(['reward_id'=>$id,'updated_at'=>now()]);
                }
            }
        }, 3);
    }

    public function summary(int $userId): array {
        if (!$this->available()) return ['progress'=>[], 'rewards'=>[]];
        $rules = DB::table('service_repeat_rules')->get();
        $names = \Modules\Service\Models\Service::whereIn('id',$rules->pluck('service_id'))->get()->keyBy('id');
        $enabled = $this->enabled();
        $progress = $rules->filter(fn($r) => $enabled && $r->enabled)->map(fn($r)=>[
            'service_id'=>$r->service_id,'name'=>$names->get($r->service_id)?->name ?? '', 'required_visits'=>$r->required_visits,
            'completed'=>DB::table('service_repeat_visits')->where('user_id',$userId)->where('rule_id',$r->id)->whereNull('reward_id')->count(),
        ])->values();
        $rewards = DB::table('service_repeat_rewards')->where('user_id',$userId)->orderByDesc('id')->get()->map(function($r) use($rules,$names,$enabled) {
            return ['id'=>$r->id,'service_id'=>$r->service_id,'name'=>$names->get($r->service_id)?->name ?? '',
                'status'=>$r->used_at ? 'used' : ($r->booking_id ? 'reserved' : 'available'),
                'usable'=>$enabled && !$r->used_at && !$r->booking_id && (bool)$rules->firstWhere('id',$r->rule_id)?->enabled];
        });
        return ['progress'=>$progress,'rewards'=>$rewards];
    }
}
