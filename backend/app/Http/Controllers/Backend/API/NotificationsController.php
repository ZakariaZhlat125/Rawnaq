<?php
namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function notificationList(Request $request)
    {
        $request->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100']);
        if ($request->type === 'mark_as_read') {
            $request->user()->unreadNotifications()->update(['read_at' => now()]);
        }
        return $this->snapshot($request);
    }

    public function index(Request $request)
    {
        return $this->notificationList($request);
    }

    private function snapshot(Request $request): array
    {
        $notifications = $request->user()->notifications()->reorder()->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(min(100, max(1, (int) $request->input('per_page', 30))));
        return [
            'status' => true,
            'notification_data' => $notifications->items(),
            'all_unread_count' => $request->user()->unreadNotifications()->count(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
        ];
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return ['status' => true];
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return ['status' => true];
    }

    public function stream(Request $request)
    {
        return response()->stream(function () use ($request) {
            $deadline = microtime(true) + 20;
            $previous = null;
            do {
                if (connection_aborted()) break;
                $payload = json_encode($this->snapshot($request), JSON_UNESCAPED_UNICODE);
                if ($payload !== $previous) {
                    echo 'data: '.$payload."\n\n";
                    $previous = $payload;
                } else {
                    echo ": heartbeat\n\n";
                }
                if (ob_get_level() > 0) @ob_flush();
                flush();
                usleep(1000000);
            } while (microtime(true) < $deadline);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}