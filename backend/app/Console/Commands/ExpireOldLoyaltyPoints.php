<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use Carbon\Carbon;

class ExpireOldLoyaltyPoints extends Command
{

    protected $signature = 'loyalty:expire-points';
    protected $description = 'Delete loyalty points transactions that exceed the validity period set in the control panel';


    public function handle()
    {
// 1. Load validity settings from control panel
        $validityValue = Setting::where('name', 'points_validity_value')->value('val');
        $validityUnit  = Setting::where('name', 'points_validity_unit')->value('val') ?? 'months';

        if (!$validityValue || $validityValue <= 0) {
            $this->warn('Points validity is not configured. Skipping.');
            return Command::SUCCESS;
        }

        // 2. Calculate the cutoff date
        $cutoffDate = match ($validityUnit) {
            'days'  => Carbon::now()->subDays($validityValue),
            'years' => Carbon::now()->subYears($validityValue),
            default => Carbon::now()->subMonths($validityValue), // months
        };

        $this->info("Validity period  : {$validityValue} {$validityUnit}");
        $this->info("Expiring points added before : {$cutoffDate->toDateTimeString()}");

        // 3. Fetch expired transactions grouped by user
        //    Only consider 'add' actions so we don't double-expire deductions
        $expiredByUser = DB::table('loyalty_points_transactions')
            ->select('user_id', DB::raw('SUM(points) as total_expired'))
            ->where('created_at', '<', $cutoffDate)
            ->where('action', 'add')
            ->whereNull('expired_at')           // skip already expired rows
            ->groupBy('user_id')
            ->get();

        if ($expiredByUser->isEmpty()) {
            $this->info('No expired points found.');
            return Command::SUCCESS;
        }

        $now = Carbon::now();

        foreach ($expiredByUser as $row) {
            DB::transaction(function () use ($row, $now, $cutoffDate) {

                // 4. Get current balance from loyalty_points table
                $loyaltyPoint = DB::table('loyalty_points')
                    ->where('user_id', $row->user_id)
                    ->first();

                if (!$loyaltyPoint) {
                    $this->warn("No loyalty_points record found for user_id {$row->user_id}. Skipping.");
                    return;
                }

                // 5. Calculate how many points to actually deduct (can't go below 0)
                $pointsToDeduct = min($row->total_expired, $loyaltyPoint->points);
                $newBalance     = $loyaltyPoint->points - $pointsToDeduct;

                // 6. Deduct from loyalty_points balance
                DB::table('loyalty_points')
                    ->where('user_id', $row->user_id)
                    ->update(['points' => $newBalance]);

                // 7. Insert an expiry transaction to log the deduction
                \App\Models\LoyaltyPointTransaction::create([
                    'user_id'       => $row->user_id,
                    'action'        => 'deduct',
                    'points'        => $pointsToDeduct,
                    'balance_after' => $newBalance,
                    'source'        => 'system',
                    'source_id'     => null,
                    'meta'          => [
                        'reason'         => 'Points expired',
                        'expired_before' => $cutoffDate->toDateTimeString(),
                    ],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);

                // 8. Mark original transactions as expired so they won't be processed again
                DB::table('loyalty_points_transactions')
                    ->where('user_id', $row->user_id)
                    ->where('created_at', '<', $cutoffDate)
                    ->where('action', 'add')
                    ->whereNull('expired_at')
                    ->update(['expired_at' => $now]);
            });

            $this->info("User {$row->user_id} — expired {$row->total_expired} point(s).");
        }

        $this->info('Done. All expired points processed.');

        return Command::SUCCESS;
    }
}
