<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only report of balances that may have been created without a payment:
 *  1. top-ups through the retired POST /api/wallet-top-up (it never verified a payment);
 *  2. wallets holding a balance with no history at all (POST /api/save-wallet accepted an amount).
 * Nothing is changed; review the output and correct balances with /app/wallet adjustments.
 */
class AuditWalletTopups extends Command
{
    protected $signature = 'wallet:audit-topups {--csv= : Write the findings to this CSV file}';

    protected $description = 'List wallet balances that were added without a verified payment';

    public function handle(): int
    {
        $apiTopups = DB::table('wallet_histories as h')
            ->leftJoin('users as u', 'u.id', '=', 'h.user_id')
            ->where('h.activity_type', 'wallet_top_up')
            ->whereNull('h.direction') // ledger rows (real top-ups) have a direction
            ->orderBy('h.id')
            ->get(['h.id', 'h.user_id', 'u.mobile', 'h.activity_data', 'h.created_at'])
            ->map(function ($row) {
                $data = json_decode($row->activity_data ?? '{}', true) ?: [];

                return [
                    'finding' => 'api_topup_without_payment',
                    'user_id' => $row->user_id,
                    'mobile' => $row->mobile,
                    'amount' => (float) ($data['credit_debit_amount'] ?? 0),
                    'reference' => $data['transaction_id'] ?? '',
                    'history_id' => $row->id,
                    'date' => $row->created_at,
                ];
            });

        $orphanWallets = DB::table('wallet as w')
            ->leftJoin('users as u', 'u.id', '=', 'w.user_id')
            ->where('w.amount', '>', 0)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('wallet_histories as h')->whereColumn('h.user_id', 'w.user_id'))
            ->get(['w.user_id', 'u.mobile', 'w.amount', 'w.created_at'])
            ->map(fn ($row) => [
                'finding' => 'balance_without_history',
                'user_id' => $row->user_id,
                'mobile' => $row->mobile,
                'amount' => (float) $row->amount,
                'reference' => '',
                'history_id' => null,
                'date' => $row->created_at,
            ]);

        $rows = $apiTopups->concat($orphanWallets);
        if ($rows->isEmpty()) {
            $this->info('No suspicious wallet balances found.');

            return self::SUCCESS;
        }

        $this->table(array_keys($rows->first()), $rows->all());
        $this->warn(sprintf('%d finding(s), %.2f SAR in total, %d customer(s).', $rows->count(), $rows->sum('amount'), $rows->pluck('user_id')->unique()->count()));

        if ($path = $this->option('csv')) {
            $handle = fopen($path, 'w');
            fputcsv($handle, array_keys($rows->first()));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
            $this->info("Written to {$path}");
        }

        return self::SUCCESS;
    }
}
