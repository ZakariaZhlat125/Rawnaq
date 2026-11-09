@extends('backend.layouts.app')
@section('title','محتوى الرئيسية')
@section('content')
<div dir="rtl"><h4>محتوى الصفحة الرئيسية</h4>
<p>نصوص الديسكتوب وشرائح الموبايل بالعربية والإنجليزية. قسم من نحن والصور مشتركة بين النسختين.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" enctype="multipart/form-data" action="{{ route('backend.home-page-content.update') }}">@csrf @method('PUT')
@foreach(['cafe_image'=>'صورة قسم المقهى','about_image'=>'صورة من نحن','home_service_image'=>'صورة الخدمات المنزلية'] as $key=>$label)
<div class="card card-body mb-3"><label>{{ $label }}<input class="form-control" type="file" name="{{ $key }}_file" accept="image/jpeg,image/png,image/webp"></label><small>حتى 2 ميجابايت. عدم اختيار ملف يحافظ على الصورة الحالية.</small>
@if(filter_var($page[$key], FILTER_VALIDATE_URL))<img src="{{ $page[$key] }}" alt="{{ $label }}" style="max-width:240px;max-height:160px;object-fit:contain" loading="lazy">@endif</div>
@endforeach
@foreach($fields as $key=>$field)
<div class="card card-body mb-3"><h6>{{ $field['label'] }}</h6><div class="row">
@foreach(['ar'=>'العربية','en'=>'English'] as $lang=>$label)
<label class="col-md-6" dir="{{ $lang==='ar'?'rtl':'ltr' }}">{{ $label }}<textarea class="form-control" name="{{ $key }}[{{ $lang }}]" rows="2" maxlength="4000" required>{{ old($key.'.'.$lang,$page[$key][$lang]) }}</textarea></label>
@endforeach</div></div>
@endforeach
<button class="btn btn-primary" type="submit">حفظ محتوى الرئيسية</button></form></div>
@endsection
