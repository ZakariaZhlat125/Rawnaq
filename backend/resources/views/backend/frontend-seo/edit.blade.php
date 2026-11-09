@extends('backend.layouts.app')
@section('title', 'إعدادات SEO')
@section('content')
<a class="btn btn-outline-primary mb-3" href="{{ route('backend.sitemap.index') }}">توليد وتحميل ملف sitemap</a>
<div dir="rtl">
    <h4>إعدادات SEO لصفحات الموقع</h4>
    <p>حدد عنوان الصفحة ووصفها والكلمات المفتاحية لكل لغة. اترك الحقل فارغًا لاستخدام القيمة الافتراضية المعروضة تحته.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="GET" action="{{ route('backend.frontend-seo.edit') }}" class="mb-4">
        <label for="seo-page">الصفحة</label>
        <div class="d-flex gap-2">
            <select id="seo-page" name="page" class="form-select">
                @foreach($pages as $url => $label)<option value="{{ $url }}" @selected($url === $path)>{{ $label }} — {{ $url }}</option>@endforeach
            </select>
            <button class="btn btn-outline-primary" type="submit">عرض الإعدادات</button>
        </div>
    </form>
    <form class="card card-body" method="POST" action="{{ route('backend.frontend-seo.update') }}">
        @csrf @method('PUT')
        <input type="hidden" name="path" value="{{ $path }}">
        <h5>{{ $pages[$path] }}</h5>
        @foreach(['ar'=>'العربية', 'en'=>'English'] as $lang => $label)
        <fieldset dir="{{ $lang === 'en' ? 'ltr' : 'rtl' }}" class="mb-4">
            <legend class="h6">{{ $label }}</legend>
            <label for="seo-title-{{ $lang }}">{{ __('admin_ui.title') }}</label>
            <input id="seo-title-{{ $lang }}" class="form-control" name="{{ $lang }}[title]" maxlength="160" value="{{ old($lang.'.title', $values[$lang]['title'] ?? '') }}" aria-describedby="seo-title-default-{{ $lang }}">
            <small id="seo-title-default-{{ $lang }}" class="d-block text-muted mb-3">{{ $defaults[$lang]['title'] }}</small>
            <label for="seo-description-{{ $lang }}">{{ __('admin_ui.meta_description') }}</label>
            <textarea id="seo-description-{{ $lang }}" class="form-control" name="{{ $lang }}[description]" maxlength="320" rows="3" aria-describedby="seo-description-default-{{ $lang }}">{{ old($lang.'.description', $values[$lang]['description'] ?? '') }}</textarea>
            <small id="seo-description-default-{{ $lang }}" class="d-block text-muted">{{ $defaults[$lang]['description'] }}</small>
            <label class="mt-3" for="seo-keywords-{{ $lang }}">{{ __('admin_ui.meta_keywords') }}</label>
            <input id="seo-keywords-{{ $lang }}" class="form-control" name="{{ $lang }}[keywords]" maxlength="1000" value="{{ old($lang.'.keywords', $values[$lang]['keywords'] ?? '') }}" aria-describedby="seo-keywords-help-{{ $lang }}">
            <small id="seo-keywords-help-{{ $lang }}" class="d-block text-muted">{{ $lang === 'en' ? 'Separate keywords with commas. Leave empty to omit this tag.' : 'افصل الكلمات بفواصل. اترك الحقل فارغًا لعدم إضافة الوسم.' }}</small>
        </fieldset>
        @endforeach
        <button class="btn btn-primary align-self-start" type="submit">حفظ إعدادات الصفحة</button>
    </form>
</div>
@endsection
