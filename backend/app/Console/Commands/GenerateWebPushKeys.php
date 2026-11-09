<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
class GenerateWebPushKeys extends Command
{
    protected $signature = 'webpush:keys';
    protected $description = 'Generate VAPID keys for the private server environment';
    public function handle(): int
    {
        if (!class_exists(VAPID::class)) { $this->error('Run composer install first.'); return self::FAILURE; }
        $keys = VAPID::createVapidKeys();
        $this->warn('Store the private key only in your server .env. Do not commit or share it.');
        $this->line('WEBPUSH_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('WEBPUSH_PRIVATE_KEY='.$keys['privateKey']);
        return self::SUCCESS;
    }
}
