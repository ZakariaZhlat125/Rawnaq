@extends('backend.layouts.app')
 
@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection



@section('content')

<script>
    if (window.location.hash === '' || window.location.hash === '#/') {
        window.location.href = '/app/settings#/commission';
    }
</script>

<meta name="setting_local" content="none">
<input type="hidden" name="admin-profile" value="{{asset('images/JOSPA.webp')}}">
<input type="hidden" name="logo" value="{{asset('images/JOSPA.webp')}}">
<input type="hidden" name="mini-logo" value="{{asset('images/JOSPA.webp')}}">
<input type="hidden" name="dark-logo" value="{{asset('images/JOSPA.webp')}}">
<input type="hidden" name="dark-mini-logo" value="{{asset('images/JOSPA.webp')}}">
<input type="hidden" name="favicon" value="{{asset('images/JOSPA.webp')}}">

<div class="card mb-4">
    <div class="card-body">
        <h5>واتساب الموقع</h5>
        <form method="post" action="{{ route('backend.settings.store') }}">
            @csrf
            <input type="hidden" name="settings_section" value="whatsapp">
            <label for="site-whatsapp" class="form-label">رقم الواتساب مع رمز الدولة</label>
            <input id="site-whatsapp" class="form-control" name="whatsapp_number" type="text" inputmode="tel" dir="ltr" maxlength="30" value="{{ old('whatsapp_number', \App\Models\Setting::get('whatsapp_number', '')) }}">
            <p class="text-muted mt-2">أدخل الرقم مع رمز الدولة، ويمكن استخدام + أو مسافات. ترك الحقل فارغًا يخفي أزرار واتساب الموقع.</p>
            @error('whatsapp_number')<p class="text-danger">{{ $message }}</p>@enderror
            @if(session('status'))<p class="text-success">{{ session('status') }}</p>@endif
            <button class="btn btn-primary" type="submit">حفظ رقم الواتساب</button>
        </form>
    </div>
</div>
<div id="setting-app"></div>
{{-- <div class="card">
    <div class="card-body">

        <x-backend.section-header>
            <i class="{{ $module_icon }}"></i> {{ $module_title }} <small class="text-muted">{{ __($module_action) }}</small>

            <x-slot name="subtitle">
                @lang(":module_name Management Dashboard", ['module_name'=>Str::title($module_name)])
            </x-slot>
            <x-slot name="toolbar">
                <x-backend.buttons.return-back />
            </x-slot>
        </x-backend.section-header>

        <hr>

        <div class="row mt-4">
            <div class="col">
                <form method="post" action="{{ route('backend.settings.store') }}" enctype="multipart/form-data" class="form-horizontal" role="form">
                    {!! csrf_field() !!}

                    @if(count(config('setting_fields', [])) )

                        @foreach(config('setting_fields') as $section => $fields)
                        <div class="card card-accent-primary mb-4">
                            <div class="card-header">
                                <i class="{{ Arr::get($fields, 'icon', 'glyphicon glyphicon-flash') }}"></i>
                                {{ $fields['title'] }}
                            </div>
                            <div class="card-body">
                                <p class="text-muted">{{ $fields['desc'] }}</p>

                                <div class="row mb-3">
                                    <div class="col row">
                                        @foreach($fields['elements'] as $field)
                                            @includeIf('backend.settings.fields.' . $field['type'] )
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach

                    @endif

                    <div class="row m-b-md">
                        <div class="col-md-12">
                            <button class="btn-primary btn">
                                <i class='fas fa-save'></i> @lang('Save')
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <div class="row">

        </div>
    </div>
</div> --}}
@endsection

@push('after-styles')
  <style>
    .modal-backdrop {
      --bs-backdrop-zindex: 1030;
    }
  </style>
@endpush
@push('after-scripts')
@php
    $settingsVuePath = public_path('js/setting-vue.min.js');
    $settingsVueVersion = file_exists($settingsVuePath) ? filemtime($settingsVuePath) : time();
@endphp
<script src="{{ asset('js/setting-vue.min.js') }}?v={{ $settingsVueVersion }}"></script>
@endpush
