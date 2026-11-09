<?php

namespace App\Helpers;


class Translation
{

    public static function getTranslatedWords($word)
    {
        $locale = app()->getLocale();
        $file = base_path("lang/{$locale}.json");

        // If file doesn't exist, create an empty JSON file
        if (!\File::exists($file)) {
            \File::put($file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $translations = json_decode(\File::get($file), true);
        if (!isset($translations[$word])) {
            $translations[$word] = $word;
            \File::put($file, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        return $translations[$word];
    }
}
