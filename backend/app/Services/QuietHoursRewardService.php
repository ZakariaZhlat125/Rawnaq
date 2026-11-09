<?php
namespace App\Services;

use App\Models\BranchQuietHours;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;

class QuietHoursRewardService
{
    private ?bool $ready = null;

    public function available(): bool
    {
        return $this->ready ??= Schema::hasTable('branch_quiet_hours')
            && Schema::hasColumn('bookings', 'quiet_hours_points')
            && Schema::hasColumn('bookings', 'quiet_hours_credited_at');
    }

    public function snapshot(Booking $booking): void
    {
        if (!$this->available()) return;
        // Reward fields are server-owned, regardless of mass-assigned request data.
        $booking->quiet_hours_credited_at = $booking->getRawOriginal('quiet_hours_credited_at');
        if (!$booking->exists || (!$booking->getRawOriginal('quiet_hours_credited_at')
            && $booking->getRawOriginal('status') !== 'completed'
            && $booking->isDirty(['start_date_time', 'branch_id', 'user_id', 'home_city_id']))) {
            $settings = BranchQuietHours::where('branch_id', $booking->branch_id)->first();
            $booking->quiet_hours_points = $settings && !$booking->home_city_id
                ? app(QuietHoursRule::class)->points($settings->toArray(), (string) $booking->start_date_time) : 0;
        } else {
            $booking->quiet_hours_points = (int) $booking->getRawOriginal('quiet_hours_points');
        }
    }

    public function award(Booking $booking): void
    {
        if ($booking->status !== 'completed' || !$booking->user_id || !$this->available()) return;
        DB::transaction(function () use ($booking) {
            $row = Booking::whereKey($booking->id)->lockForUpdate()->first();
            if (!$row || $row->status !== 'completed' || $row->quiet_hours_credited_at || $row->quiet_hours_points <= 0) return;
            // Serialize first-time balance creation as well as existing balances.
            if (!User::whereKey($row->user_id)->lockForUpdate()->first()) return;
            $balance = LoyaltyPoint::where('user_id', $row->user_id)->lockForUpdate()->first()
                ?? LoyaltyPoint::create(['user_id' => $row->user_id, 'points' => 0]);
            $balance->points = (int) $balance->points + (int) $row->quiet_hours_points;
            $balance->save();
            LoyaltyPointTransaction::create([
                'user_id' => $row->user_id, 'action' => 'add', 'points' => $row->quiet_hours_points,
                'balance_after' => $balance->points, 'source' => 'quiet_hours', 'source_id' => $row->id,
                'meta' => ['label' => 'مكافأة أوقات الهدوء', 'booking_id' => $row->id,
                    'branch_id' => $row->branch_id, 'start_date_time' => (string) $row->start_date_time],
            ]);
            // Query builder avoids firing booking observers recursively.
            $creditedAt = now();
            DB::table('bookings')->where('id', $row->id)->update(['quiet_hours_credited_at' => $creditedAt]);
            $booking->quiet_hours_credited_at = $creditedAt;
            $booking->syncOriginalAttribute('quiet_hours_credited_at');
        });
    }
}
