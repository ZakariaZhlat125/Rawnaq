<?php
namespace App\Observers;
use App\Jobs\SendBrowserPush;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
class BrowserPushObserver
{
    public function created(DatabaseNotification $notification): void
    {
        if (!config('webpush.enabled') || $notification->notifiable_type !== (new User)->getMorphClass()) return;
        SendBrowserPush::dispatch($notification->id)->onConnection(config('webpush.connection'))->onQueue('webpush')->afterCommit();
    }
}
