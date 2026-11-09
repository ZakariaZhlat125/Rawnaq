<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerMessages
{
    public const BIRTHDAY_MESSAGE = 'كل عام وأنت بخير يا {name}! أسرة رونق تتمنى لك عيد ميلاد سعيداً وعاماً مليئاً بالسعادة.';

    public function customers()
    {
        return User::role('user')->where('status', 1)->where('is_banned', 0);
    }

    public function create(string $occasion, string $message, $customers, ?string $birthdayKey = null): int
    {
        return DB::transaction(function () use ($occasion, $message, $customers, $birthdayKey) {
            if ($birthdayKey && ($existing = DB::table('customer_message_campaigns')->where('birthday_key', $birthdayKey)->value('id'))) {
                return (int) $existing;
            }
            $id = DB::table('customer_message_campaigns')->insertGetId([
                'occasion' => $occasion, 'message' => $message, 'send_at' => now(),
                'birthday_key' => $birthdayKey, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $customers->select('users.id')->chunkById(200, function ($users) use ($id) {
                DB::table('customer_message_deliveries')->insert($users->map(fn ($user) => [
                    'campaign_id' => $id, 'user_id' => $user->id, 'status' => 'pending',
                    'created_at' => now(), 'updated_at' => now(),
                ])->all());
            }, 'users.id', 'id');
            return $id;
        });
    }

    public function birthdays(): ?int
    {
        if (! Setting::get('birthday_messages_enabled', '1')) return null;
        $today = now();
        return $this->create('أعياد ميلاد '.$today->toDateString(),
            Setting::get('birthday_message', self::BIRTHDAY_MESSAGE),
            $this->customers()->whereMonth('date_of_birth', $today->month)->whereDay('date_of_birth', $today->day),
            $today->toDateString());
    }

    public function sendCampaign(int $campaignId, TaqnyatSmsService $gateway): int
    {
        $count = 0;
        DB::table('customer_message_deliveries as d')
            ->join('customer_message_campaigns as c', 'c.id', '=', 'd.campaign_id')
            ->where('d.status', 'pending')->where('c.id', $campaignId)
            ->select('d.id', 'd.user_id', 'c.message', 'c.occasion')->orderBy('d.id')
            ->chunkById(100, function ($deliveries) use ($gateway, &$count) {
                foreach ($deliveries as $delivery) {
                    // Claim atomically so simultaneous scheduler runs cannot send twice.
                    if (! DB::table('customer_message_deliveries')->where('id', $delivery->id)->where('status', 'pending')
                        ->update(['status' => 'processing', 'updated_at' => now()])) continue;
                    $status = 'failed'; $error = null;
                    try {
                        $user = $this->customers()->find($delivery->user_id);
                        if (! $user || ! trim((string) $user->mobile)) {
                            throw new \RuntimeException('العميل غير متاح أو ليس لديه رقم جوال.');
                        }
                        $message = strtr($delivery->message, ['{name}' => trim($user->full_name), '{occasion}' => $delivery->occasion]);
                        $result = $gateway->sendWhatsApp($user->mobile, $message);
                        if (! $result) throw new \RuntimeException('تعذر الإرسال عبر بوابة الرسائل.');
                        $status = ($result['status'] ?? null) === 'bypassed_local' ? 'simulated' : 'sent';
                    } catch (\Throwable $e) {
                        $error = mb_substr($e->getMessage(), 0, 1000);
                        report($e);
                    }
                    DB::table('customer_message_deliveries')->where('id', $delivery->id)->update([
                        'status' => $status, 'error' => $error, 'sent_at' => $status === 'sent' ? now() : null, 'updated_at' => now(),
                    ]);
                    $count++;
                }
            }, 'd.id', 'id');
        return $count;
    }
}
