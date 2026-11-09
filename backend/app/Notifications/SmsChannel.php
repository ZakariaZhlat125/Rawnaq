<?php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\SmsService;

class SmsChannel
{
    public function send($notifiable, Notification $notification)
    {
        $phone = $notifiable->phone_number ?? null;
        if (! $phone) {
            return;
        }

        $message = $notification->toSms($notifiable);

        SmsService::send($phone, $message);
    }
}
