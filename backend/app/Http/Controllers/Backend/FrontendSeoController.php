<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\FrontendSeo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FrontendSeoController extends Controller
{
    public function edit(Request $request, FrontendSeo $seo)
    {
        $pages = $seo->pages();
        $path = $seo->normalize((string) $request->query('page', '/'));
        abort_unless(isset($pages[$path]), 404);
        return view('backend.frontend-seo.edit', [
            'pages'=>$pages, 'path'=>$path, 'values'=>$seo->saved()[$path] ?? [],
            'defaults'=>['ar'=>$seo->defaults($path, 'ar'), 'en'=>$seo->defaults($path, 'en')],
        ]);
    }

    public function update(Request $request, FrontendSeo $seo)
    {
        $data = $request->validate([
            'path'=>['required', 'string', Rule::in(array_keys($seo->pages()))],
            'ar'=>['required', 'array:title,description,keywords'], 'en'=>['required', 'array:title,description,keywords'],
            'ar.keywords'=>['nullable', 'string', 'max:1000'], 'en.keywords'=>['nullable', 'string', 'max:1000'],
            'ar.title'=>['nullable', 'string', 'max:160'], 'en.title'=>['nullable', 'string', 'max:160'],
            'ar.description'=>['nullable', 'string', 'max:320'], 'en.description'=>['nullable', 'string', 'max:320'],
        ]);
        $all = $seo->saved();
        foreach (['ar', 'en'] as $lang) {
            foreach (['title', 'description', 'keywords'] as $key) {
                $all[$data['path']][$lang][$key] = $seo->text($data[$lang][$key] ?? '', $lang);
            }
        }
        abort_unless(Setting::set('frontend_seo', json_encode($all, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), 500);
        return redirect()->route('backend.frontend-seo.edit', ['page'=>$data['path']])
            ->with('success', 'تم حفظ إعدادات SEO للصفحة بنجاح.');
    }

    public function show(Request $request, FrontendSeo $seo)
    {
        $data = $request->validate(['path'=>['required','string','max:512'], 'lang'=>['required',Rule::in(['ar','en'])]]);
        if ($seo->normalize($data['path']) === '/home-service') abort_unless(app(\App\Services\HomeService::class)->settings()['enabled'], 404);
        $result = $seo->resolve($data['path'], $data['lang']);
        abort_unless($result, 404);
        return response()->json(['status'=>true,'data'=>$result])->header('Cache-Control', 'no-store');
    }
}
