@extends('backend.layouts.app')

@section('title', 'صفحة الخدمة المنزلية')

@section('content')
<div dir="rtl">
    <h4 class="mb-4">صفحة الخدمة المنزلية</h4>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="POST" action="{{ route('backend.home-service-page.update') }}" class="card card-body">
        @csrf
        @method('PUT')
        <h5>إعدادات الخدمة المنزلية</h5>
        <input type="hidden" name="enabled" value="0">
        <label class="mb-3"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $homeSettings['enabled']))> تفعيل الخدمة المنزلية</label>
        <p>حدد مركز التغطية على خرائط جوجل ونصف قطر نطاق الخدمة بالكيلومتر. رسوم الزيارة تحدد من إدارة المدن.</p>
        <div class="row">
          <label class="col">خط العرض<input id="coverage-lat" name="latitude" type="number" step="any" min="-90" max="90" required class="form-control" value="{{ old('latitude', $homeSettings['latitude']) }}"></label>
          <label class="col">خط الطول<input id="coverage-lng" name="longitude" type="number" step="any" min="-180" max="180" required class="form-control" value="{{ old('longitude', $homeSettings['longitude']) }}"></label>
          <label class="col">نصف القطر (كم)<input id="coverage-radius" name="radius_km" type="number" step="0.1" min="0.1" max="20000" required class="form-control" value="{{ old('radius_km', $homeSettings['radius_km']) }}"></label>
        </div>
        <div id="home-coverage-map" style="height:360px" class="my-3"></div>
        <p id="map-error" hidden>تعذر تحميل خرائط جوجل. راجع مفتاح Google Maps في الإعدادات. يمكنك إدخال إحداثيات المركز أعلاه.</p>
        <hr>
        <label for="page-title" class="form-label">عنوان الصفحة</label>
        <input id="page-title" name="title" class="form-control mb-3" maxlength="150" required value="{{ old('title', $page['title']) }}">
        <label for="page-subtitle" class="form-label">وصف مختصر</label>
        <textarea id="page-subtitle" name="subtitle" class="form-control mb-3" rows="2" maxlength="500">{{ old('subtitle', $page['subtitle']) }}</textarea>
        <p class="text-muted">أضف فقرات المحتوى بالترتيب الذي تريد ظهوره في الصفحة. تُعرض النصوص كما تكتبها مع الحفاظ على فواصل الأسطر.</p>
        <div id="home-service-sections">
            @foreach (old('sections', $page['sections'] ?: [['heading' => '', 'body' => '']]) as $section)
                <fieldset class="page-section border rounded p-3 mb-3">
                    <legend class="fs-6">فقرة المحتوى</legend>
                    <label class="d-block mb-3">عنوان الفقرة
                        <input data-field="heading" name="sections[{{ $loop->index }}][heading]" class="form-control" maxlength="150" required value="{{ $section['heading'] ?? '' }}">
                    </label>
                    <label class="d-block mb-3">النص
                        <textarea data-field="body" name="sections[{{ $loop->index }}][body]" class="form-control" rows="6" maxlength="10000" required>{{ $section['body'] ?? '' }}</textarea>
                    </label>
                    <button type="button" class="btn btn-outline-secondary" data-action="up">نقل لأعلى</button>
                    <button type="button" class="btn btn-outline-secondary" data-action="down">نقل لأسفل</button>
                    <button type="button" class="btn btn-outline-danger" data-action="remove">حذف الفقرة</button>
                </fieldset>
            @endforeach
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" id="add-home-service-section" class="btn btn-outline-primary">إضافة فقرة</button>
            <button type="submit" class="btn btn-success">حفظ المحتوى</button>
        </div>
    </form>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('js/home-service-map.js') }}"></script>
<script>
(async function () {
 const lat = document.getElementById('coverage-lat'), lng = document.getElementById('coverage-lng'), radius = document.getElementById('coverage-radius');
 const error = document.getElementById('map-error');
 const values = () => ({latitude:lat.value, longitude:lng.value, radiusKm:radius.value});
 function showError(message) { error.textContent = message; error.hidden = false; }
 window.addEventListener('sami-map-error', event => showError(event.detail));
 try {
   const widget = await window.SamiHomeMap.create(document.getElementById('home-coverage-map'), {
     key: @json(\App\Models\Setting::get('google_maps_key', '')),
     ...values(), editable:true,
     onChange: value => {lat.value=value.latitude.toFixed(7);lng.value=value.longitude.toFixed(7);radius.value=Math.max(0.1, Math.min(20000,value.radiusKm)).toFixed(1);}
   });
   [lat,lng,radius].forEach(input => input.addEventListener('change', () => {
     if ([lat,lng,radius].every(field => field.value !== '' && field.checkValidity())) widget.update(values());
   }));
 } catch (failure) { showError(failure.message); }
})();
</script>
<script>
(function () {
    const container = document.getElementById('home-service-sections');
    const addButton = document.getElementById('add-home-service-section');
    const template = container.firstElementChild.cloneNode(true);

    function sync() {
        const sections = Array.from(container.children);
        sections.forEach(function (section, index) {
            section.querySelectorAll('[data-field]').forEach(function (field) {
                field.name = `sections[${index}][${field.dataset.field}]`;
            });
            section.querySelector('[data-action="up"]').disabled = index === 0;
            section.querySelector('[data-action="down"]').disabled = index === sections.length - 1;
            section.querySelector('[data-action="remove"]').disabled = sections.length === 1;
        });
        addButton.disabled = sections.length >= 30;
    }

    addButton.addEventListener('click', function () {
        if (container.children.length >= 30) return;
        const section = template.cloneNode(true);
        section.querySelectorAll('[data-field]').forEach(field => { field.value = ''; });
        container.appendChild(section);
        sync();
        section.querySelector('input').focus();
    });
    container.addEventListener('click', function (event) {
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const section = button.closest('.page-section');
        if (button.dataset.action === 'remove' && container.children.length > 1) section.remove();
        if (button.dataset.action === 'up' && section.previousElementSibling) container.insertBefore(section, section.previousElementSibling);
        if (button.dataset.action === 'down' && section.nextElementSibling) container.insertBefore(section.nextElementSibling, section);
        sync();
    });
    sync();
})();
</script>
@endpush
