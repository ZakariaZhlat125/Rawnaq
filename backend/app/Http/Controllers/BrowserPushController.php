<?php
namespace App\Http\Controllers;
use App\Models\BrowserPushSubscription;
use Illuminate\Http\Request;
class BrowserPushController extends Controller
{
    public function config()
    {
        return response()->json(['enabled' => (bool) config('webpush.enabled') && (bool) config('webpush.public_key') && (bool) config('webpush.private_key'), 'public_key' => config('webpush.public_key')])->header('Cache-Control', 'no-store');
    }
    public function store(Request $request)
    {
        abort_unless(config('webpush.enabled'), 503);
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048', function ($attribute, $value, $fail) { if (!BrowserPushSubscription::validEndpoint($value)) $fail('عنوان اشتراك المتصفح غير مدعوم.'); }],
            'keys.p256dh' => ['required', 'regex:/^[A-Za-z0-9_-]{87}=?$/'],
            'keys.auth' => ['required', 'regex:/^[A-Za-z0-9_-]{22}(==)?$/'],
        ]);
        $hash = hash('sha256', $data['endpoint']);
        $existing = BrowserPushSubscription::where('endpoint_hash', $hash)->first();
        abort_if($existing && (int) $existing->user_id !== (int) $request->user()->id, 409, 'ألغِ اشتراك الحساب السابق أولًا.');
        BrowserPushSubscription::updateOrCreate(['endpoint_hash' => $hash], [
            'user_id' => $request->user()->id, 'endpoint' => $data['endpoint'],
            'public_key' => $data['keys']['p256dh'], 'auth_token' => $data['keys']['auth'], 'expires_at' => now()->addDays(90),
        ]);
        return response()->json(['status' => true]);
    }
    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => 'required|string|max:2048']);
        BrowserPushSubscription::where('user_id', $request->user()->id)->where('endpoint_hash', hash('sha256', $request->endpoint))->delete();
        return response()->json(['status' => true]);
    }
}
