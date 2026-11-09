<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CategoryPageContent;
use Illuminate\Http\Request;
use Modules\Category\Models\Category;

class CategoryPageController extends Controller
{
    public function edit(Category $category, CategoryPageContent $content)
    {
        abort_unless($category->parent_id === null, 404);
        return view('backend.category-page.edit', ['category'=>$category, 'content'=>$content->get($category)]);
    }
    public function update(Request $request, Category $category, CategoryPageContent $content)
    {
        abort_unless($category->parent_id === null, 404);
        $rules = [];
        foreach (['why_title','why_intro','benefits_title','benefits_intro','faq_title','banner_title','banner_text'] as $field) {
            $rules[$field] = ['required','array:ar,en'];
            foreach (['ar','en'] as $lang) $rules[$field.'.'.$lang] = ['nullable','string','max:2000'];
        }
        foreach (['why','benefits','faq'] as $section) {
            $rules[$section] = ['nullable','array','max:20'];
            $rules[$section.'.*'] = ['array:'.($section==='faq'?'q,a':'icon,title,text')];
            foreach ($section === 'faq' ? ['q','a'] : ['title','text'] as $key) {
                $rules[$section.'.*.'.$key] = ['required','array:ar,en'];
                foreach (['ar','en'] as $lang) $rules[$section.'.*.'.$key.'.'.$lang] = ['nullable','string','max:2000'];
            }
            if ($section !== 'faq') $rules[$section.'.*.icon'] = ['required','in:shield,leaf,user,heart,clock,sparkle'];
        }
        foreach (['why','benefits','faq','banner'] as $section) $rules['show_'.$section] = ['required','boolean'];
        foreach (['benefits_image'] as $field) $rules[$field] = ['nullable','string','max:2048','regex:~^(?:https?://|/(?!/))[^\s<>"\x27]+$~'];
        $rules['banner_image_file'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
        $data = $request->validate($rules);
        unset($data['banner_image_file']);
        $data['banner_image'] = $content->get($category)['banner_image'] ?? '';
        if ($request->hasFile('banner_image_file')) {
            $image = $request->file('banner_image_file');
            $filename = $image->hashName();
            $image->move(public_path('uploads/category-pages'), $filename);
            $data['banner_image'] = asset('uploads/category-pages/'.$filename);
        }
        foreach (['why','benefits','faq'] as $section) {
            $fields = $section==='faq'?['q','a']:['title','text'];
            $data[$section] = array_values(array_filter($data[$section] ?? [], function ($row) use ($fields) {
                foreach (['ar','en'] as $lang) if (trim($row[$fields[0]][$lang] ?? '') !== '' && trim($row[$fields[1]][$lang] ?? '') !== '') return true;
                return false;
            }));
        }
        foreach (['why','benefits','faq','banner'] as $section) $data['show_'.$section] = (bool) $data['show_'.$section];
        abort_unless(Setting::set('category_page_'.$category->id, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), 500);
        return redirect()->route('backend.categories.page-content.edit', $category->id)->with('success','تم حفظ محتوى صفحة الخدمة.');
    }
}
