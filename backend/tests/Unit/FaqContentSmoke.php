<?php
namespace App\Models {
    class Setting {
        public static $value = '[]';
        public static function get($key, $default = null) { return self::$value; }
    }
}
namespace {
    require __DIR__.'/../../app/Services/FaqContent.php';
    function check($condition) { if (!$condition) throw new \RuntimeException('FAQ check failed'); }
    $content = new \App\Services\FaqContent;
    check($content->published() === []);
    $rows = [
        ['question' => ['ar' => 'سؤال أول', 'en' => 'First'], 'answer' => ['ar' => 'إجابة', 'en' => 'Answer'], 'active' => true],
        ['question' => ['ar' => 'مخفي'], 'answer' => ['ar' => 'مخفي'], 'active' => false],
        ['question' => ['ar' => 'سؤال أخير'], 'answer' => ['ar' => 'الإجابة الأخيرة'], 'active' => true],
    ];
    \App\Models\Setting::$value = json_encode($rows);
    check($content->all() === $rows);
    check($content->published() === [$rows[0], $rows[2]]);
    \App\Models\Setting::$value = 'invalid';
    check($content->all() === []);
    echo "FAQ empty state, publishing, ordering, translations and invalid-setting fallback passed.\n";
}
