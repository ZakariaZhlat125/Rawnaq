<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Modules\Category\Models\Category;

class PageQrController extends Controller
{
    public function index()
    {
        $baseUrl = rtrim((string) config('app.frontend_url'), '/');

        abort_unless(
            filter_var($baseUrl, FILTER_VALIDATE_URL)
            && in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https']),
            500,
            'اضبط FRONTEND_URL في ملف .env على رابط الفرونت الصحيح.'
        );

        $routes = [
            '/' => 'الرئيسية',
            '/booking' => 'حجز موعد',
            '/services' => 'الخدمات',
            '/store' => 'المتجر',
            '/gifts' => 'الهدايا',
            '/packages-gifts' => 'باقات الهدايا',
            '/branches' => 'الفروع',
            '/contact' => 'تواصل معنا',
            //'/gift-recipient' => 'مستلم الهدية',
            '/terms' => 'الشروط والأحكام',
            '/privacy-policy' => 'سياسة الخصوصية',
            '/page-about' => 'من نحن',
            '/blog' => 'المدونة',
        ];

        $pages = [];

        foreach ($routes as $path => $title) {
            $pages[] = [
                'title' => $title,
                'url' => $baseUrl . $path,
            ];
        }

        foreach (Category::where('status', 1)
            ->whereNull('parent_id')
            ->with(['media', 'services' => function ($query) {
                $query->where('status', 1)->with('media');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get() as $service) {
            $pages[] = [
                'title' => 'خدمة: ' . $service->name,
                'url' => $baseUrl . '/services/' . $service->id,
            ];
        }

        foreach (Blog::published()->get() as $blog) {
            $pages[] = [
                'title' => 'مقال: ' . $blog->title,
                'url' => $baseUrl . '/blog/' . rawurlencode($blog->slug),
            ];
        }

        return view('backend.page-qr.index', compact('pages'));
    }
}