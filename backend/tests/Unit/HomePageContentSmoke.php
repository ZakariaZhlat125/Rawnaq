<?php
namespace App\Models { class Setting { public static $value = '{}'; public static function get($key, $default = null) { return self::$value; } } }
namespace {
function resource_path($path) { return __DIR__.'/../../resources/'.$path; }
require __DIR__.'/../../app/Services/HomePageContent.php';
function check($value) { if (!$value) throw new \RuntimeException('Home content check failed'); }
$content = new \App\Services\HomePageContent;
$defaults = $content->get();
foreach ($content->fields() as $key=>$field) { check(!empty($defaults[$key]['ar'])); check(!empty($defaults[$key]['en'])); }
\App\Models\Setting::$value = json_encode(['hero_title'=>['en'=>'Custom title'], 'about_image'=>'https://example.com/upload.png']);
$saved = $content->get();
check($saved['hero_title']['en'] === 'Custom title');
check($saved['hero_title']['ar'] === $defaults['hero_title']['ar']);
check($saved['about_image'] === 'https://example.com/upload.png');
\App\Models\Setting::$value = 'invalid';
check($content->get() === $defaults);
echo "Home content defaults, translations, saved values and invalid-setting fallback passed.\n";
}
