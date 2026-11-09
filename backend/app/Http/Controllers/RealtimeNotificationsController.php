<?php
namespace App\Http\Controllers;
use App\Services\RealtimeNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
class RealtimeNotificationsController extends Controller {
    public function config(Request $request) {
        $api = $request->is('api/*');
        return response()->json([
            'enabled'=>app(RealtimeNotifications::class)->ready(),
            'key'=>config('broadcasting.connections.pusher.key'),
            'cluster'=>config('broadcasting.connections.pusher.options.cluster'),
            'channel'=>'private-notifications.user.'.$request->user()->id,
            'auth_url'=>$api ? route('api.realtime.auth') : route('broadcasting.auth'),
        ])->header('Cache-Control','private, no-store');
    }
    public function auth(Request $request) {
        abort_unless(app(RealtimeNotifications::class)->ready(),503);
        $data = $request->validate(['socket_id'=>['required','regex:/^\d+\.\d+$/'],'channel_name'=>['required','string','max:200']]);
        abort_unless($data['channel_name'] === 'private-notifications.user.'.$request->user()->id,403);
        $driver = Broadcast::connection('pusher');
        // Channel callbacks belong to a broadcaster instance. Register on this explicit
        // connection as well, even when the application's default driver is log/null.
        $driver->channel('notifications.user.{id}', fn($user,$id)=>(int)$user->id===(int)$id);
        return $driver->auth($request);
    }
}
