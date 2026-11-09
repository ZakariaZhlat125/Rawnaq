<?php
namespace App\Services;
use App\Models\Setting;
class HomePageContent
{
    public function fields(): array { return json_decode(file_get_contents(resource_path('data/home-page-fields.json')), true, 512, JSON_THROW_ON_ERROR); }
    public function get(): array {
        $defaults = json_decode(file_get_contents(resource_path('data/home-page-defaults.json')), true, 512, JSON_THROW_ON_ERROR);
        $saved = json_decode(Setting::get('home_page_content', '{}'), true);
        return array_replace_recursive($defaults, is_array($saved) ? $saved : []);
    }
}
