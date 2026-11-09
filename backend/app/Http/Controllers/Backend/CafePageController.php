<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CafePageController extends Controller
{
    private function page(): array
    {
        $page = json_decode(Setting::get('cafe_page', '{}'), true) ?: [];

        return array_merge([
            'title' => 'المقهى',
            'subtitle' => '',
            'sections' => [],
        ], $page);
    }

    public function edit()
    {
        return view('backend.cafe-page.edit', ['page' => $this->page()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'sections' => ['required', 'array', 'max:30'],
            'sections.*.heading' => ['required', 'string', 'max:150'],
            'sections.*.body' => ['required', 'string', 'max:10000'],
        ]);

        $data['sections'] = array_values($data['sections']);
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (strlen($content) > 60000) {
            throw ValidationException::withMessages([
                'sections' => 'المحتوى طويل جدًا. يرجى تقليل حجم النصوص ثم المحاولة مرة أخرى.',
            ]);
        }
        Setting::set('cafe_page', $content);

        return redirect()->route('backend.cafe-page.edit')
            ->with('success', 'تم حفظ محتوى صفحة المقهى بنجاح.');
    }

    public function show()
    {
        return response()->json(['status' => true, 'data' => $this->page()]);
    }
}
