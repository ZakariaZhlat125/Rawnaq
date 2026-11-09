<div class="card card-body mb-2 content-row">
@if($section !== 'faq')
<label>الأيقونة <select class="form-select" name="{{ $section }}[{{ $index }}][icon]">@foreach(['shield'=>'نظافة','leaf'=>'طبيعي','user'=>'مختص','heart'=>'راحة','clock'=>'وقت','sparkle'=>'عناية'] as $value=>$label)<option value="{{ $value }}" @selected(($row['icon'] ?? 'shield') === $value)>{{ $label }}</option>@endforeach</select></label>
@endif
<div class="row">
@foreach(['ar'=>'العربية','en'=>'English'] as $lang=>$label)
<div class="col-md-6" dir="{{ $lang === 'en' ? 'ltr' : 'rtl' }}"><b>{{ $label }}</b>
@foreach($section === 'faq' ? ['q'=>'السؤال','a'=>'الإجابة'] : ['title'=>'العنوان','text'=>'الوصف'] as $field=>$label)
<label class="d-block mt-2">{{ $label }}<textarea class="form-control" rows="2" maxlength="2000" name="{{ $section }}[{{ $index }}][{{ $field }}][{{ $lang }}]">{{ $row[$field][$lang] ?? '' }}</textarea></label>
@endforeach
</div>
@endforeach
</div><button type="button" class="btn btn-outline-danger align-self-end mt-2" data-remove-row>حذف العنصر</button>
</div>