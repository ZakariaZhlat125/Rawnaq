<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
class RealtimeNotifications {
    public function ready(): bool {
        $p = config('broadcasting.connections.pusher');
        return (bool)config('realtime.enabled') && !empty($p['key']) && !empty($p['secret']) && !empty($p['app_id']) && !empty($p['options']['cluster']);
    }
    public function publish(string $id): void {
        if (!$this->ready()) return;
        $notification = DatabaseNotification::find($id);
        if (!$notification || $notification->notifiable_type !== (new User)->getMorphClass()) return;
        try {
            // A small invalidation event; clients fetch their authorized inbox, never another user's payload.
            Broadcast::connection('pusher')->broadcast(['private-notifications.user.'.$notification->notifiable_id], 'notification.created', ['id'=>(string)$notification->id]);
        } catch (\Throwable $e) {
            // Notification persistence and booking/payment completion must survive a Pusher outage.
            Log::warning('Realtime notification delivery unavailable', ['notification_id'=>$id,'exception'=>get_class($e)]);
        }
    }
}
