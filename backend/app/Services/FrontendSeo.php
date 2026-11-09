<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Setting;
use Modules\Category\Models\Category;
use Modules\Package\Models\Package;

class FrontendSeo
{
    public function saved(): array
    {
        $data = json_decode(Setting::get('frontend_seo', '{}'), true);
        return is_array($data) ? $data : [];
    }

    public function pages(): array
    {
        $pages = array_map(fn ($page) => $page['label'], config('frontend_seo'));
        foreach (Category::query()->where('status', 1)->orderBy('id')->get() as $category) {
            $pages['/services/'.$category->id] = 'خدمة: '.$this->text($category->getTranslations('name'), 'ar');
        }
        foreach (Blog::query()->published()->orderBy('id')->get() as $blog) {
            $pages['/blog/'.$blog->slug] = 'مقال: '.$this->text($blog->title, 'ar');
        }
        foreach (Package::query()->where('status', 1)->activeBasePackages()->orderBy('id')->get() as $package) {
            $pages['/packages/'.$package->id] = 'باقة: '.$this->text($package->getTranslations('name'), 'ar');
        }
        foreach (Package::query()->where('status', 1)->offerPackages()->orderBy('id')->get() as $offer) {
            $pages['/offers/'.$offer->id] = 'عرض: '.$this->text($offer->getTranslations('name'), 'ar');
        }
        return $pages;
    }

    public function normalize(string $path): string
    {
        $path = '/'.trim(explode('?', $path, 2)[0], '/');
        $aliases = ['/index.html'=>'/', '/about.html'=>'/page-about', '/TermsAndConditions'=>'/terms',
            '/privacy'=>'/privacy-policy', '/packages'=>'/packages-gifts'];
        if (isset($aliases[$path])) return $aliases[$path];
        if (str_ends_with($path, '.html')) $path = substr($path, 0, -5);
        return $path;
    }

    public function text($value, string $lang): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) $value = $decoded;
        }
        if (is_array($value)) $value = $value[$lang] ?? '';
        if (!is_scalar($value)) return '';
        return trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    }

    public function defaults(string $path, string $lang): ?array
    {
        $pages = config('frontend_seo');
        if (isset($pages[$path])) return $pages[$path][$lang];
        $record = null;
        if (preg_match('~^/services/([0-9]+)$~', $path, $match)) {
            $record = Category::query()->where('status', 1)->find($match[1]);
            if (!$record) return null;
            $base = $pages['/services'][$lang];
            $name = $this->text($record->getTranslations('name'), $lang);
            $description = $this->text($record->summary, $lang) ?: $this->text($record->description, $lang);
        } elseif (preg_match('~^/blog/([^/]+)$~u', $path, $match)) {
            $record = Blog::query()->published()->where('slug', $match[1])->first();
            if (!$record) return null;
            $base = $pages['/blog'][$lang];
            $name = $this->text($record->title, $lang);
            $description = $this->text($record->excerpt, $lang);
        } elseif (preg_match('~^/(packages|offers)/([0-9]+)$~', $path, $match)) {
            $query = Package::query()->where('status', 1);
            $match[1] === 'offers' ? $query->offerPackages() : $query->activeBasePackages();
            $record = $query->find($match[2]);
            if (!$record) return null;
            $base = $pages['/packages-gifts'][$lang];
            $name = $this->text($record->getTranslations('name'), $lang);
            $description = $this->text($record->getTranslations('description'), $lang);
        } else {
            return null;
        }
        return [
            'title' => $name ? $name.($lang === 'en' ? ' | Rawnaq' : ' | رونق') : $base['title'],
            'keywords' => '',
            'description' => $description ? mb_substr($description, 0, 320) : $base['description'],
        ];
    }

    public function resolve(string $path, string $lang): ?array
    {
        $path = $this->normalize($path);
        $defaults = $this->defaults($path, $lang);
        if (!$defaults) return null;
        $saved = $this->saved()[$path][$lang] ?? [];
        foreach (['title', 'description', 'keywords'] as $key) {
            $defaults[$key] = $this->text($saved[$key] ?? '', $lang) ?: $defaults[$key];
        }
        return ['path'=>$path, 'lang'=>$lang] + $defaults;
    }
}
