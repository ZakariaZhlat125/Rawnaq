<?php
namespace App\Jobs;
use App\Models\BrowserPushSubscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
class SendBrowserPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;
    public $timeout = 120;
    public function __construct(public string $notificationId) {}
    public function backoff(): array { return [30, 120, 600]; }
    public function handle(): void
    {
        if (!config('webpush.enabled')) return;
        $notification = DatabaseNotification::find($this->notificationId);
        if (!$notification || $notification->read_at || $notification->created_at->lt(now()->subDay())) return;
        $user = User::find($notification->notifiable_id);
        if (!$user || (int) $user->status !== 1 || $user->is_banned) return;
        $push = new WebPush(['VAPID' => ['subject' => config('webpush.subject'), 'publicKey' => config('webpush.public_key'), 'privateKey' => config('webpush.private_key')]], ['TTL' => 3600], 15, ['allow_redirects' => false]);
        $data = $notification->data;
        $payload = json_encode([
            'title' => mb_substr(strip_tags($data['subject'] ?? $data['data']['type'] ?? 'رونق'), 0, 100),
            'body' => mb_substr(html_entity_decode(strip_tags($data['data']['message'] ?? $data['message'] ?? 'لديك إشعار جديد'), ENT_QUOTES, 'UTF-8'), 0, 300),
            'tag' => $notification->id, 'url' => '/account',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $retry = false;
        foreach (BrowserPushSubscription::where('user_id', $user->id)->where('expires_at', '>', now())->cursor() as $subscription) {
            if (!BrowserPushSubscription::validEndpoint($subscription->endpoint)) continue;
            $report = $push->sendOneNotification(Subscription::create(['endpoint' => $subscription->endpoint, 'keys' => ['p256dh' => $subscription->public_key, 'auth' => $subscription->auth_token]]), $payload);
            if ($report->isSubscriptionExpired()) $subscription->delete();
            elseif (!$report->isSuccess()) $retry = true;
        }
        if ($retry) throw new \RuntimeException('Browser push delivery failed; retry pending.');
    }
}
