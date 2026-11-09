<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Package\Models\Package;

class NewPackageCustomerNotification
{
    public function send(Package $package): void
    {
        // Explicit Arabic translations keep the message Arabic even in an English admin session.
        $name = trim((string) $package->getTranslation('name', 'ar', false));
        $description = trim(strip_tags((string) $package->getTranslation('description', 'ar', false)));
        $message = 'تمت إضافة باقة جديدة: '.($name ?: 'باقة رونق');
        if ($description !== '') $message .= ' — '.$description;

        User::role('user')->where('status', 1)->where('is_banned', 0)->chunkById(200, function ($users) use ($package, $message) {
            foreach ($users as $user) {
                $user->notifications()->create([
                    'id' => (string) Str::uuid(),
                    'type' => 'package.created',
                    'data' => ['subject' => 'باقة جديدة من رونق', 'data' => [
                        'type' => 'باقة جديدة', 'message' => $message,
                        'notification_group' => 'package', 'id' => $package->id, 'event' => 'package.created',
                    ]],
                ]);
            }
        });
    }
}
