<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\DynamicQr;
use App\Services\QrTextFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DynamicQrController extends Controller
{
    public function index()
    {
        $codes = DynamicQr::latest()->paginate(12);

        return view('backend.dynamic-qr.index', ['codes' => $codes, 'assets' => ['editor']]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $code = new DynamicQr($data);

        // يُنشأ مرة واحدة ولا يتغير عند تعديل المحتوى.
        $code->token = (string) Str::uuid();
        $code->save();

        return back()->with('success', 'تم إنشاء QR بنجاح.');
    }

    public function update(Request $request, DynamicQr $dynamicQr)
    {
        $dynamicQr->update($this->validatedData($request));

        return back()->with(
            'success',
            'تم تحديث المحتوى، وصورة QR القديمة ما زالت تعمل.'
        );
    }

    /* private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:url,text'],
            'content' => ['required', 'string', 'max:10000'],
        ]);

        if ($data['type'] === 'url') {
            $data['content'] = trim($data['content']);

            $scheme = strtolower(
                (string) parse_url($data['content'], PHP_URL_SCHEME)
            );

            if (
                !filter_var($data['content'], FILTER_VALIDATE_URL)
                || !in_array($scheme, ['http', 'https'], true)
            ) {
                throw ValidationException::withMessages([
                    'content' => 'أدخل رابطًا صحيحًا يبدأ بـ https:// أو http://',
                ]);
            }
        }

        return $data;
    } */

    private function validatedData(Request $request): array
{
    $data = $request->validate([
        'title' => ['required', 'string', 'max:150'],
        'type' => ['required', 'in:url,text,wifi'],

        'content' => [
            'exclude_if:type,wifi',
            'required',
            'string',
            'max:10000',
        ],

        'ssid' => [
            'exclude_unless:type,wifi',
            'required',
            'string',
            'max:32',
        ],

        'security' => [
            'exclude_unless:type,wifi',
            'required',
            'in:WPA,WEP,nopass',
        ],

        'password' => [
            'exclude_unless:type,wifi',
            'nullable',
            'string',
            'max:255',
        ],

        'hidden' => [
            'exclude_unless:type,wifi',
            'sometimes',
            'boolean',
        ],
    ]);

    if ($data['type'] === 'wifi') {
        if (strlen($data['ssid']) > 32) {
            throw ValidationException::withMessages([
                'ssid' => 'اسم الشبكة يجب ألا يتجاوز 32 بايت.',
            ]);
        }

        $password = (string) ($data['password'] ?? '');

        if ($data['security'] !== 'nopass' && $password === '') {
            throw ValidationException::withMessages([
                'password' => 'أدخل كلمة مرور الشبكة.',
            ]);
        }

        return [
            'title' => $data['title'],
            'type' => 'wifi',
            'content' => json_encode([
                'ssid' => $data['ssid'],
                'security' => $data['security'],
                'password' => $data['security'] === 'nopass'
                    ? ''
                    : $password,
                'hidden' => $request->boolean('hidden'),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ];
    }

    if ($data['type'] === 'url') {
        $data['content'] = trim($data['content']);

        $scheme = strtolower(
            (string) parse_url($data['content'], PHP_URL_SCHEME)
        );

        if (
            !filter_var($data['content'], FILTER_VALIDATE_URL)
            || !in_array($scheme, ['http', 'https'], true)
        ) {
            throw ValidationException::withMessages([
                'content' => 'أدخل رابطًا صحيحًا يبدأ بـ https:// أو http://',
            ]);
        }
    }

    if ($data['type'] === 'text') {
        $data['content'] = app(QrTextFormatter::class)->format($data['content']);
        $plainText = html_entity_decode(strip_tags($data['content']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (trim(str_replace("\xc2\xa0", ' ', $plainText)) === '') {
            throw ValidationException::withMessages([
                'content' => 'أدخل نصًا للمحتوى.',
            ]);
        }
        if (mb_strlen($data['content']) > 10000) {
            throw ValidationException::withMessages([
                'content' => 'النص مع التنسيق يجب ألا يتجاوز 10000 حرف.',
            ]);
        }
    }

    return [
        'title' => $data['title'],
        'type' => $data['type'],
        'content' => $data['content'],
    ];
}

public function destroy(DynamicQr $dynamicQr)
{
    $dynamicQr->delete();

    return redirect()
        ->route('backend.dynamic-qr.index')
        ->with('success', 'تم حذف QR بنجاح.');
}
}