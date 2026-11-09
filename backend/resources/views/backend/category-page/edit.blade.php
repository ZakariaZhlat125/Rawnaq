@extends('backend.layouts.app')
@section('title','محتوى صفحة الخدمة')
@section('content')
<div dir="rtl"><h4>محتوى صفحة الخدمة: {{ $category->name }}</h4>
<p>المحتوى الافتراضي مستعاد من التصميم القديم. عدّل النصوص لكل لغة، وأضف أو احذف العناصر. ترك عنوان القسم فارغًا يستخدم اسم الخدمة تلقائيًا.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" enctype="multipart/form-data" action="{{ route('backend.categories.page-content.update', $category->id) }}">@csrf @method('PUT')
@foreach(['why'=>'لماذا تختار الخدمة','benefits'=>'فوائد الخدمة','faq'=>'الأسئلة الشائعة','banner'=>'بانر الحجز'] as $section=>$label)
<section class="card card-body mb-4"><h5>{{ $label }}</h5>
<input type="hidden" name="show_{{ $section }}" value="0"><label><input type="checkbox" name="show_{{ $section }}" value="1" @checked(old('show_'.$section,$content['show_'.$section]))> إظهار القسم</label>
@foreach(['ar'=>'العربية','en'=>'English'] as $lang=>$language)
<div dir="{{ $lang==='en'?'ltr':'rtl' }}" class="my-2"><b>{{ $language }}</b>
@foreach($section==='faq'?['title']:($section==='banner'?['title','text']:['title','intro']) as $field)
@php($key = $section.'_'.$field)
<label class="d-block">{{ $field==='title'?'عنوان القسم':'الوصف' }}<textarea class="form-control" rows="2" maxlength="2000" name="{{ $key }}[{{ $lang }}]">{{ old($key.'.'.$lang,$content[$key][$lang] ?? '') }}</textarea></label>
@endforeach</div>
@endforeach
@if($section==='banner')
<label class="d-block">صورة البانر السفلي (اختياري)<input type="file" class="form-control" name="banner_image_file" accept="image/jpeg,image/png,image/webp"></label>
<small>اختر صورة JPG أو PNG أو WebP بحد أقصى 2 ميجابايت. إذا لم تختر ملفًا، تظل الصورة الحالية كما هي.</small>
@elseif($section==='benefits')
<label>رابط الصورة (اختياري)<input dir="ltr" class="form-control" name="{{ $section }}_image" maxlength="2048" value="{{ old($section.'_image',$content[$section.'_image']) }}"></label><small>رابط https أو مسار يبدأ بـ /. عند تركه فارغًا تُستخدم صورة الخدمة.</small>
@endif
@if($section!=='banner')
@php($rows = old($section, $content[$section]))
<div data-rows="{{ $section }}" data-next="{{ count($rows) }}">@foreach($rows as $index=>$row)@include('backend.category-page.row')@endforeach</div>
<template id="row-{{ $section }}">@include('backend.category-page.row',['index'=>'__INDEX__','row'=>[]])</template>
<button type="button" class="btn btn-outline-primary mt-2" data-add-row="{{ $section }}">إضافة عنصر</button>
@endif
</section>
@endforeach
<button type="submit" class="btn btn-primary">حفظ محتوى الخدمة</button>
<a class="btn btn-outline-secondary" href="{{ route('backend.categories.index') }}">العودة للخدمات الرئيسية</a>
</form></div>
<script>
document.addEventListener('click', function(event) {
 var add=event.target.closest('[data-add-row]'), remove=event.target.closest('[data-remove-row]');
 if(remove)remove.closest('.content-row').remove();
 if(add){var section=add.dataset.addRow, list=document.querySelector('[data-rows="'+section+'"]');if(list.children.length>=20)return;var index=Number(list.dataset.next);list.dataset.next=index+1;list.insertAdjacentHTML('beforeend',document.getElementById('row-'+section).innerHTML.replaceAll('__INDEX__',String(index)));}
});
</script>
@endsection