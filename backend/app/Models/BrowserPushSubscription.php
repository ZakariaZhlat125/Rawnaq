<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BrowserPushSubscription extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['endpoint', 'public_key', 'auth_token'];
    protected $casts = ['expires_at' => 'datetime'];

    public static function validEndpoint(string $endpoint): bool
    {
        $url = parse_url($endpoint);
        if (!$url || ($url['scheme'] ?? '') !== 'https' || isset($url['user']) || isset($url['pass']) || isset($url['fragment']) || (isset($url['port']) && $url['port'] !== 443)) return false;
        $host = strtolower($url['host'] ?? '');
        return $host === 'fcm.googleapis.com' || $host === 'updates.push.services.mozilla.com'
            || str_ends_with($host, '.push.services.mozilla.com')
            || $host === 'web.push.apple.com' || str_ends_with($host, '.notify.windows.com');
    }
}
