@extends('backend.layouts.app')

@php $t = fn ($k) => __('gift_system.admin.'.$k); @endphp

@section('title') {{ $t('batches') }} @endsection

@section('content')
@include('backend.gift._nav')

<div class="card mb-3">
    <div class="card-body">
        <h5>{{ $t('create_batch') }}</h5>
        <form method="post" action="{{ route('gifts.batches.store') }}" class="row g-2" id="batch-form">
            @csrf
            <div class="col-md-4"><label class="form-label">{{ $t('batch_name') }}</label><input name="name" value="{{ old('name') }}" class="form-control" required maxlength="150"></div>
            <div class="col-md-2">
                <label class="form-label">{{ $t('source') }}</label>
                <select name="source" class="form-select">
                    @foreach (['admin', 'corporate', 'campaign'] as $s)<option value="{{ $s }}" @selected(old('source') === $s)>{{ __('gift_system.source.'.$s) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ $t('type') }}</label>
                <select name="type" class="form-select" id="batch-type">
                    @foreach (['value', 'service', 'package'] as $s)<option value="{{ $s }}" @selected(old('type', 'value') === $s)>{{ __('gift_system.type.'.$s) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">{{ $t('quantity') }}</label><input type="number" name="quantity" min="1" max="1000" value="{{ old('quantity', 10) }}" class="form-control" required></div>
            <div class="col-md-2" data-for="value"><label class="form-label">{{ $t('value') }}</label><input type="number" step="0.01" min="1" name="value_amount" value="{{ old('value_amount') }}" class="form-control"></div>
            <div class="col-md-6" data-for="service">
                <label class="form-label">{{ $t('services') }}</label>
                <select name="requested_services[]" class="form-select" multiple size="5">
                    @foreach ($services as $s)<option value="{{ $s->id }}">{{ $s->name }} — {{ number_format((float) $s->default_price, 2) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6" data-for="package">
                <label class="form-label">{{ $t('packages') }}</label>
                <select name="requested_packages[]" class="form-select" multiple size="5">
                    @foreach ($packages as $p)<option value="{{ $p->id }}">{{ $p->name }} — {{ number_format((float) $p->package_price, 2) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ $t('branch') }}</label>
                <select name="branch_id" class="form-select">
                    <option value="">{{ $t('any_branch') }}</option>
                    @foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ $t('design') }}</label>
                <select name="design" class="form-select">
                    @foreach ($designs as $d)<option value="{{ $d->slug }}">{{ $d->name_ar }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">{{ $t('expires_at') }}</label><input type="date" name="expires_at" min="{{ now()->addDay()->toDateString() }}" value="{{ old('expires_at') }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">{{ $t('note') }}</label><input name="note" value="{{ old('note') }}" class="form-control" maxlength="1000"></div>
            <div class="col-12"><button class="btn btn-primary">{{ $t('create_batch') }}</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table align-middle">
            <thead><tr><th>#</th><th>{{ $t('batch_name') }}</th><th>{{ $t('type') }}</th><th>{{ $t('quantity') }}</th><th>{{ $t('used') }}</th><th>{{ $t('closed') }}</th><th>{{ $t('expires_at') }}</th><th>{{ $t('created_by') }}</th><th>{{ $t('exports') }}</th><th></th></tr></thead>
            <tbody>
            @forelse ($batches as $b)
                <tr>
                    <td>{{ $b->id }}</td>
                    <td><a href="{{ route('app.gift', ['batch' => $b->id]) }}">{{ $b->name }}</a><br><small class="text-muted">{{ __('gift_system.source.'.$b->source) }}</small></td>
                    <td>{{ __('gift_system.type.'.$b->type) }}@if($b->value_amount) · {{ number_format($b->value_amount, 2) }}@endif</td>
                    <td>{{ $b->quantity }}</td>
                    <td>{{ $b->used_count }}</td>
                    <td>{{ $b->cancelled_count }}</td>
                    <td>{{ $b->expires_at?->toDateString() ?? '—' }}</td>
                    <td>{{ $b->creator ? trim($b->creator->first_name.' '.$b->creator->last_name) : '' }}</td>
                    <td>{{ $b->export_count }}</td>
                    <td><a href="{{ route('gifts.batches.export', $b) }}" class="btn btn-sm btn-outline-secondary">{{ $t('export_csv') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted">{{ $t('no_records') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $batches->links() }}
    </div>
</div>
@endsection

@push('after-scripts')
<script>
(() => {
    const type = document.getElementById('batch-type');
    const sync = () => document.querySelectorAll('#batch-form [data-for]').forEach(el => {
        el.style.display = el.dataset.for === type.value ? '' : 'none';
    });
    type.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
