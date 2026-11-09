@extends('backend.layouts.app')
@section('title','توليد خريطة الموقع')
@section('content')
<div class="card card-body" dir="rtl">
    <h4>توليد خريطة الموقع sitemap</h4>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <p>توليد ملف محدث يشمل صفحات الموقع العامة والخدمات النشطة والمقالات المنشورة، ويستبعد الصفحات الخاصة والخدمات المنزلية الموقوفة.</p>
    <p>آخر توليد: {{ $generatedAt ? \Carbon\Carbon::createFromTimestamp($generatedAt)->timezone(config('app.timezone'))->format('Y-m-d H:i:s') : 'لم يتم توليد نسخة محفوظة بعد' }}</p>
    <div class="d-flex flex-wrap gap-2">
        <form method="POST" action="{{ route('backend.sitemap.generate') }}">@csrf<button class="btn btn-primary">توليد وتحديث الملف</button></form>
        @if($generatedAt)<a class="btn btn-success" href="{{ route('backend.sitemap.download') }}">تحميل sitemap.xml</a>@endif
        <a class="btn btn-outline-primary" href="{{ route('backend.frontend-seo.edit') }}">إعدادات SEO</a>
    </div>
    <p class="mt-3 text-muted">إذا كانت استضافة الفرونت تستخدم ملفاً ثابتاً، ارفع النسخة المحمّلة باسم sitemap.xml إلى جذر الموقع لتحديث الخريطة المنشورة. توليد النسخة هنا لا يرفعها تلقائياً لاستضافة أخرى.</p>
</div>
@endsection
