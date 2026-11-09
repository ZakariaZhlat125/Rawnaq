<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\HomePageContent;
use Illuminate\Http\Request;
class HomePageContentController extends Controller
{
    public function edit(HomePageContent $content) {
        return view('backend.home-page-content.edit', ['page'=>$content->get(), 'fields'=>$content->fields()]);
    }
    public function update(Request $request, HomePageContent $content) {
        $rules = [];
        foreach ($content->fields() as $key=>$field) {
            $rules[$key] = ['required','array:ar,en'];
            foreach (['ar','en'] as $lang) $rules[$key.'.'.$lang] = ['required','string','max:4000'];
        }
        foreach (['about_image','home_service_image','cafe_image'] as $key) $rules[$key.'_file'] = ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'];
        $data = $request->validate($rules);
        $current = $content->get();
        foreach (['about_image','home_service_image','cafe_image'] as $key) {
            unset($data[$key.'_file']);
            $data[$key] = $current[$key];
            if ($request->hasFile($key.'_file')) {
                $file = $request->file($key.'_file');
                $name = $file->hashName();
                $file->move(public_path('uploads/home-page'), $name);
                $data[$key] = asset('uploads/home-page/'.$name);
            }
        }
        abort_unless(Setting::set('home_page_content', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), 500);
        return redirect()->route('backend.home-page-content.edit')->with('success', 'تم حفظ محتوى الرئيسية.');
    }
}
