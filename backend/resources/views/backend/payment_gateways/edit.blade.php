@extends('backend.layouts.app')

@php $t = fn ($k, $r = []) => __('payment_gateways.'.$k, $r); @endphp

@section('title') {{ $t('title') }}: {{ $gateway->name() }} @endsection

@push('after-styles')
<style>
    .gateway-credential { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-size: .875rem; resize: vertical; min-height: 4.5rem; word-break: break-all; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('backend.payment-gateways.index') }}" class="btn btn-sm btn-outline-primary">&larr; {{ $t('back') }}</a>
    <div>
        @if ($gateway->is_active)
            <span class="badge bg-success">{{ $t('active') }}</span>
        @else
            <span class="badge bg-secondary">{{ $t('inactive') }}</span>
        @endif
    </div>
</div>

@include('backend.payment_gateways._alerts')

<form method="post" action="{{ route('backend.payment-gateways.update', $gateway) }}" autocomplete="off">
    @csrf @method('PUT')

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">{{ $t('details') }}</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ $t('code') }}</label>
                    <input type="text" class="form-control" value="{{ $gateway->code }}" disabled dir="ltr">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ $t('driver') }}</label>
                    <input type="text" class="form-control" value="{{ $driverLabel }}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sort_order">{{ $t('sort_order') }}</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $gateway->sort_order) }}" class="form-control" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="name_ar">{{ $t('name_ar') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" id="name_ar" value="{{ old('name_ar', $gateway->name_ar) }}" class="form-control" maxlength="150" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="name_en">{{ $t('name_en') }}</label>
                    <input type="text" name="name_en" id="name_en" value="{{ old('name_en', $gateway->name_en) }}" class="form-control" maxlength="150" dir="ltr">
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">{{ $t('description') }}</label>
                    <textarea name="description" id="description" class="form-control" rows="2" maxlength="2000">{{ old('description', $gateway->description) }}</textarea>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $gateway->is_active))>
                        <label class="form-check-label" for="is_active">{{ $t('enable') }}</label>
                    </div>
                    <div class="form-text">{{ $t('enable_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-1">{{ $t('credentials') }}</h5>
            <p class="small text-muted">{{ $t('credentials_help') }}</p>

            @forelse ($gateway->fields as $field)
                @php
                    $name = 'fields['.$field->id.']';
                    $id = 'field-'.$field->id;
                    $error = $errors->first('fields.'.$field->id);
                    $isBuiltin = in_array($field->key, $driverKeys, true);
                @endphp
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <label class="form-label mb-0 fw-semibold" for="{{ $id }}">
                            {{ $field->label }}
                            @if ($field->is_required)<span class="text-danger">*</span>@endif
                            <code class="ms-1 small">{{ $field->key }}</code>
                        </label>
                        <div class="d-flex flex-wrap align-items-center gap-1">
                            @if ($field->is_required)<span class="badge bg-light text-dark border">{{ $t('required') }}</span>@endif
                            @if ($field->is_secret)<span class="badge bg-light text-dark border"><i class="fa-solid fa-lock"></i> {{ $t('secret') }}</span>@endif
                            @if ($isBuiltin)
                                <span class="badge bg-light text-muted border">{{ $t('builtin', ['driver' => $driverLabel]) }}</span>
                            @else
                                <span class="badge bg-light text-muted border">{{ $t('custom') }}</span>
                                <button type="submit" form="delete-field-{{ $field->id }}" class="btn btn-sm btn-outline-danger py-0">{{ $t('delete') }}</button>
                            @endif
                        </div>
                    </div>

                    @if ($field->type === \App\Models\PaymentGatewayField::TYPE_SELECT)
                        <select name="{{ $name }}" id="{{ $id }}" class="form-select @if($error) is-invalid @endif">
                            <option value="">{{ $t('select_placeholder') }}</option>
                            @foreach ($field->options ?? [] as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('fields.'.$field->id, $field->value) === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @elseif ($field->is_secret)
                        <textarea name="{{ $name }}" id="{{ $id }}" rows="3" class="form-control gateway-credential @if($error) is-invalid @endif"
                            dir="ltr" spellcheck="false" autocomplete="off" autocapitalize="off" maxlength="20000"
                            placeholder="{{ $field->maskedValue() ? $t('saved_secret', ['mask' => $field->maskedValue()]) : $t('not_set') }}"></textarea>
                        @if ($field->maskedValue())
                            <div class="form-check mt-1">
                                <input type="checkbox" name="clear[{{ $field->id }}]" value="1" class="form-check-input" id="clear-{{ $field->id }}">
                                <label class="form-check-label small" for="clear-{{ $field->id }}">{{ $t('clear_secret') }}</label>
                            </div>
                        @endif
                    @else
                        <textarea name="{{ $name }}" id="{{ $id }}" rows="2" class="form-control gateway-credential @if($error) is-invalid @endif"
                            dir="ltr" spellcheck="false" autocomplete="off" autocapitalize="off" maxlength="20000"
                            placeholder="{{ $t('not_set') }}">{{ old('fields.'.$field->id, $field->value) }}</textarea>
                    @endif

                    @if ($error)<div class="invalid-feedback d-block">{{ $error }}</div>@endif
                    @if ($field->help_text)<div class="form-text">{{ $field->help_text }}</div>@endif
                </div>
            @empty
                <p class="text-muted mb-0">{{ $t('no_fields') }}</p>
            @endforelse

            <button class="btn btn-primary">{{ $t('save') }}</button>
        </div>
    </div>
</form>

{{-- Outside the main form: forms cannot be nested. --}}
@foreach ($gateway->fields as $field)
    @unless (in_array($field->key, $driverKeys, true))
        <form method="post" id="delete-field-{{ $field->id }}" action="{{ route('backend.payment-gateways.fields.destroy', [$gateway, $field]) }}" onsubmit="return confirm(@js($t('confirm_delete_field')))">
            @csrf @method('DELETE')
        </form>
    @endunless
@endforeach

<div class="card">
    <div class="card-body">
        <h5 class="mb-1">{{ $t('add_field') }}</h5>
        <p class="small text-muted">{{ $t('add_field_help') }}</p>
        <form method="post" action="{{ route('backend.payment-gateways.fields.store', $gateway) }}" class="row g-3 align-items-start">
            @csrf
            <div class="col-md-4">
                <label class="form-label" for="new-field-key">{{ $t('field_key') }} <span class="text-danger">*</span></label>
                <input type="text" name="key" id="new-field-key" value="{{ old('key') }}" class="form-control" maxlength="64" pattern="[a-z0-9_]+" required dir="ltr">
                <div class="form-text">{{ $t('field_key_help') }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="new-field-label">{{ $t('field_label') }} <span class="text-danger">*</span></label>
                <input type="text" name="label" id="new-field-label" value="{{ old('label') }}" class="form-control" maxlength="150" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="new-field-type">{{ $t('field_type') }}</label>
                <select name="type" id="new-field-type" class="form-select">
                    <option value="textarea" @selected(old('type', 'textarea') === 'textarea')>{{ $t('type_textarea') }}</option>
                    <option value="select" @selected(old('type') === 'select')>{{ $t('type_select') }}</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="new-field-options">{{ $t('field_options') }}</label>
                <textarea name="options" id="new-field-options" rows="3" class="form-control gateway-credential" dir="ltr" placeholder="test=Test&#10;live=Live">{{ old('options') }}</textarea>
                <div class="form-text">{{ $t('field_options_help') }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="new-field-help">{{ $t('field_help') }}</label>
                <textarea name="help_text" id="new-field-help" rows="3" class="form-control" maxlength="255">{{ old('help_text') }}</textarea>
            </div>
            <div class="col-md-6 d-flex flex-wrap gap-4">
                <div class="form-check">
                    <input type="hidden" name="is_required" value="0">
                    <input type="checkbox" name="is_required" value="1" class="form-check-input" id="new-field-required" @checked(old('is_required'))>
                    <label class="form-check-label" for="new-field-required">{{ $t('field_required') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="is_secret" value="0">
                    <input type="checkbox" name="is_secret" value="1" class="form-check-input" id="new-field-secret" @checked(old('is_secret'))>
                    <label class="form-check-label" for="new-field-secret">{{ $t('field_secret') }}</label>
                </div>
            </div>
            <div class="col-12"><button class="btn btn-outline-primary">{{ $t('add_field') }}</button></div>
        </form>
    </div>
</div>
@endsection
