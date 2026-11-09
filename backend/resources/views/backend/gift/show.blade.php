@extends('backend.layouts.app')

@php
    $t = fn ($k) => __('gift_system.admin.'.$k);
    $status = in_array($gift->gift_status, \App\Models\GiftCard::USABLE_STATUSES, true) && $gift->isExpired() ? 'expired' : $gift->gift_status;
    $cancellable = in_array($gift->gift_status, ['pending_payment', 'paid', 'sent', 'expired'], true) && ! $gift->claimed_at && ! $gift->redeemed_at;
    $extendable = ! $gift->redeemed_at && ! in_array($gift->gift_status, ['cancelled', 'refunded', 'redeemed'], true);
@endphp

@section('title') {{ $t('title') }} #{{ $gift->id }} @endsection

@section('content')
@include('backend.gift._nav')

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3"><div class="card-body">
            <h5 class="mb-3">{{ $t('details') }} — #{{ $gift->id }}
                <span class="badge bg-secondary ms-2">{{ __('gift_system.status.'.$status) }}</span>
            </h5>
            <dl class="row mb-0">
                <dt class="col-sm-4">{{ $t('type') }}</dt><dd class="col-sm-8">{{ __('gift_system.type.'.$gift->type) }} · {{ __('gift_system.source.'.$gift->source) }}@if($gift->batch) · <a href="{{ route('app.gift', ['batch' => $gift->batch_id]) }}">{{ $gift->batch->name }}</a>@endif</dd>
                <dt class="col-sm-4">{{ $t('value') }}</dt><dd class="col-sm-8">{{ number_format($gift->faceValue(), 2) }} SAR</dd>
                @if ($gift->services_list->isNotEmpty() || $gift->packages_list->isNotEmpty())
                    <dt class="col-sm-4">{{ $t('services') }}</dt>
                    <dd class="col-sm-8">{{ $gift->services_list->pluck('name')->merge($gift->packages_list->pluck('name'))->implode('، ') }}</dd>
                @endif
                <dt class="col-sm-4">{{ $t('buyer') }}</dt><dd class="col-sm-8">{{ $gift->user ? trim($gift->user->first_name.' '.$gift->user->last_name).' ('.$gift->user->mobile.')' : ($gift->sender_name ?: '—') }}</dd>
                <dt class="col-sm-4">{{ $t('recipient') }}</dt>
                <dd class="col-sm-8">{{ $gift->recipient_name ?: '—' }} <span dir="ltr">{{ $gift->recipient_phone }}</span>
                    @if ($gift->recipient)<br><small>→ #{{ $gift->recipient->id }} {{ trim($gift->recipient->first_name.' '.$gift->recipient->last_name) }}</small>@endif
                </dd>
                <dt class="col-sm-4">{{ $t('branch') }}</dt><dd class="col-sm-8">{{ $gift->branch->name ?? $t('any_branch') }}</dd>
                <dt class="col-sm-4">{{ $t('code') }}</dt>
                <dd class="col-sm-8"><span dir="ltr" id="gift-code">{{ $gift->maskedCode() ?? '—' }}</span>
                    @can('manage_gifts')
                        @if ($gift->redeem_code_encrypted)
                            <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="reveal-code">{{ $t('show_code') }}</button>
                        @endif
                    @endcan
                </dd>
                <dt class="col-sm-4">{{ $t('expires_at') }}</dt><dd class="col-sm-8">{{ $gift->expires_at?->toDateString() ?? '—' }}</dd>
                @if ($gift->message)<dt class="col-sm-4">✉</dt><dd class="col-sm-8">{{ $gift->message }}</dd>@endif
                @if ($gift->cancel_reason)<dt class="col-sm-4">{{ $t('cancel_reason') }}</dt><dd class="col-sm-8">{{ $gift->cancel_reason }}</dd>@endif
            </dl>
        </div></div>

        @if ($gift->redemptions->isNotEmpty())
            <div class="card mb-3"><div class="card-body">
                <h5>{{ $t('redemptions') }}</h5>
                <table class="table table-sm mb-0">
                    @foreach ($gift->redemptions as $r)
                        <tr>
                            <td>{{ $r->created_at }}</td>
                            <td>{{ $r->channel === 'branch' ? '🏪' : '🌐' }} {{ trim(($r->user->first_name ?? '').' '.($r->user->last_name ?? '')) }}</td>
                            <td>{{ $r->amount > 0 ? number_format($r->amount, 2).' SAR → wallet' : ($r->booking_id ? 'booking #'.$r->booking_id : '') }}</td>
                            <td>{{ $r->staff ? trim($r->staff->first_name.' '.$r->staff->last_name) : '' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div></div>
        @endif

        @can('manage_gifts')
            <div class="card"><div class="card-body">
                <h5>{{ $t('actions') }}</h5>
                <div class="row g-3">
                    @if ($gift->isUsable() && $gift->recipient_phone)
                        <div class="col-12">
                            <form method="post" action="{{ route('gifts.resend', $gift->id) }}">@csrf
                                <button class="btn btn-outline-primary btn-sm">{{ $t('resend') }}</button>
                            </form>
                        </div>
                    @endif
                    @if ($extendable)
                        <div class="col-md-6">
                            <form method="post" action="{{ route('gifts.extend', $gift->id) }}" class="d-grid gap-2">@csrf
                                <label class="form-label mb-0">{{ $t('extend') }}</label>
                                <input type="date" name="expires_at" class="form-control form-control-sm" min="{{ now()->addDay()->toDateString() }}" required>
                                <input name="reason" class="form-control form-control-sm" placeholder="{{ $t('reason') }}">
                                <button class="btn btn-sm btn-primary">{{ $t('save') }}</button>
                            </form>
                        </div>
                    @endif
                    @if ($cancellable)
                        <div class="col-md-6">
                            <form method="post" action="{{ route('gifts.cancel', $gift->id) }}" class="d-grid gap-2" onsubmit="return confirm('{{ $t('cancel') }}?')">@csrf
                                <label class="form-label mb-0">{{ $t('cancel') }}</label>
                                <input name="reason" class="form-control form-control-sm" placeholder="{{ $t('cancel_reason') }}" minlength="3" required>
                                @if ($gift->source === 'customer' && (int) $gift->payment_status === 1)
                                    <label class="form-check"><input type="checkbox" name="refund" value="1" class="form-check-input" checked> {{ $t('refund_buyer') }}</label>
                                @endif
                                <button class="btn btn-sm btn-outline-danger">{{ $t('cancel') }}</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div></div>
        @endcan
    </div>

    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <h5>{{ $t('timeline') }}</h5>
            <ul class="list-unstyled mb-0">
                @forelse ($gift->events as $e)
                    <li class="border-start border-2 ps-3 pb-3 position-relative">
                        <b>{{ __('gift_system.event.'.$e->event) }}</b>
                        <div class="small text-muted">{{ $e->created_at }}@if($e->actor) · {{ trim($e->actor->first_name.' '.$e->actor->last_name) }}@endif</div>
                        @if (! empty($e->meta['reason']))<div class="small">{{ $e->meta['reason'] }}</div>@endif
                        @if (! empty($e->meta['to']) && ! empty($e->meta['from']))<div class="small">{{ $e->meta['from'] }} → {{ $e->meta['to'] }}</div>@endif
                    </li>
                @empty
                    <li class="text-muted">{{ $t('no_records') }}</li>
                @endforelse
            </ul>
        </div></div>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
document.getElementById('reveal-code')?.addEventListener('click', async (e) => {
    const res = await fetch(@json(route('gifts.code', $gift->id)), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
    });
    if (res.ok) {
        document.getElementById('gift-code').textContent = (await res.json()).code || '—';
        e.target.remove();
    }
});
</script>
@endpush
