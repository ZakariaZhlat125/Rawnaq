<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\FaqContent;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function edit(FaqContent $content)
    {
        return view('backend.faq.edit', ['rows' => $content->all(), 'module_title' => __('faq.title')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'items' => 'sometimes|array|max:100',
            'items.*' => 'array:question,answer,active',
            'items.*.question' => 'required|array:ar,en',
            'items.*.answer' => 'required|array:ar,en',
            'items.*.question.ar' => 'required|string|max:500',
            'items.*.answer.ar' => 'required|string|max:5000',
            'items.*.question.en' => 'nullable|string|max:500',
            'items.*.answer.en' => 'nullable|string|max:5000',
            'items.*.active' => 'required|boolean',
        ]);
        $rows = array_values($data['items'] ?? []);
        foreach ($rows as &$row) $row['active'] = (bool) $row['active'];
        unset($row);
        abort_unless(Setting::set('faq_content', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), 500);
        return redirect()->route('backend.faq.edit')->with('success', __('faq.saved'));
    }
}
