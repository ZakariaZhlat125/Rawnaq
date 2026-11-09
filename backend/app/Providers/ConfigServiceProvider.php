<?php

namespace App\Providers;

use Config;
use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        try {
            // Setting invalidates this cache when values change. Booting every
            // API request no longer checks the schema and reloads the table.
            $settings = Setting::getAllSettings()->whereIn('type', ['mail_config', 'general', 'misc']);
            foreach ([
                'app_name' => 'app.name',
                'mail_driver' => 'mail.driver',
                'mail_host' => 'mail.host',
                'mail_port' => 'mail.port',
                'mail_from' => 'mail.from.address',
                'from_name' => 'mail.from.name',
                'mail_encryption' => 'mail.encryption',
                'mail_username' => 'mail.username',
                'mail_password' => 'mail.password',
                'default_language' => 'app.locale',
            ] as $name => $configKey) {
                $value = $this->getSetting($settings, $name);
                if ($value !== null) Config::set($configKey, $value);
            }
        } catch (\Exception $e) {
        }
    }

    public function getSetting($model, $name)
    {
        $query = $model->where('name', $name)->first();
        if (isset($query)) {
            return $query->val;
        }

        return null;
    }
}
