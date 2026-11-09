<?php
// Run directly with PHP; uses isolated storage/model doubles, no database writes.
namespace App\Models {
    class Setting {
        public static array $data = [];
        public static function get($key, $default = null) { return self::$data[$key] ?? $default; }
    }
}
namespace {
    function config($key) { return require __DIR__.'/../../config/frontend_seo.php'; }
    require __DIR__.'/../../app/Services/FrontendSeo.php';
    $seo = new \App\Services\FrontendSeo;
    $count = 0;
    function check($value, $message) {
        global $count;
        if (!$value) throw new \RuntimeException($message);
        $count++;
    }
    check($seo->normalize('/services.html?ref=x') === '/services', 'Alias normalization');
    check($seo->normalize('/about.html') === '/page-about', 'About alias');
    check($seo->normalize('/index.html') === '/', 'Home alias');
    check($seo->resolve('/store', 'en')['title'] === 'Products | Rawnaq', 'English defaults');
    check($seo->resolve('/store', 'ar')['title'] === 'المنتجات | رونق', 'Arabic defaults');
    \App\Models\Setting::$data['frontend_seo'] = json_encode(['/store'=>[
        'en'=>['title'=>'Custom product title', 'description'=>'', 'keywords'=>'grooming, care'],
        'ar'=>['title'=>'عنوان المنتجات', 'description'=>'وصف المنتجات', 'keywords'=>'عناية، منتجات'],
    ]]);
    check($seo->resolve('/store', 'en')['title'] === 'Custom product title', 'Saved override');
    check($seo->resolve('/store', 'en')['description'] === config('frontend_seo')['/store']['en']['description'], 'Empty field fallback');
    check($seo->resolve('/store', 'ar')['description'] === 'وصف المنتجات', 'Language isolation');
    check($seo->resolve('/gifts', 'en')['title'] === 'Gifts | Rawnaq', 'Page isolation');
    check($seo->resolve('/not-a-page', 'en') === null, 'Unknown route rejected');
    check($seo->text('<b>Safe</b> &amp; sound', 'en') === 'Safe & sound', 'Plain text sanitization');
    check($seo->text('{"ar":"عربي","en":"English"}', 'en') === 'English', 'JSON translations');
    check($seo->text(['ar'=>'عربي'], 'en') === '', 'Missing language falls back to page default');
    check($seo->resolve('/store', 'en')['keywords'] === 'grooming, care', 'Saved English keywords');
    check($seo->resolve('/store', 'ar')['keywords'] === 'عناية، منتجات', 'Saved Arabic keywords');
    check($seo->resolve('/gifts', 'en')['keywords'] === '', 'Unset keywords remain empty');
    echo "Passed $count SEO checks.\n";
}
