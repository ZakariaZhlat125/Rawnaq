<?php
namespace App\Observers;
use App\Services\RealtimeNotifications;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
class RealtimeNotificationObserver {
    public function created(DatabaseNotification $notification): void {
        if (!app(RealtimeNotifications::class)->ready()) return;
        $id = (string)$notification->id;
        DB::afterCommit(fn()=>app(RealtimeNotifications::class)->publish($id));
    }
}
