@extends('backend.layouts.app')

@php $t = fn ($k) => __('wallet_ledger.admin.'.$k); @endphp

@section('title') {{ $t('withdrawals') }} @endsection

@section('content')
@include('backend.wallet._nav')

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach (['pending', 'approved', 'paid', 'rejected', 'all'] as $s)
                <a href="{{ route('wallet.withdrawals', ['status' => $s]) }}" class="btn btn-sm {{ $status === $s ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    {{ $s === 'all' ? $t('all') : __('wallet_ledger.withdraw_status.'.$s) }}
                </a>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>#</th><th>{{ $t('customer') }}</th><th>{{ $t('amount') }}</th><th>{{ $t('bank') }}</th><th>{{ $t('status') }}</th><th>{{ $t('date') }}</th><th>{{ $t('actions') }}</th></tr></thead>
                <tbody>
                @forelse ($rows as $w)
                    <tr>
                        <td>{{ $w->id }}</td>
                        <td>{{ trim(($w->user->first_name ?? '').' '.($w->user->last_name ?? '')) }}<br><small class="text-muted">{{ $w->user->mobile ?? '' }}</small></td>
                        <td><b>{{ number_format($w->amount, 2) }}</b></td>
                        <td>
                            @if ($w->iban)
                                <span dir="ltr">{{ $w->iban }}</span><br><small>{{ $w->account_name }}</small>
                            @elseif ($w->bank)
                                {{ $w->bank->bank_name }}<br><small dir="ltr">{{ $w->bank->account_no }}</small>
                            @else — @endif
                        </td>
                        <td>
                            {{ __('wallet_ledger.withdraw_status.'.$w->status) }}
                            @if ($w->reference)<br><small>{{ $t('reference') }}: {{ $w->reference }}</small>@endif
                            @if ($w->review_note)<br><small class="text-muted">{{ $w->review_note }}</small>@endif
                        </td>
                        <td>{{ $w->created_at }}</td>
                        <td style="min-width: 260px">
                            @if ($w->status === 'pending')
                                <form method="post" action="{{ route('wallet.withdrawals.approve', $w) }}" class="d-inline">@csrf
                                    <button class="btn btn-sm btn-success">{{ $t('approve') }}</button>
                                </form>
                            @endif
                            @if (in_array($w->status, ['pending', 'approved'], true))
                                <form method="post" action="{{ route('wallet.withdrawals.paid', $w) }}" class="d-flex gap-1 mt-1">@csrf
                                    <input name="reference" class="form-control form-control-sm" placeholder="{{ $t('reference') }}" required>
                                    <button class="btn btn-sm btn-primary text-nowrap">{{ $t('mark_paid') }}</button>
                                </form>
                                <form method="post" action="{{ route('wallet.withdrawals.reject', $w) }}" class="d-flex gap-1 mt-1">@csrf
                                    <input name="note" class="form-control form-control-sm" placeholder="{{ $t('reject_reason') }}" required>
                                    <button class="btn btn-sm btn-outline-danger">{{ $t('reject') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">{{ $t('no_records') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </div>
</div>
@endsection
