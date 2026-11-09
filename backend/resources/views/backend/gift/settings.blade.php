@extends('backend.layouts.app')

@php
    $t = fn ($k) => __('gift_system.admin.'.$k);
    $fields = [
        'gift_validity_days' => 'number', 'gift_refund_on_cancel' => 'bool', 'gift_refund_on_expiry' => 'bool',
        'gift_value_min' => 'number', 'gift_value_max' => 'number', 'gift_value_presets' => 'text',
        'gift_expiry_reminder_days' => 'number', 'gift_expiry_reminder_sms' => 'textarea',
    ];
@endphp

@section('title') {{ $t('settings') }} @endsection

@section('content')
@include('backend.gift._nav')

<form method="post" action="{{ route('gifts.settings.save') }}" class="card">
    @csrf
    <div class="card-body row g-3">
        @foreach ($fields as $key => $type)
            <div class="{{ $type === 'textarea' ? 'col-12' : 'col-md-4' }}">
                <label class="form-label">{{ __('gift_system.setting.'.$key) }}</label>
                @if ($type === 'bool')
                    <select name="{{ $key }}" class="form-select">
                        <option value="1" @selected((int) old($key, $values[$key]) === 1)>{{ $t('yes') }}</option>
                        <option value="0" @selected((int) old($key, $values[$key]) === 0)>{{ $t('no') }}</option>
                    </select>
                @elseif ($type === 'textarea')
                    <textarea name="{{ $key }}" rows="3" class="form-control" maxlength="500" required>{{ old($key, $values[$key]) }}</textarea>
                @else
                    <input type="{{ $type }}" name="{{ $key }}" value="{{ old($key, $values[$key]) }}" class="form-control" @if($type === 'number') min="0" step="1" @endif required>
                @endif
            </div>
        @endforeach
        <div class="col-12"><button class="btn btn-primary">{{ $t('save') }}</button></div>
    </div>
</form>
@endsection
