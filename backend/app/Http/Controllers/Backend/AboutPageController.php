<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AboutPageController extends Controller
{
    public function fields(): array
    {
        return [
            'title' => ['اسم المركز', 'رونق'],
            'english_title' => ['الاسم بالإنجليزية', 'RAWNAQ — FOR MEN'],
            'tagline_en' => ['شعار الصفحة بالإنجليزية', ''],
            'location_text_en' => ['وصف الموقع بالإنجليزية', ''],
            'description_en' => ['نبذة عن المركز بالإنجليزية', ''],
            'hours_en' => ['ساعات العمل بالإنجليزية', ''],
            'tagline' => ['شعار الصفحة', 'الجمال. الثقة. العناية الفاخرة'],
            'location_text' => ['وصف الموقع', 'جدة، المملكة العربية السعودية'],
            'description' => ['نبذة عن المركز', 'مركز رونق للرجال — وجهتك المتكاملة للعناية الرجالية في جدة. نقدم الحلاقة والمساج والحمام المغربي وتنظيف البشرة والبديكير، مع خدمة منزلية تصلك أينما كنت.'],
            'hours' => ['ساعات العمل', 'يوميًا: 10:00 صباحًا — 12:00 منتصف الليل'],
            'email' => ['البريد الإلكتروني', Setting::get('inquriy_email', 'info@sami-care.sa')],
            'instagram_url' => ['رابط إنستغرام', 'https://instagram.com/samicare.sa'],
            'tiktok_url' => ['رابط تيك توك', 'https://tiktok.com/@samicare.sa'],
            'snapchat_url' => ['رابط سناب شات', 'https://snapchat.com/add/samicare.sa'],
            'facebook_url' => ['رابط فيسبوك', 'https://facebook.com/samicare.sa'],
            'youtube_url' => ['رابط يوتيوب', Setting::get('youtube_url', '')],
            'android_url' => ['رابط تطبيق أندرويد', Setting::get('customer_app_play_store', '')],
            'ios_url' => ['رابط تطبيق آيفون', Setting::get('customer_app_app_store', '')],
            'app_url' => ['رابط التطبيق الموحد', ''],
            'google_review_url' => ['رابط تقييمنا على جوجل', ''],
        ];
    }

    private function page(): array
    {
        $defaults = array_map(fn ($field) => $field[1], $this->fields());
        $defaults['stats'] = [['value'=>'+10','label'=>'سنوات خبرة'],['value'=>'+45','label'=>'خدمة متخصصة'],['value'=>'+50K','label'=>'عميل سعيد'],['value'=>'4.9 ★','label'=>'تقييم عملائنا']];
        $defaults['features'] = [['name'=>'بطاقات هدايا','detail'=>'أهدِ من تحب تجربة فاخرة','icon'=>'gift'],['name'=>'برنامج الولاء','detail'=>'نقاط ومكافآت مع كل حجز','icon'=>'star'],['name'=>'متجر المنتجات','detail'=>'منتجات عناية أصلية','icon'=>'bag'],['name'=>'حجز إلكتروني','detail'=>'اختر موعدك بسهولة','icon'=>'calendar']];
        return array_replace($defaults, json_decode(Setting::get('about_page', '{}'), true) ?: [], ['whatsapp' => (string) Setting::get('whatsapp_number', '')]);
    }

    public function edit()
    {
        return view('backend.about-page.edit', ['page'=>$this->page(), 'fields'=>$this->fields()]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach ($this->fields() as $key => $field) {
            $rules[$key] = ['nullable','string','max:'.(in_array($key, ['description', 'description_en']) ? '10000' : '1000')];
            if (str_ends_with($key, '_url')) $rules[$key][] = 'url:http,https';
        }
        $rules['stats.*.label_en'] = ['nullable','string','max:100'];
        $rules['features.*.name_en'] = ['nullable','string','max:100'];
        $rules['features.*.detail_en'] = ['nullable','string','max:500'];
        $rules['title'] = ['required','string','max:150'];
        $rules['email'] = ['nullable','email','max:254'];
        $rules += ['stats'=>['nullable','array','max:8'], 'stats.*.value'=>['nullable','string','max:50'], 'stats.*.label'=>['nullable','string','max:100'], 'features'=>['nullable','array','max:8'], 'features.*.name'=>['nullable','string','max:100'], 'features.*.detail'=>['nullable','string','max:500'], 'features.*.icon'=>['required','in:gift,star,bag,calendar,home,spa,scissors,bath']];
        $data = $request->validate($rules);
        foreach ($this->fields() as $key => $field) $data[$key] = $data[$key] ?? '';
        $data['stats'] = array_values(array_filter($data['stats'] ?? [], fn ($r) => !empty($r['label']) && !empty($r['value'])));
        $data['features'] = array_values(array_filter($data['features'] ?? [], fn ($r) => !empty($r['name'])));
        Setting::set('about_page', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return redirect()->route('backend.about-page.edit')->with('success', 'تم حفظ الصفحة التعريفية بنجاح.');
    }

    public function show()
    {
        return response()->json(['status'=>true,'data'=>$this->page()]);
    }
}
