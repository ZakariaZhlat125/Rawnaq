<?php
namespace App\Services;
use App\Models\Setting;
use Modules\Category\Models\Category;

class CategoryPageContent
{
    public function defaults(Category $category): array
    {
        static $all;
        $all ??= json_decode(file_get_contents(resource_path('data/service-page-defaults.json')), true, 512, JSON_THROW_ON_ERROR);
        $name = mb_strtolower($category->slug.' '.implode(' ', $category->getTranslations('name')));
        $groups = ['bath'=>['bath','hammam','moroccan','حمام'], 'pedi'=>['pedicure','foot','بديكير'], 'skin'=>['skin','facial','بشرة'], 'mass'=>['massage','مساج'], 'hair'=>['hair','shav','حلاق']];
        foreach ($groups as $key=>$words) foreach ($words as $word) if (str_contains($name, $word)) return $all[$key];
        $fallback = $all['hair'];
        $fallback['benefits'] = []; $fallback['faq'] = [];
        $fallback['benefits_image'] = ''; $fallback['banner_image'] = '';
        return $fallback;
    }
    public function get(Category $category): array
    {
        $saved = json_decode(Setting::get('category_page_'.$category->id, '{}'), true);
        return array_replace($this->defaults($category), is_array($saved) ? $saved : []);
    }
}
