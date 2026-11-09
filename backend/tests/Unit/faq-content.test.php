<?php
namespace App\Models {
    class Setting {
        public static string $value = '[]';
        public static function get($key, $default) { return self::$value; }
    }
}
namespace {
    require __DIR__.'/../../app/Services/FaqContent.php';
    $content = new \App\Services\FaqContent();
    \App\Models\Setting::$value = json_encode([
        ['active' => true, 'question' => ['ar' => 'سؤال', 'en' => 'Question'], 'answer' => ['ar' => 'إجابة', 'en' => 'Answer']],
        ['active' => false, 'question' => 'Hidden'],
        ['active' => 0, 'question' => 'Hidden too'],
    ]);
    $published = $content->published();
    if (count($published) !== 1 || $published[0]['question']['en'] !== 'Question'
        || $published[0]['answer']['ar'] !== 'إجابة') throw new \RuntimeException('FAQ publication filtering failed');
    \App\Models\Setting::$value = 'invalid json';
    if ($content->published() !== []) throw new \RuntimeException('Invalid FAQ configuration must be empty');
    echo "Passed FAQ publication and translation checks\n";
}
