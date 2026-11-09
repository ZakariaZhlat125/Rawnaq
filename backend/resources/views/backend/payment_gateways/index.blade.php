@extends('backend.layouts.app')

@php $t = fn ($k, $r = []) => __('payment_gateways.'.$k, $r); @endphp

@section('title') {{ $t('title') }} @endsection

@section('content')
@include('backend.payment_gateways._alerts')

<p class="text-muted">{{ $t('subtitle') }}</p>

<div class="card mb-3">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ $t('name_ar') }}</th>
                        <th>{{ $t('code') }}</th>
                        <th>{{ $t('driver') }}</th>
                        <th>{{ $t('status') }}</th>
                        <th>{{ $t('fields_count') }}</th>
                        <th>{{ $t('sort_order') }}</th>
                        <th class="text-end">{{ $t('actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gateways as $gateway)
                        @php $missing = count($gateway->missingFields()); @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $gateway->name_ar }}</div>
                                @if ($gateway->name_en)<div class="small text-muted">{{ $gateway->name_en }}</div>@endif
                            </td>
                            <td><code>{{ $gateway->code }}</code></td>
                            <td>{{ $drivers[$gateway->driver] ?? $gateway->driver }}</td>
                            <td>
                                @if ($gateway->is_active)
                                    <span class="badge bg-success">{{ $t('active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ $t('inactive') }}</span>
                                @endif
                                @if ($missing)
                                    <span class="badge bg-warning text-dark">{{ $t('incomplete', ['count' => $missing]) }}</span>
                                @endif
                            </td>
                            <td>{{ $gateway->fields->count() }}</td>
                            <td>{{ $gateway->sort_order }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('backend.payment-gateways.edit', $gateway) }}" class="btn btn-sm btn-primary">{{ $t('edit') }}</a>
                                <form method="post" action="{{ route('backend.payment-gateways.destroy', $gateway) }}" class="d-inline" onsubmit="return confirm(@js($t('confirm_delete')))">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">{{ $t('delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted text-center">{{ $t('no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5>{{ $t('add_gateway') }}</h5>
        <p class="small text-muted">{{ $t('add_gateway_help') }}</p>
        <form method="post" action="{{ route('backend.payment-gateways.store') }}" class="row g-3 align-items-start">
            @csrf
            <div class="col-md-4">
                <label class="form-label" for="new-driver">{{ $t('driver') }} <span class="text-danger">*</span></label>
                <select name="driver" id="new-driver" class="form-select" required>
                    <option value="">{{ $t('select_placeholder') }}</option>
                    @foreach ($drivers as $key => $label)
                        <option value="{{ $key }}" @selected(old('driver') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="new-code">{{ $t('code') }} <span class="text-danger">*</span></label>
                <input type="text" name="code" id="new-code" value="{{ old('code') }}" class="form-control" maxlength="20" pattern="[a-z0-9_\-]+" required dir="ltr">
                <div class="form-text">{{ $t('code_help') }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="new-sort">{{ $t('sort_order') }}</label>
                <input type="number" name="sort_order" id="new-sort" value="{{ old('sort_order', ($gateways->max('sort_order') ?? 0) + 10) }}" class="form-control" min="0">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="new-name-ar">{{ $t('name_ar') }} <span class="text-danger">*</span></label>
                <input type="text" name="name_ar" id="new-name-ar" value="{{ old('name_ar') }}" class="form-control" maxlength="150" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="new-name-en">{{ $t('name_en') }}</label>
                <input type="text" name="name_en" id="new-name-en" value="{{ old('name_en') }}" class="form-control" maxlength="150" dir="ltr">
            </div>
            <div class="col-12"><button class="btn btn-primary">{{ $t('create') }}</button></div>
        </form>
    </div>
</div>
@endsection
