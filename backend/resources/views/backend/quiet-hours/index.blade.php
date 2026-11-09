@extends('backend.layouts.app')
@section('title', __('quiet_hours.title'))
@section('content')
<div class="card"><div class="card-body">
    <h3>{{ __('quiet_hours.title') }}</h3>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="get" class="mb-4">
        <label for="quiet-branch">{{ __('quiet_hours.branch') }}</label>
        <select id="quiet-branch" name="branch_id" class="form-select" onchange="this.form.submit()">
            @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>@endforeach
        </select>
    </form>
    <form method="post" action="{{ route('backend.quiet-hours.store') }}">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $branchId }}">
        <input type="hidden" name="enabled" value="0">
        <label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings->enabled))> {{ __('quiet_hours.enabled') }}</label>
        <label for="quiet-points">{{ __('quiet_hours.points') }}</label>
        <input id="quiet-points" name="points" type="number" min="1" max="1000000" required class="form-control mb-3" value="{{ old('points', $settings->points ?: 20) }}">
        <p>{{ __('quiet_hours.help') }}</p>
        <p class="text-muted">{{ __('quiet_hours.policy') }}</p>
        @foreach(__('quiet_hours.days') as $day => $label)
        <fieldset class="border rounded p-3 mb-3">
            <legend class="float-none w-auto px-2 fs-6">{{ $label }}</legend>
            <div class="row g-2">
            @for($hour=0; $hour<24; $hour++)
                @php
                    $next = ($hour+1)%24;
                    $formatHour = fn($h) => ($h%12 ?: 12).' '.__('quiet_hours.'.($h<12?'am':'pm'));
                @endphp
                <div class="col-6 col-md-4 col-xl-3">
                    <label class="d-block border rounded p-2">
                        <input type="checkbox" name="hours[{{ $day }}][]" value="{{ $hour }}" @checked(in_array($hour, ($errors->any() ? old('hours', []) : ($settings->hours ?? []))[$day] ?? []))>
                        {{ $formatHour($hour) }} {{ __('quiet_hours.to') }} {{ $formatHour($next) }}
                    </label>
                </div>
            @endfor
            </div>
        </fieldset>
        @endforeach
        @can('store_loyalty')<button class="btn btn-primary" type="submit">{{ __('quiet_hours.save') }}</button>@endcan
    </form>
</div></div>
@endsection
