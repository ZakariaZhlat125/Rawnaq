@extends('backend.layouts.app')
@section('title', 'الصفحة التعريفية')
@section('content')
<div dir="rtl">
<h4>محتوى الصفحة التعريفية</h4>
<p>الفروع وأرقام الاتصال والخدمات والتقييمات تُقرأ من بيانات الموقع. اترك الرابط فارغًا لإخفائه.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="card card-body" method="POST" action="{{ route('backend.about-page.update') }}">
@csrf @method('PUT')
<p class="text-muted">??? ?????? ?????? ??? ?????? ?? <a href="{{ route('backend.settings') }}">????????? ? ?????? ??????</a>.</p>
@foreach($fields as $key => $field)
<label class="mb-3">{{ $field[0] }}
@if(in_array($key, ['description', 'description_en']))
<textarea class="form-control" name="{{ $key }}" rows="5" maxlength="10000">{{ old($key, $page[$key]) }}</textarea>
@else
<input class="form-control" name="{{ $key }}" type="{{ str_ends_with($key, '_url') ? 'url' : ($key === 'email' ? 'email' : 'text') }}" value="{{ old($key, $page[$key]) }}" @required($key === 'title')>
@endif
</label>
@endforeach
<h5>إحصائيات المركز</h5>
@foreach(array_pad(old('stats', $page['stats']), 4, ['value'=>'','label'=>'']) as $i => $stat)
<div class="row mb-3"><label class="col">القيمة<input class="form-control" name="stats[{{ $i }}][value]" value="{{ $stat['value'] }}"></label><label class="col">الوصف<input class="form-control" name="stats[{{ $i }}][label]" value="{{ $stat['label'] }}"></label><label class="col">الوصف بالإنجليزية<input dir="ltr" class="form-control" name="stats[{{ $i }}][label_en]" value="{{ $stat['label_en'] ?? '' }}"></label></div>
@endforeach
<h5>المزايا</h5>
@foreach(array_pad(old('features', $page['features']), 4, ['name'=>'','detail'=>'','icon'=>'star']) as $i => $feature)
<div class="row mb-3">
<label class="col">العنوان<input class="form-control" name="features[{{ $i }}][name]" value="{{ $feature['name'] }}"></label>
<label class="col">التفاصيل<input class="form-control" name="features[{{ $i }}][detail]" value="{{ $feature['detail'] }}"></label>
<label class="col">العنوان بالإنجليزية<input dir="ltr" class="form-control" name="features[{{ $i }}][name_en]" value="{{ $feature['name_en'] ?? '' }}"></label>
<label class="col">التفاصيل بالإنجليزية<input dir="ltr" class="form-control" name="features[{{ $i }}][detail_en]" value="{{ $feature['detail_en'] ?? '' }}"></label>
<label class="col">الأيقونة<select class="form-control" name="features[{{ $i }}][icon]">@foreach(['gift'=>'هدية','star'=>'نجمة','bag'=>'متجر','calendar'=>'حجز','home'=>'منزل','spa'=>'عناية','scissors'=>'حلاقة','bath'=>'حمام'] as $icon=>$label)<option value="{{ $icon }}" @selected($feature['icon'] === $icon)>{{ $label }}</option>@endforeach</select></label>
</div>
@endforeach
<button class="btn btn-primary" type="submit">حفظ المحتوى</button>
</form></div>
@endsection
